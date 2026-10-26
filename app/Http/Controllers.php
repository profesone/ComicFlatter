<?php

declare(strict_types=1);

namespace App\Http;

use App\Auth\Auth;
use App\Domain\JobRepository;
use App\Domain\UserRepository;
use App\Security\Csrf;
use App\Services\ComicLetterer;
use App\Services\Flatting;
use App\Services\Images;
use App\Services\Mailer;
use App\Services\Storage;
use App\Support\Db;
use App\Support\Env;
use App\Support\View;
use Stripe\Checkout\Session;
use Stripe\Price;
use Stripe\Stripe;
use Stripe\Webhook;

final class Controllers
{
    public static function home(): void
    {
        View::render('home', [
            'title' => 'Comic-Subs — Tools for comic production',
            'user' => Auth::user(),
        ]);
    }

    public static function flatter(): void
    {
        View::render('flatter', [
            'title' => 'Comic Flatter — Comic-Subs',
            'freeMode' => Env::bool('FREE_MODE'),
            'user' => Auth::user(),
        ]);
    }

    public static function balloons(): void
    {
        $user = Auth::user();
        View::render('balloons', [
            'title' => 'Comic Balloon Popper — Comic-Subs',
            'user' => $user,
            'csrf' => Csrf::token(),
            'freeMode' => Env::bool('FREE_MODE'),
            'credits' => $user ? (new JobRepository())->balloonCreditCount($user['id']) : 0,
        ]);
    }

    public static function balloonUpload(): void
    {
        self::jsonGuard();
        $user = Auth::requireUser();
        if (empty($_FILES['page']['tmp_name']) || !is_uploaded_file($_FILES['page']['tmp_name'])) {
            self::json(['error' => 'Upload a JPG or PNG page.'], 400);
        }
        $bytes = file_get_contents($_FILES['page']['tmp_name']);
        $check = Images::validate((string) $bytes, 20 * 1024 * 1024);
        if (!$check['ok'] || !in_array($check['ext'], ['png', 'jpg'], true)) {
            self::json(['error' => $check['ok'] ? 'Use a JPG or PNG page.' : $check['error']], 400);
        }
        if (!class_exists(\Imagick::class)) {
            self::json(['error' => 'Imagick is required to letter a page.'], 500);
        }
        $image = new \Imagick();
        $image->readImageBlob((string) $bytes);
        if (method_exists($image, 'autoOrient')) {
            $image->autoOrient();
        }
        $long = max($image->getImageWidth(), $image->getImageHeight());
        if ($long > 2000) {
            $scale = 2000 / $long;
            $image->resizeImage(
                max(1, (int) round($image->getImageWidth() * $scale)),
                max(1, (int) round($image->getImageHeight() * $scale)),
                \Imagick::FILTER_LANCZOS,
                1
            );
        }
        $image->setImageFormat('png');
        $width = $image->getImageWidth();
        $height = $image->getImageHeight();
        $png = $image->getImagesBlob();
        $image->clear();
        $relative = Storage::save('balloons/' . $user['id'], $png, 'png');
        $pageId = bin2hex(random_bytes(8));
        $_SESSION['balloon_pages'][$pageId] = $relative;
        self::json([
            'pageId' => $pageId,
            'pageUrl' => '/uploads/' . $relative,
            'width' => $width,
            'height' => $height,
        ]);
    }

    public static function balloonGenerate(): void
    {
        self::jsonGuard();
        $user = Auth::requireUser();
        $payload = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($payload)) {
            self::json(['error' => 'Invalid request.'], 400);
        }
        $pageId = (string) ($payload['pageId'] ?? '');
        $relative = $_SESSION['balloon_pages'][$pageId] ?? null;
        if (!is_string($relative) || !str_starts_with($relative, 'balloons/' . $user['id'] . '/')) {
            self::json(['error' => 'Upload a page first.'], 403);
        }
        $absolute = Storage::absolute($relative);
        if ($absolute === null) {
            self::json(['error' => 'Page file is missing.'], 404);
        }
        $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];
        $jobs = new JobRepository();
        $credit = $jobs->openBalloonCredit($user['id']);
        if ($credit === null) {
            self::json(['error' => 'Buy a page before drawing balloons.'], 402);
        }
        try {
            @set_time_limit(120);
            $png = (new ComicLetterer())->render($absolute, (int) ($payload['panelCount'] ?? 1), $lines);
            $out = Storage::save('balloons/' . $user['id'] . '/overlays', $png, 'png');
            $jobs->useBalloonCredit($credit);
            self::json(['overlayUrl' => '/uploads/' . $out]);
        } catch (\Throwable $e) {
            self::json(['error' => $e->getMessage()], 500);
        }
    }

    private static function jsonGuard(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? null);
        if (!Csrf::verify(is_string($token) ? $token : null)) {
            self::json(['error' => 'Invalid session token.'], 419);
        }
    }

    private static function json(array $body, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($body);
        exit;
    }

    public static function jobs(): void
    {
        $posts = Db::pdo()->query(
            'SELECT board_posts.role, board_posts.title, board_posts.body, board_posts.created_at, users.name, users.email
             FROM board_posts INNER JOIN users ON users.id = board_posts.user_id
             ORDER BY board_posts.created_at DESC'
        )->fetchAll();
        View::render('jobs', [
            'title' => 'Creator board — Comic-Subs',
            'user' => Auth::user(),
            'posts' => $posts,
            'error' => $_SESSION['board_error'] ?? null,
        ]);
        unset($_SESSION['board_error']);
    }

    public static function jobCreate(): void
    {
        $user = Auth::requireUser();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            exit('Invalid session token.');
        }
        $role = trim((string) ($_POST['role'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $allowed = ['Letterer', 'Flatter', 'Colorist', 'Other'];
        if (!in_array($role, $allowed, true) || $title === '' || mb_strlen($title) > 160 || $body === '' || mb_strlen($body) > 4000) {
            $_SESSION['board_error'] = 'Add a role, a short title, and a description under 4000 characters.';
            header('Location: /jobs');
            exit;
        }
        Db::pdo()->prepare(
            'INSERT INTO board_posts (id, user_id, role, title, body) VALUES (?, ?, ?, ?, ?)'
        )->execute([UserRepository::uuid(), $user['id'], $role, $title, $body]);
        header('Location: /jobs');
        exit;
    }

    public static function privacy(): void
    {
        View::render('privacy', ['title' => 'Privacy · ComicFlats', 'user' => Auth::user()]);
    }

    public static function terms(): void
    {
        View::render('terms', ['title' => 'Terms · ComicFlats', 'user' => Auth::user()]);
    }

    public static function conduct(): void
    {
        View::render('conduct', ['title' => 'Code of conduct · Comic-Subs', 'user' => Auth::user()]);
    }

    public static function loginForm(): void
    {
        View::render('login', ['title' => 'Log in · ComicFlats', 'error' => null, 'user' => null]);
    }

    public static function login(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            echo 'Invalid session token';
            return;
        }
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $bucket = hash('sha256', $ip . '|' . $email);
        if (Auth::tooManyAttempts($bucket)) {
            View::render('login', ['title' => 'Log in', 'error' => 'Too many attempts. Try again in 15 minutes.', 'user' => null]);
            return;
        }
        $user = (new UserRepository())->findByEmail($email);
        if ($user && !empty($user['deleted_at'])) {
            View::render('login', [
                'title' => 'Log in',
                'error' => 'This account was closed. Create it again with the same email to restore your purchase history.',
                'user' => null,
            ]);
            return;
        }
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Auth::recordAttempt($bucket);
            View::render('login', ['title' => 'Log in', 'error' => 'Email or password is incorrect.', 'user' => null]);
            return;
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
            (new UserRepository())->updatePassword($user['id'], $password);
        }
        Auth::clearAttempts($bucket);
        Auth::login($user);
        header('Location: /studio');
    }

    public static function registerForm(): void
    {
        View::render('register', ['title' => 'Create account · ComicFlats', 'error' => null, 'user' => null]);
    }

    public static function register(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            echo 'Invalid session token';
            return;
        }
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $name = trim((string) ($_POST['name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            View::render('register', ['title' => 'Create account', 'error' => 'Enter a valid email.', 'user' => null]);
            return;
        }
        if (strlen($password) < 10) {
            View::render('register', ['title' => 'Create account', 'error' => 'Use at least 10 characters.', 'user' => null]);
            return;
        }
        $repo = new UserRepository();
        $existing = $repo->findByEmail($email);
        if ($existing && empty($existing['deleted_at'])) {
            View::render('register', ['title' => 'Create account', 'error' => 'An account with that email already exists.', 'user' => null]);
            return;
        }
        if ($existing) {
            $repo->reopen($existing['id'], $password, $name);
            $id = $existing['id'];
        } else {
            $id = $repo->create($email, $password, $name);
        }
        Auth::login($repo->findById($id));
        header('Location: /studio');
    }

    public static function logout(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            echo 'Invalid session token';
            return;
        }
        Auth::logout();
        header('Location: /');
    }

    public static function forgotForm(): void
    {
        View::render('forgot', ['title' => 'Reset password', 'message' => null, 'error' => null, 'user' => null]);
    }

    public static function forgot(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            echo 'Invalid session token';
            return;
        }
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $repo = new UserRepository();
        if ($repo->findByEmail($email)) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $stmt = Db::pdo()->prepare(
                'INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
            );
            $stmt->execute([$email, $hash]);
            $url = rtrim(Env::get('APP_URL', '') ?? '', '/') . '/reset?token=' . urlencode($token) . '&email=' . urlencode($email);
            Mailer::send($email, 'Reset your ComicFlats password', "Open this link within one hour:\n{$url}\n");
        }
        View::render('forgot', [
            'title' => 'Reset password',
            'message' => 'If that email exists, we sent a reset link.',
            'error' => null,
            'user' => null,
        ]);
    }

    public static function resetForm(): void
    {
        View::render('reset', [
            'title' => 'Choose a new password',
            'token' => (string) ($_GET['token'] ?? ''),
            'email' => (string) ($_GET['email'] ?? ''),
            'error' => null,
            'user' => null,
        ]);
    }

    public static function reset(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            echo 'Invalid session token';
            return;
        }
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $token = (string) ($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) < 10) {
            View::render('reset', ['title' => 'Choose a new password', 'token' => $token, 'email' => $email, 'error' => 'Use at least 10 characters.', 'user' => null]);
            return;
        }
        $hash = hash('sha256', $token);
        $stmt = Db::pdo()->prepare(
            'SELECT id FROM password_resets WHERE email = ? AND token_hash = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$email, $hash]);
        $row = $stmt->fetch();
        if (!$row) {
            View::render('reset', ['title' => 'Choose a new password', 'token' => $token, 'email' => $email, 'error' => 'That reset link is invalid or expired.', 'user' => null]);
            return;
        }
        $user = (new UserRepository())->findByEmail($email);
        if ($user && empty($user['deleted_at'])) {
            (new UserRepository())->updatePassword($user['id'], $password);
        }
        Db::pdo()->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
        header('Location: /login');
    }

    public static function account(): void
    {
        $user = Auth::requireUser();
        View::render('account', [
            'title' => 'Profile · Comic-Subs',
            'user' => $user,
            'purchases' => (new JobRepository())->purchasesFor($user['id']),
            'balloonPurchases' => (new JobRepository())->balloonPurchases($user['id']),
            'error' => $_SESSION['account_error'] ?? null,
            'saved' => $_SESSION['account_saved'] ?? null,
        ]);
        unset($_SESSION['account_error'], $_SESSION['account_saved']);
    }

    public static function accountUpdate(): void
    {
        $user = Auth::requireUser();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            exit('Invalid session token.');
        }
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $name = trim((string) ($_POST['name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $current = (string) ($_POST['current_password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($name) > 120) {
            $_SESSION['account_error'] = 'Enter a valid email and a name under 120 characters.';
            header('Location: /account');
            exit;
        }
        if (!password_verify($current, $user['password_hash'])) {
            $_SESSION['account_error'] = 'Current password is incorrect.';
            header('Location: /account');
            exit;
        }
        $repo = new UserRepository();
        $taken = $repo->findByEmail($email);
        if ($taken && $taken['id'] !== $user['id']) {
            $_SESSION['account_error'] = 'That email is already in use.';
            header('Location: /account');
            exit;
        }
        if ($password !== '' && strlen($password) < 10) {
            $_SESSION['account_error'] = 'New passwords need at least 10 characters.';
            header('Location: /account');
            exit;
        }
        $repo->updateProfile($user['id'], $email, $name);
        if ($password !== '') {
            $repo->updatePassword($user['id'], $password);
        }
        $_SESSION['account_saved'] = 'Profile saved.';
        header('Location: /account');
        exit;
    }

    public static function accountDelete(): void
    {
        $user = Auth::requireUser();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            exit('Invalid session token.');
        }
        $password = (string) ($_POST['password'] ?? '');
        if (($_POST['confirm'] ?? '') !== 'delete' || !password_verify($password, $user['password_hash'])) {
            $_SESSION['account_error'] = 'To delete the account, enter your password and type delete.';
            header('Location: /account');
            exit;
        }
        (new UserRepository())->close($user['id']);
        Auth::logout();
        header('Location: /?closed=1');
        exit;
    }

    public static function studio(): void
    {
        $user = Auth::requireUser();
        $jobs = new JobRepository();
        $open = $jobs->usableFor($user['id']);
        $requested = (string) ($_GET['job'] ?? '');
        $job = null;
        foreach ($open as $candidate) {
            if ($candidate['id'] === $requested) {
                $job = $candidate;
                break;
            }
        }
        $job ??= $open[0] ?? null;
        View::render('studio', [
            'title' => 'Studio · ComicFlats',
            'user' => $user,
            'job' => $job,
            'openJobs' => $open,
            'freeMode' => Env::bool('FREE_MODE'),
            'error' => $_GET['error'] ?? null,
            'outputs' => $job ? (json_decode($job['output_paths'] ?: '[]', true) ?: []) : [],
        ]);
    }

    public static function checkout(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            echo 'Invalid session token';
            return;
        }
        $user = Auth::requireUser();
        $jobs = new JobRepository();
        $quantity = max(1, min(12, (int) ($_POST['quantity'] ?? 1)));
        $product = ($_POST['product'] ?? '') === 'balloon' ? 'balloon' : 'flatter';
        if (Env::bool('FREE_MODE') && $product === 'balloon') {
            $session = 'free_' . bin2hex(random_bytes(8));
            for ($i = 0; $i < $quantity; $i++) {
                $jobs->createBalloonCredit($user['id'], $session, 'paid');
            }
            header('Location: /balloons');
            return;
        }
        if (Env::bool('FREE_MODE')) {
            $last = $jobs->createFree($user['id']);
            for ($i = 1; $i < $quantity; $i++) {
                $last = $jobs->createFree($user['id']);
            }
            header('Location: /studio?job=' . urlencode($last));
            return;
        }
        $key = Env::get('STRIPE_SECRET_KEY');
        if (!$key || str_contains($key, '...')) {
            http_response_code(500);
            echo 'Stripe is not configured';
            return;
        }
        Stripe::setApiKey($key);
        $app = rtrim(Env::get('APP_URL', '') ?? '', '/');
        $session = Session::create([
            'mode' => 'payment',
            'line_items' => [[
                'quantity' => $quantity,
                'price' => self::stripePriceId($product === 'balloon' ? 'COMICBALLOON_POPPER_PRICE_ID' : 'COMICFLATTER_PRICE_ID'),
            ]],
            'success_url' => $app . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $app . ($product === 'balloon' ? '/balloons' : '/#pricing'),
            'customer_email' => $user['email'],
            'metadata' => ['user_id' => $user['id'], 'product' => $product, 'quantity' => (string) $quantity],
        ]);
        for ($i = 0; $i < $quantity; $i++) {
            if ($product === 'balloon') {
                $jobs->createBalloonCredit($user['id'], $session->id);
            } else {
                $jobs->createPending($user['id'], $session->id);
            }
        }
        header('Location: ' . $session->url);
    }

    private static function stripePriceId(string $envKey): string
    {
        $id = trim((string) Env::get($envKey, ''));
        if (str_starts_with($id, 'price_')) {
            return $id;
        }
        if (str_starts_with($id, 'prod_')) {
            $prices = Price::all(['product' => $id, 'active' => true, 'limit' => 1]);
            $price = $prices->data[0]->id ?? '';
            if (is_string($price) && $price !== '') {
                return $price;
            }
        }
        throw new \RuntimeException($envKey . ' is missing a Stripe price.');
    }

    public static function checkoutSuccess(): void
    {
        $user = Auth::requireUser();
        $sessionId = (string) ($_GET['session_id'] ?? '');
        $key = Env::get('STRIPE_SECRET_KEY');
        $product = 'flatter';
        if ($sessionId !== '' && $key && !str_contains($key, '...')) {
            Stripe::setApiKey($key);
            $session = Session::retrieve($sessionId);
            $product = (string) ($session->metadata->product ?? 'flatter');
            if (($session->payment_status ?? '') === 'paid') {
                $pi = is_string($session->payment_intent) ? $session->payment_intent : null;
                $jobs = new JobRepository();
                if ($product === 'balloon') {
                    $jobs->markBalloonPaid($sessionId, $pi);
                } else {
                    $jobs->markPaid($sessionId, $pi);
                }
            }
        }
        header('Location: ' . ($product === 'balloon' ? '/balloons' : '/studio'));
    }

    public static function webhook(): void
    {
        $payload = file_get_contents('php://input') ?: '';
        $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $secret = Env::get('STRIPE_WEBHOOK_SECRET');
        $key = Env::get('STRIPE_SECRET_KEY');
        if (!$secret || !$key || $sig === '') {
            http_response_code(400);
            echo 'Webhook not configured';
            return;
        }
        Stripe::setApiKey($key);
        try {
            $event = Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable) {
            http_response_code(400);
            echo 'Invalid signature';
            return;
        }
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            if (($session->payment_status ?? '') === 'paid' || ($session->status ?? '') === 'complete') {
                $pi = is_string($session->payment_intent ?? null) ? $session->payment_intent : null;
                $jobs = new JobRepository();
                $product = (string) ($session->metadata->product ?? 'flatter');
                if ($product === 'balloon') {
                    $jobs->markBalloonPaid((string) $session->id, $pi);
                } else {
                    $jobs->markPaid((string) $session->id, $pi);
                }
            }
        }
        header('Content-Type: application/json');
        echo '{"received":true}';
    }

    public static function process(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            echo 'Invalid session token';
            return;
        }
        $user = Auth::requireUser();
        $jobs = new JobRepository();
        $posted = (string) ($_POST['job_id'] ?? '');
        $job = $posted !== '' ? $jobs->findForUser($posted, $user['id']) : $jobs->latestUsable($user['id']);
        $usableStatus = ['paid', 'processing', 'completed', 'failed'];
        if ($job && (!in_array($job['status'], $usableStatus, true) || (int) $job['attempts_used'] >= (int) $job['max_attempts'] || strtotime((string) $job['expires_at']) <= time())) {
            $job = null;
        }
        if (!$job) {
            header('Location: /studio?error=' . urlencode('Buy a flatting job to unlock the studio.'));
            return;
        }
        if ((int) $job['attempts_used'] >= (int) $job['max_attempts']) {
            header('Location: /studio?error=' . urlencode('All attempts used for this payment.'));
            return;
        }

        $bytes = null;
        if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            $bytes = file_get_contents($_FILES['file']['tmp_name']);
            $check = Images::validate((string) $bytes, 20 * 1024 * 1024);
            if (!$check['ok']) {
                header('Location: /studio?job=' . urlencode($job['id']) . '&error=' . urlencode($check['error']));
                return;
            }
            $relative = Storage::save('inputs/' . $job['id'], (string) $bytes, $check['ext']);
            $jobs->update($job['id'], ['input_path' => $relative]);
            $job['input_path'] = $relative;
        } elseif (!empty($job['input_path'])) {
            $abs = Storage::absolute($job['input_path']);
            $bytes = $abs ? file_get_contents($abs) : false;
        }

        if (!is_string($bytes) || $bytes === '') {
            header('Location: /studio?job=' . urlencode($job['id']) . '&error=' . urlencode('Upload line art first.'));
            return;
        }

        $seed = isset($_POST['seed']) && $_POST['seed'] !== '' ? (int) $_POST['seed'] : random_int(0, 1_000_000_000);
        $region = isset($_POST['region_hint']) && $_POST['region_hint'] !== '' ? (int) $_POST['region_hint'] : null;
        $notes = trim((string) ($_POST['style_notes'] ?? ''));

        $jobs->update($job['id'], ['status' => 'processing', 'style_notes' => $notes, 'region_hint' => $region, 'seed' => $seed, 'error_message' => null]);

        try {
            @set_time_limit(180);
            $result = Flatting::generate($bytes, $seed, $region);
            $out = Storage::save('outputs/' . $job['id'], $result['flats'], 'png');
            $line = Storage::save('lines/' . $job['id'], $result['lines'], 'png');
            $paths = json_decode($job['output_paths'] ?: '[]', true) ?: [];
            $paths[] = $out;
            $jobs->update($job['id'], [
                'status' => 'completed',
                'attempts_used' => ((int) $job['attempts_used']) + 1,
                'output_paths' => json_encode($paths, JSON_THROW_ON_ERROR),
                'line_path' => $line,
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $jobs->update($job['id'], ['status' => 'failed', 'error_message' => $e->getMessage()]);
            header('Location: /studio?job=' . urlencode($job['id']) . '&error=' . urlencode('Processing failed. Please try again.'));
            return;
        }

        header('Location: /studio?job=' . urlencode($job['id']));
    }

    public static function upload(array $params): void
    {
        $relative = $params['path'] ?? '';
        // catch-all not used; path param from route uploads/*
        $request = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $relative = ltrim(substr($request, strlen('/uploads/')), '/');
        $absolute = Storage::absolute($relative);
        if (!$absolute || !is_file($absolute)) {
            http_response_code(404);
            echo 'Not found';
            return;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolute) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($absolute));
        header('X-Content-Type-Options: nosniff');
        readfile($absolute);
    }
}

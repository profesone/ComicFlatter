import { Ratelimit } from "@upstash/ratelimit";
import { Redis } from "@upstash/redis";

type LimitResult = { success: boolean; remaining: number };

let checkoutLimiter: Ratelimit | null = null;
let processLimiter: Ratelimit | null = null;

function getRedis() {
  const url = process.env.UPSTASH_REDIS_REST_URL;
  const token = process.env.UPSTASH_REDIS_REST_TOKEN;
  if (!url || !token) return null;
  return new Redis({ url, token });
}

function getCheckoutLimiter() {
  if (checkoutLimiter) return checkoutLimiter;
  const redis = getRedis();
  if (!redis) return null;
  checkoutLimiter = new Ratelimit({
    redis,
    limiter: Ratelimit.slidingWindow(8, "10 m"),
    prefix: "comicflats:checkout",
  });
  return checkoutLimiter;
}

function getProcessLimiter() {
  if (processLimiter) return processLimiter;
  const redis = getRedis();
  if (!redis) return null;
  processLimiter = new Ratelimit({
    redis,
    limiter: Ratelimit.slidingWindow(12, "10 m"),
    prefix: "comicflats:process",
  });
  return processLimiter;
}

const memoryHits = new Map<string, { count: number; reset: number }>();

function memoryLimit(
  key: string,
  limit: number,
  windowMs: number,
): LimitResult {
  const now = Date.now();
  const entry = memoryHits.get(key);
  if (!entry || entry.reset < now) {
    memoryHits.set(key, { count: 1, reset: now + windowMs });
    return { success: true, remaining: limit - 1 };
  }
  if (entry.count >= limit) {
    return { success: false, remaining: 0 };
  }
  entry.count += 1;
  return { success: true, remaining: limit - entry.count };
}

export async function limitCheckout(ip: string): Promise<LimitResult> {
  const limiter = getCheckoutLimiter();
  if (!limiter) return memoryLimit(`checkout:${ip}`, 8, 10 * 60 * 1000);
  const res = await limiter.limit(ip);
  return { success: res.success, remaining: res.remaining };
}

export async function limitProcess(ip: string): Promise<LimitResult> {
  const limiter = getProcessLimiter();
  if (!limiter) return memoryLimit(`process:${ip}`, 12, 10 * 60 * 1000);
  const res = await limiter.limit(ip);
  return { success: res.success, remaining: res.remaining };
}

export function clientIp(request: Request) {
  return (
    request.headers.get("x-forwarded-for")?.split(",")[0]?.trim() ||
    request.headers.get("x-real-ip") ||
    "unknown"
  );
}

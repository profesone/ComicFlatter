import Link from "next/link";

export function SiteFooter() {
  return (
    <footer className="border-t-4 border-ink bg-ink text-paper">
      <div className="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-10 sm:flex-row sm:items-end sm:justify-between sm:px-6">
        <div>
          <p className="font-display text-3xl tracking-wide">ComicFlats</p>
          <p className="mt-2 max-w-sm text-sm text-paper/70">
            Professional comic flatting for colorists. Upload line art, get clean
            solid-color flats ready for Photoshop & Clip Studio.
          </p>
        </div>
        <div className="flex flex-col items-start gap-3 sm:items-end">
          <div className="flex gap-4 text-sm font-semibold uppercase tracking-wide">
            <Link href="/privacy" className="hover:text-cyan">
              Privacy
            </Link>
            <Link href="/terms" className="hover:text-cyan">
              Terms
            </Link>
          </div>
          <p className="rounded-sm border border-paper/30 px-3 py-1 font-mono text-xs uppercase tracking-widest text-paper/80">
            Payments by Stripe
          </p>
          <p className="text-xs text-paper/50">
            © {new Date().getFullYear()} ComicFlats
          </p>
        </div>
      </div>
    </footer>
  );
}

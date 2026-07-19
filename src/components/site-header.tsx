import Link from "next/link";
import { isFreeMode } from "@/lib/flags";

export function SiteHeader() {
  const freeMode = isFreeMode();

  return (
    <header className="relative z-20 border-b-4 border-ink bg-paper">
      <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
        <Link href="/" className="group flex items-baseline gap-2">
          <span className="font-display text-3xl tracking-wide text-ink transition-transform group-hover:-rotate-1 sm:text-4xl">
            ComicFlats
          </span>
          <span className="hidden font-mono text-xs uppercase tracking-[0.2em] text-ink/50 sm:inline">
            {freeMode ? "free mode" : "pro flats"}
          </span>
        </Link>
        <nav className="flex items-center gap-3 sm:gap-5">
          <a
            href="/#gallery"
            className="hidden text-sm font-semibold uppercase tracking-wide text-ink/70 hover:text-ink sm:inline"
          >
            Examples
          </a>
          <a
            href="/#pricing"
            className="hidden text-sm font-semibold uppercase tracking-wide text-ink/70 hover:text-ink sm:inline"
          >
            Pricing
          </a>
          <Link
            href={freeMode ? "/studio" : "/#pricing"}
            className="inline-flex items-center bg-cyan px-4 py-2 font-display text-lg tracking-wide text-ink shadow-[4px_4px_0_0_#0a0a0a] transition hover:-translate-y-0.5 hover:shadow-[6px_6px_0_0_#0a0a0a]"
          >
            {freeMode ? "Open studio" : "Try it — $4.99"}
          </Link>
        </nav>
      </div>
    </header>
  );
}

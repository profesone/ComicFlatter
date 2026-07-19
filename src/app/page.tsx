import { Check, Sparkles, Timer, Wand2 } from "lucide-react";
import { BeforeAfterGallery } from "@/components/before-after";
import { CheckoutButton } from "@/components/checkout-button";
import { isFreeMode } from "@/lib/flags";

const benefits = [
  {
    icon: Wand2,
    title: "Pro flatting style",
    body: "Solid regions, clean edges, trapping under the lines — built for real colorist workflows.",
  },
  {
    icon: Timer,
    title: "Minutes, not hours",
    body: "Skip tedious bucket-fills. Generate a first pass fast, then refine in Photoshop or CSP.",
  },
  {
    icon: Sparkles,
    title: "4 attempts included",
    body: "Regenerate with new seeds or style notes until the flats feel right — all in one $4.99 payment.",
  },
];

export default function HomePage() {
  const freeMode = isFreeMode();

  return (
    <>
      <section className="relative overflow-hidden border-b-4 border-ink bg-ink text-paper">
        <div
          aria-hidden
          className="pointer-events-none absolute inset-0 opacity-30"
          style={{
            backgroundImage:
              "radial-gradient(circle at 20% 20%, #00d4ff 0%, transparent 40%), radial-gradient(circle at 80% 30%, #ffe600 0%, transparent 35%), linear-gradient(135deg, #0a0a0a 0%, #15202b 100%)",
          }}
        />
        <div className="relative mx-auto grid min-h-[calc(100vh-5rem)] max-w-6xl items-center gap-10 px-4 py-14 lg:grid-cols-[1.05fr_0.95fr] lg:px-6 lg:py-20">
          <div className="animate-slide-up">
            <p className="font-display text-5xl leading-none tracking-wide text-cyan sm:text-7xl md:text-8xl">
              ComicFlats
            </p>
            <h1 className="mt-5 max-w-xl text-2xl font-semibold leading-snug text-paper sm:text-3xl">
              Upload B&W comic line art → get professional flats in seconds.
            </h1>
            <p className="mt-4 max-w-lg text-base text-paper/75 sm:text-lg">
              Perfect for colorists using Photoshop, Clip Studio, and more.
              Clean solid colors. Crisp lines. Ready to paint.
            </p>
            <div className="mt-8 flex flex-wrap items-center gap-4">
              <CheckoutButton className="animate-pop" freeMode={freeMode}>
                {freeMode ? "Open studio — free" : "Try it — $4.99"}
              </CheckoutButton>
              <a
                href="#gallery"
                className="font-display text-xl tracking-wide text-yellow underline decoration-2 underline-offset-4"
              >
                See before / after
              </a>
            </div>
          </div>

          <div className="animate-drift relative">
            <div className="absolute -left-3 -top-3 z-10 bg-yellow px-3 py-1 font-mono text-xs uppercase tracking-[0.2em] text-ink shadow-[3px_3px_0_0_#00d4ff]">
              Demo
            </div>
            <div className="grid grid-cols-2 gap-3 border-4 border-paper bg-paper p-3 shadow-[12px_12px_0_0_#00d4ff]">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src="/demos/lineart-hero.svg"
                alt="Sample comic line art"
                className="aspect-[4/5] w-full border-2 border-ink object-cover"
              />
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src="/demos/flats-hero.svg"
                alt="Sample comic flats"
                className="aspect-[4/5] w-full border-2 border-ink object-cover"
              />
            </div>
          </div>
        </div>
      </section>

      <section id="gallery" className="border-b-4 border-ink bg-halftone py-16">
        <div className="mx-auto max-w-6xl px-4 sm:px-6">
          <div className="mb-8 max-w-2xl">
            <h2 className="font-display text-5xl tracking-wide text-ink">
              Before / after
            </h2>
            <p className="mt-3 text-lg text-ink/70">
              Line art in. Distinct flat regions out — skin, cloth, props, and
              backgrounds separated for easy selection.
            </p>
          </div>
          <BeforeAfterGallery />
        </div>
      </section>

      <section className="border-b-4 border-ink bg-paper py-16">
        <div className="mx-auto max-w-6xl px-4 sm:px-6">
          <h2 className="font-display text-5xl tracking-wide">Why ComicFlats</h2>
          <p className="mt-3 max-w-xl text-lg text-ink/70">
            Built for the flatting step colorists actually do — not fancy
            rendered shading.
          </p>
          <div className="mt-10 grid gap-5 md:grid-cols-3">
            {benefits.map((item) => (
              <div
                key={item.title}
                className="border-4 border-ink bg-white p-5 shadow-[6px_6px_0_0_#0a0a0a]"
              >
                <item.icon className="size-7 text-ink" />
                <h3 className="mt-4 font-display text-2xl tracking-wide">
                  {item.title}
                </h3>
                <p className="mt-2 text-sm leading-relaxed text-ink/70">
                  {item.body}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section id="pricing" className="border-b-4 border-ink bg-cyan py-16">
        <div className="mx-auto flex max-w-6xl flex-col items-start gap-8 px-4 lg:flex-row lg:items-center lg:justify-between lg:px-6">
          <div className="max-w-xl">
            <h2 className="font-display text-5xl tracking-wide text-ink sm:text-6xl">
              Simple pricing
            </h2>
            <p className="mt-3 text-lg text-ink/80">
              One-time payment. No subscription. Pay when you need flats.
            </p>
            <ul className="mt-6 space-y-3 text-ink">
              {[
                "$4.99 per successful paid job",
                "Up to 4 generation attempts",
                "Downloadable PNG ready for import",
                "Optional style notes & seed control",
              ].map((line) => (
                <li key={line} className="flex items-start gap-3 font-medium">
                  <Check className="mt-0.5 size-5 shrink-0" />
                  <span>{line}</span>
                </li>
              ))}
            </ul>
          </div>

          <div className="w-full max-w-md border-4 border-ink bg-paper p-6 shadow-[10px_10px_0_0_#0a0a0a]">
            <p className="font-mono text-xs uppercase tracking-[0.25em] text-ink/55">
              Single use
            </p>
            <p className="mt-2 font-display text-6xl tracking-wide text-ink">
              $4.99
            </p>
            <p className="mt-2 text-sm text-ink/70">
              Unlock the studio, upload your page, download flats. Secure
              checkout powered by Stripe.
            </p>
            <div className="mt-6">
              <CheckoutButton className="w-full" freeMode={freeMode}>
                {freeMode ? "Open studio — free" : "Start flatting — $4.99"}
              </CheckoutButton>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}

import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Terms of Service",
};

export default function TermsPage() {
  return (
    <article className="mx-auto max-w-3xl px-4 py-14 sm:px-6">
      <h1 className="font-display text-5xl tracking-wide">Terms of Service</h1>
      <p className="mt-2 text-sm text-ink/60">Last updated: July 17, 2026</p>

      <div className="mt-8 space-y-6 text-ink/85">
        <p>
          By purchasing or using ComicFlats, you agree to these terms.
        </p>
        <section className="space-y-2">
          <h2 className="font-display text-3xl tracking-wide">The service</h2>
          <p>
            ComicFlats provides automated flat-color generation from
            black-and-white comic line art. Output quality varies by input scan
            quality and model behavior. You receive up to four generation
            attempts per successful $4.99 payment.
          </p>
        </section>
        <section className="space-y-2">
          <h2 className="font-display text-3xl tracking-wide">Your content</h2>
          <p>
            You must own or have rights to upload the artwork. You retain
            ownership of your line art and resulting flats. You grant us a
            limited license to process and temporarily store files to deliver the
            service.
          </p>
        </section>
        <section className="space-y-2">
          <h2 className="font-display text-3xl tracking-wide">Payments & refunds</h2>
          <p>
            Payments are one-time and processed by Stripe. Refunds are handled
            manually for failed deliveries or clear service errors — contact
            support with your Stripe receipt. Abuse, chargebacks without cause,
            or prohibited content may result in denial of service.
          </p>
        </section>
        <section className="space-y-2">
          <h2 className="font-display text-3xl tracking-wide">Disclaimer</h2>
          <p>
            The service is provided “as is.” We do not guarantee perfect
            segmentation for every page. ComicFlats is a productivity aid, not a
            replacement for professional colorist judgment.
          </p>
        </section>
      </div>
    </article>
  );
}

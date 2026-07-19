import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Privacy Policy",
};

export default function PrivacyPage() {
  return (
    <article className="mx-auto max-w-3xl px-4 py-14 sm:px-6">
      <h1 className="font-display text-5xl tracking-wide">Privacy Policy</h1>
      <p className="mt-2 text-sm text-ink/60">Last updated: July 17, 2026</p>

      <div className="prose-comic mt-8 space-y-6 text-ink/85">
        <p>
          ComicFlats (“we”, “us”) provides a paid web tool that converts comic
          line art into flat-color images. This policy explains what we collect
          and how we use it.
        </p>
        <section className="space-y-2">
          <h2 className="font-display text-3xl tracking-wide">What we collect</h2>
          <ul className="list-disc space-y-1 pl-5">
            <li>Payment details processed by Stripe (we do not store card numbers).</li>
            <li>Uploaded images and generated outputs for your paid job.</li>
            <li>Optional style notes, region hints, and seeds you submit.</li>
            <li>Basic technical logs (IP, timestamps) for security and rate limiting.</li>
          </ul>
        </section>
        <section className="space-y-2">
          <h2 className="font-display text-3xl tracking-wide">How we use data</h2>
          <p>
            We use your uploads solely to fulfill your flatting job, deliver
            downloads, prevent abuse, and improve reliability. Images are stored
            temporarily with your job record and may expire after the job window
            ends.
          </p>
        </section>
        <section className="space-y-2">
          <h2 className="font-display text-3xl tracking-wide">Processors</h2>
          <p>
            We use Stripe for payments, Vercel for hosting/storage, a Postgres
            database for job records, and an AI image provider (e.g. Replicate)
            to generate flats. Each processor handles data under their own
            terms.
          </p>
        </section>
        <section className="space-y-2">
          <h2 className="font-display text-3xl tracking-wide">Contact</h2>
          <p>
            For privacy requests, email the address listed on your deployment or
            purchase receipt.
          </p>
        </section>
      </div>
    </article>
  );
}

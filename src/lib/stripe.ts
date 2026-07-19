import Stripe from "stripe";
import { PRICE_CENTS } from "@/lib/utils";

let _stripe: Stripe | null = null;

export function getStripe() {
  const key = process.env.STRIPE_SECRET_KEY;
  if (!key) {
    throw new Error("STRIPE_SECRET_KEY is not configured");
  }
  if (!_stripe) {
    _stripe = new Stripe(key, {
      apiVersion: "2026-06-24.dahlia",
      typescript: true,
    });
  }
  return _stripe;
}

export function getAppUrl() {
  return (
    process.env.NEXT_PUBLIC_APP_URL?.replace(/\/$/, "") ||
    "http://localhost:3000"
  );
}

export async function createCheckoutSession() {
  const stripe = getStripe();
  const appUrl = getAppUrl();

  return stripe.checkout.sessions.create({
    mode: "payment",
    payment_method_types: ["card"],
    line_items: [
      {
        quantity: 1,
        price_data: {
          currency: "usd",
          unit_amount: PRICE_CENTS,
          product_data: {
            name: "ComicFlats — Professional Flats Generation",
            description:
              "One comic page flatting job with up to 4 generation attempts. Download-ready PNG for Photoshop / Clip Studio.",
            images: [`${appUrl}/og.png`],
          },
        },
      },
    ],
    success_url: `${appUrl}/api/success?session_id={CHECKOUT_SESSION_ID}`,
    cancel_url: `${appUrl}/#pricing`,
    metadata: {
      product: "comicflats_job",
    },
  });
}

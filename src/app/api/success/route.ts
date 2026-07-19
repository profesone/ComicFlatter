import { NextResponse } from "next/server";
import { getStripe, getAppUrl } from "@/lib/stripe";
import { getJobBySessionId, markJobPaid } from "@/lib/jobs";
import { setJobCookie } from "@/lib/session";

export const runtime = "nodejs";

export async function GET(request: Request) {
  const { searchParams } = new URL(request.url);
  const sessionId = searchParams.get("session_id");
  const appUrl = getAppUrl();

  if (!sessionId) {
    return NextResponse.redirect(`${appUrl}/?error=missing_session`);
  }

  try {
    const stripe = getStripe();
    const session = await stripe.checkout.sessions.retrieve(sessionId);

    if (session.payment_status !== "paid") {
      return NextResponse.redirect(`${appUrl}/?error=payment_incomplete`);
    }

    let job = await getJobBySessionId(sessionId);
    if (!job || job.status === "pending_payment") {
      const paymentIntent =
        typeof session.payment_intent === "string"
          ? session.payment_intent
          : session.payment_intent?.id;
      job = await markJobPaid({
        stripeSessionId: sessionId,
        stripePaymentIntentId: paymentIntent ?? null,
      });
    }

    await setJobCookie(job.id);
    return NextResponse.redirect(`${appUrl}/studio`);
  } catch (error) {
    console.error("Success fulfillment error:", error);
    return NextResponse.redirect(`${appUrl}/?error=fulfillment_failed`);
  }
}

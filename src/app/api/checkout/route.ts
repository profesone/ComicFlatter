import { NextResponse } from "next/server";
import { isFreeMode } from "@/lib/flags";
import { createFreeJob, createPendingJob } from "@/lib/jobs";
import { clientIp, limitCheckout } from "@/lib/rate-limit";
import { setJobCookie } from "@/lib/session";
import { createCheckoutSession, getAppUrl } from "@/lib/stripe";

export const runtime = "nodejs";

export async function POST(request: Request) {
  try {
    const ip = clientIp(request);
    const limited = await limitCheckout(ip);
    if (!limited.success) {
      return NextResponse.json(
        { error: "Too many checkout attempts. Please wait a few minutes." },
        { status: 429 },
      );
    }

    if (isFreeMode()) {
      const job = await createFreeJob();
      await setJobCookie(job.id);
      return NextResponse.json({
        url: `${getAppUrl()}/studio`,
        freeMode: true,
        sessionId: job.stripeSessionId,
      });
    }

    const session = await createCheckoutSession();
    await createPendingJob(session.id);

    return NextResponse.json({
      url: session.url,
      freeMode: false,
      sessionId: session.id,
    });
  } catch (error) {
    console.error("Checkout error:", error);
    const message =
      error instanceof Error ? error.message : "Unable to start checkout";
    return NextResponse.json({ error: message }, { status: 500 });
  }
}

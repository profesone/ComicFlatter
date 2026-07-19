import { NextResponse } from "next/server";
import { getDb } from "@/lib/db";
import { jobs } from "@/lib/db/schema";
import { jobExpiresAt } from "@/lib/jobs";
import { setJobCookie } from "@/lib/session";
import { MAX_ATTEMPTS } from "@/lib/utils";

export const runtime = "nodejs";

/** Local/dev helper: unlock /studio without Stripe when DEV_UNLOCK_SECRET matches. */
export async function POST(request: Request) {
  if (process.env.NODE_ENV === "production") {
    return NextResponse.json({ error: "Not available" }, { status: 404 });
  }

  const expected = process.env.DEV_UNLOCK_SECRET;
  if (!expected) {
    return NextResponse.json(
      { error: "Set DEV_UNLOCK_SECRET in .env.local to use dev unlock" },
      { status: 400 },
    );
  }

  const body = (await request.json().catch(() => ({}))) as { secret?: string };
  if (body.secret !== expected) {
    return NextResponse.json({ error: "Invalid secret" }, { status: 401 });
  }

  try {
    const db = getDb();
    const [job] = await db
      .insert(jobs)
      .values({
        stripeSessionId: `dev_${Date.now()}`,
        status: "paid",
        maxAttempts: MAX_ATTEMPTS,
        expiresAt: jobExpiresAt(),
        outputUrls: [],
      })
      .returning();

    await setJobCookie(job.id);
    return NextResponse.json({ ok: true, jobId: job.id });
  } catch (error) {
    console.error("Dev unlock failed:", error);
    return NextResponse.json(
      {
        error:
          error instanceof Error
            ? error.message
            : "Dev unlock failed (is DATABASE_URL set?)",
      },
      { status: 500 },
    );
  }
}

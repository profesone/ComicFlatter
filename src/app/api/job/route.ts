import { NextResponse } from "next/server";
import { isFreeMode } from "@/lib/flags";
import {
  createFreeJob,
  getJobById,
  jobIsUsable,
  publicJob,
} from "@/lib/jobs";
import { getJobSession, setJobCookie } from "@/lib/session";

export const runtime = "nodejs";

export async function GET() {
  try {
    const session = await getJobSession();

    if (session) {
      const job = await getJobById(session.jobId);
      if (job && jobIsUsable(job)) {
        return NextResponse.json({
          job: publicJob(job),
          freeMode: isFreeMode(),
        });
      }
    }

    // FREE_MODE: auto-issue a free job so /studio works without paying.
    if (isFreeMode()) {
      const job = await createFreeJob();
      await setJobCookie(job.id);
      return NextResponse.json({
        job: publicJob(job),
        freeMode: true,
      });
    }

    return NextResponse.json(
      { error: "No active paid session", freeMode: false },
      { status: 401 },
    );
  } catch (error) {
    console.error("Job fetch error:", error);
    return NextResponse.json({ error: "Unable to load job" }, { status: 500 });
  }
}

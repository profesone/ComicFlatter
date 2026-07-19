import { NextResponse } from "next/server";
import { z } from "zod";
import { uploadImage } from "@/lib/blob";
import { generateFlats } from "@/lib/flatting";
import { validateLineArtUpload } from "@/lib/image-validate";
import {
  attemptsRemaining,
  getJobById,
  jobIsUsable,
  publicJob,
  updateJob,
} from "@/lib/jobs";
import { clientIp, limitProcess } from "@/lib/rate-limit";
import { getJobSession } from "@/lib/session";

export const runtime = "nodejs";
export const maxDuration = 300;

const fieldsSchema = z.object({
  styleNotes: z.string().max(500).optional().nullable(),
  regionHint: z.coerce.number().int().min(2).max(64).optional().nullable(),
  seed: z.coerce.number().int().min(0).max(2_147_483_647).optional().nullable(),
});

export async function POST(request: Request) {
  try {
    const ip = clientIp(request);
    const limited = await limitProcess(ip);
    if (!limited.success) {
      return NextResponse.json(
        { error: "Too many processing requests. Please wait a moment." },
        { status: 429 },
      );
    }

    const session = await getJobSession();
    if (!session) {
      return NextResponse.json({ error: "No active paid session" }, { status: 401 });
    }

    const job = await getJobById(session.jobId);
    if (!job || !jobIsUsable(job)) {
      return NextResponse.json(
        { error: "Job expired or unavailable. Please purchase again." },
        { status: 403 },
      );
    }

    if (attemptsRemaining(job) <= 0) {
      return NextResponse.json(
        {
          error:
            "All 4 attempts used for this payment. Purchase again for a new job.",
        },
        { status: 402 },
      );
    }

    const form = await request.formData();
    const file = form.get("file");
    const parsed = fieldsSchema.safeParse({
      styleNotes: form.get("styleNotes")?.toString() || null,
      regionHint: form.get("regionHint") || null,
      seed: form.get("seed") || null,
    });

    if (!parsed.success) {
      return NextResponse.json(
        { error: "Invalid form fields", details: parsed.error.flatten() },
        { status: 400 },
      );
    }

    let inputBuffer: Buffer;
    let inputUrl = job.inputUrl;
    let mime = "image/png";

    if (file instanceof File && file.size > 0) {
      const bytes = Buffer.from(await file.arrayBuffer());
      const validation = await validateLineArtUpload(bytes, file.type || "image/png");
      if (!validation.ok) {
        return NextResponse.json({ error: validation.error }, { status: 400 });
      }

      mime = validation.mime;
      inputBuffer = bytes;
      const uploaded = await uploadImage(
        `inputs/${job.id}`,
        bytes,
        mime,
      );
      inputUrl = uploaded.url;
    } else if (job.inputUrl) {
      const res = await fetch(job.inputUrl);
      if (!res.ok) {
        return NextResponse.json(
          { error: "Could not reload previous upload. Please upload again." },
          { status: 400 },
        );
      }
      inputBuffer = Buffer.from(await res.arrayBuffer());
      mime = res.headers.get("content-type") || "image/png";
    } else {
      return NextResponse.json(
        { error: "Please upload a line art image first." },
        { status: 400 },
      );
    }

    const seed =
      parsed.data.seed ??
      Math.floor(Math.random() * 1_000_000_000);

    await updateJob(job.id, {
      status: "processing",
      inputUrl,
      styleNotes: parsed.data.styleNotes ?? null,
      regionHint: parsed.data.regionHint ?? null,
      seed,
      errorMessage: null,
    });

    const { buffer, provider } = await generateFlats({
      inputBuffer,
      inputUrl: inputUrl ?? undefined,
      styleNotes: parsed.data.styleNotes,
      regionHint: parsed.data.regionHint,
      seed,
    });

    const output = await uploadImage(`outputs/${job.id}`, buffer, "image/png");
    const outputUrls = [...(job.outputUrls ?? []), output.url];

    const updated = await updateJob(job.id, {
      status: "completed",
      attemptsUsed: job.attemptsUsed + 1,
      inputUrl,
      outputUrls,
      styleNotes: parsed.data.styleNotes ?? null,
      regionHint: parsed.data.regionHint ?? null,
      seed,
      errorMessage: null,
    });

    return NextResponse.json({
      job: publicJob(updated!),
      provider,
    });
  } catch (error) {
    console.error("Process error:", error);

    try {
      const session = await getJobSession();
      if (session) {
        await updateJob(session.jobId, {
          status: "failed",
          errorMessage:
            error instanceof Error ? error.message : "Processing failed",
        });
      }
    } catch {
      // ignore secondary failure
    }

    return NextResponse.json(
      {
        error:
          error instanceof Error
            ? error.message
            : "Processing failed. Please try again.",
      },
      { status: 500 },
    );
  }
}

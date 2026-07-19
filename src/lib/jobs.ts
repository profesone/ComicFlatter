import { eq } from "drizzle-orm";
import { getDb } from "@/lib/db";
import { jobs, type Job, type JobStatus } from "@/lib/db/schema";
import { JOB_TTL_HOURS, MAX_ATTEMPTS } from "@/lib/utils";

export function jobExpiresAt(from = new Date()) {
  return new Date(from.getTime() + JOB_TTL_HOURS * 60 * 60 * 1000);
}

export async function createPendingJob(stripeSessionId: string) {
  const db = getDb();
  const [row] = await db
    .insert(jobs)
    .values({
      stripeSessionId,
      status: "pending_payment",
      maxAttempts: MAX_ATTEMPTS,
      expiresAt: jobExpiresAt(),
      outputUrls: [],
    })
    .returning();
  return row;
}

/** Testing / FREE_MODE: create an immediately usable job with no payment. */
export async function createFreeJob() {
  const db = getDb();
  const [row] = await db
    .insert(jobs)
    .values({
      stripeSessionId: `free_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
      status: "paid",
      maxAttempts: MAX_ATTEMPTS,
      expiresAt: jobExpiresAt(),
      outputUrls: [],
    })
    .returning();
  return row;
}

export async function getJobById(id: string) {
  const db = getDb();
  const [row] = await db.select().from(jobs).where(eq(jobs.id, id)).limit(1);
  return row ?? null;
}

export async function getJobBySessionId(sessionId: string) {
  const db = getDb();
  const [row] = await db
    .select()
    .from(jobs)
    .where(eq(jobs.stripeSessionId, sessionId))
    .limit(1);
  return row ?? null;
}

export async function markJobPaid(options: {
  stripeSessionId: string;
  stripePaymentIntentId?: string | null;
}) {
  const db = getDb();
  const existing = await getJobBySessionId(options.stripeSessionId);

  if (existing) {
    const [row] = await db
      .update(jobs)
      .set({
        status: "paid",
        stripePaymentIntentId: options.stripePaymentIntentId ?? existing.stripePaymentIntentId,
        expiresAt: jobExpiresAt(),
        updatedAt: new Date(),
        errorMessage: null,
      })
      .where(eq(jobs.id, existing.id))
      .returning();
    return row;
  }

  const [row] = await db
    .insert(jobs)
    .values({
      stripeSessionId: options.stripeSessionId,
      stripePaymentIntentId: options.stripePaymentIntentId ?? null,
      status: "paid",
      maxAttempts: MAX_ATTEMPTS,
      expiresAt: jobExpiresAt(),
      outputUrls: [],
    })
    .returning();
  return row;
}

export async function updateJob(
  id: string,
  patch: Partial<{
    status: JobStatus;
    attemptsUsed: number;
    inputUrl: string | null;
    outputUrls: string[];
    styleNotes: string | null;
    regionHint: number | null;
    seed: number | null;
    errorMessage: string | null;
  }>,
) {
  const db = getDb();
  const [row] = await db
    .update(jobs)
    .set({ ...patch, updatedAt: new Date() })
    .where(eq(jobs.id, id))
    .returning();
  return row ?? null;
}

export function jobIsUsable(job: Job) {
  if (job.expiresAt.getTime() < Date.now()) return false;
  return (
    job.status === "paid" ||
    job.status === "processing" ||
    job.status === "completed" ||
    job.status === "failed"
  );
}

export function attemptsRemaining(job: Job) {
  return Math.max(0, job.maxAttempts - job.attemptsUsed);
}

export function publicJob(job: Job) {
  return {
    id: job.id,
    status: job.status,
    attemptsUsed: job.attemptsUsed,
    maxAttempts: job.maxAttempts,
    attemptsRemaining: attemptsRemaining(job),
    inputUrl: job.inputUrl,
    outputUrls: job.outputUrls ?? [],
    latestOutputUrl: (job.outputUrls ?? []).at(-1) ?? null,
    styleNotes: job.styleNotes,
    regionHint: job.regionHint,
    seed: job.seed,
    errorMessage: job.errorMessage,
    expiresAt: job.expiresAt.toISOString(),
  };
}

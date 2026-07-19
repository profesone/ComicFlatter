import { SignJWT, jwtVerify } from "jose";
import { cookies } from "next/headers";
import { JOB_COOKIE, JOB_TTL_HOURS } from "@/lib/utils";

export type JobSessionPayload = {
  jobId: string;
};

function getSecret() {
  const secret = process.env.SESSION_SECRET;
  if (!secret || secret.length < 32) {
    throw new Error("SESSION_SECRET must be at least 32 characters");
  }
  return new TextEncoder().encode(secret);
}

export async function createJobToken(jobId: string) {
  return new SignJWT({ jobId } satisfies JobSessionPayload)
    .setProtectedHeader({ alg: "HS256" })
    .setIssuedAt()
    .setExpirationTime(`${JOB_TTL_HOURS}h`)
    .sign(getSecret());
}

export async function verifyJobToken(token: string) {
  const { payload } = await jwtVerify(token, getSecret());
  const jobId = payload.jobId;
  if (typeof jobId !== "string") return null;
  return { jobId };
}

export async function setJobCookie(jobId: string) {
  const token = await createJobToken(jobId);
  const jar = await cookies();
  jar.set(JOB_COOKIE, token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge: JOB_TTL_HOURS * 60 * 60,
  });
}

export async function clearJobCookie() {
  const jar = await cookies();
  jar.delete(JOB_COOKIE);
}

export async function getJobSession() {
  const jar = await cookies();
  const token = jar.get(JOB_COOKIE)?.value;
  if (!token) return null;
  try {
    return await verifyJobToken(token);
  } catch {
    return null;
  }
}

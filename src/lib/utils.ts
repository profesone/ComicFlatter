import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export const PRICE_CENTS = 499;
export const MAX_ATTEMPTS = 4;
export const MAX_UPLOAD_BYTES = 20 * 1024 * 1024;
export const ALLOWED_MIME_TYPES = [
  "image/png",
  "image/jpeg",
  "image/jpg",
  "image/tiff",
  "image/webp",
] as const;
export const JOB_COOKIE = "comicflats_job";
export const JOB_TTL_HOURS = 48;

import sharp, { type Metadata, type Stats } from "sharp";
import { ALLOWED_MIME_TYPES, MAX_UPLOAD_BYTES } from "@/lib/utils";

export type ValidationResult =
  | { ok: true; width: number; height: number; mime: string }
  | { ok: false; error: string };

function normalizeMime(mime: string) {
  if (mime === "image/jpg") return "image/jpeg";
  return mime;
}

export async function validateLineArtUpload(
  buffer: Buffer,
  declaredMime: string,
): Promise<ValidationResult> {
  if (buffer.byteLength > MAX_UPLOAD_BYTES) {
    return {
      ok: false,
      error: `File too large. Max size is ${Math.round(MAX_UPLOAD_BYTES / (1024 * 1024))}MB.`,
    };
  }

  const mime = normalizeMime(declaredMime);
  if (!ALLOWED_MIME_TYPES.includes(mime as (typeof ALLOWED_MIME_TYPES)[number])) {
    return {
      ok: false,
      error: "Unsupported file type. Use PNG, JPG, TIFF, or WEBP.",
    };
  }

  let meta: Metadata;
  let stats: Stats;
  try {
    const image = sharp(buffer, { failOn: "none" }).rotate();
    meta = await image.metadata();
    stats = await image.stats();
  } catch {
    return { ok: false, error: "Could not read this image. Try another file." };
  }

  if (!meta.width || !meta.height) {
    return { ok: false, error: "Invalid image dimensions." };
  }

  if (meta.width < 256 || meta.height < 256) {
    return {
      ok: false,
      error: "Image too small. Please upload at least 256×256 pixels.",
    };
  }

  if (meta.width > 8000 || meta.height > 8000) {
    return {
      ok: false,
      error: "Image too large. Please keep each side under 8000px.",
    };
  }

  // Prefer mostly B&W / low-chroma scans (line art).
  const channels = stats.channels;
  if (channels.length >= 3) {
    const [r, g, b] = channels;
    const meanDiff =
      (Math.abs(r.mean - g.mean) +
        Math.abs(g.mean - b.mean) +
        Math.abs(r.mean - b.mean)) /
      3;
    const stdAvg = (r.stdev + g.stdev + b.stdev) / 3;

    if (meanDiff > 28 && stdAvg > 40) {
      return {
        ok: false,
        error:
          "This looks like a color image. Upload black-and-white comic line art for best results.",
      };
    }
  }

  const brightness =
    channels.reduce((sum, c) => sum + c.mean, 0) / channels.length;
  if (brightness < 18) {
    return {
      ok: false,
      error: "Image looks too dark. Try a cleaner, higher-contrast scan.",
    };
  }

  return {
    ok: true,
    width: meta.width,
    height: meta.height,
    mime: meta.format === "jpeg" ? "image/jpeg" : mime,
  };
}

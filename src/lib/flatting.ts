import sharp from "sharp";
import Replicate from "replicate";

const DEFAULT_MODEL =
  process.env.REPLICATE_MODEL || "black-forest-labs/flux-kontext-pro";

const FLATTING_PROMPT = `Professional comic book FLATS / flat-color segmentation for a colorist.

Transform this black-and-white comic line art into CLEAN FLATS:
- Every distinct visual region (skin, hair, gloves, sleeves, shirt, pants, helmet, weapons, background shapes, etc.) gets its own solid flat color
- NO gradients, NO shading, NO soft shadows, NO textures, NO hatching fill
- Colors are solid, posterized, non-overlapping regions that meet cleanly under the black ink lines (professional trapping / spreading style)
- Preserve the original black line art on top — lines stay crisp and black
- Use clearly different hues so adjacent regions are easy to select in Photoshop
- Keep the same composition, proportions, and panel layout
- Output a clean PNG suitable for professional comic coloring workflows`;

function getReplicate() {
  const token = process.env.REPLICATE_API_TOKEN;
  if (!token) return null;
  return new Replicate({ auth: token });
}

async function fetchAsBuffer(url: string) {
  const res = await fetch(url);
  if (!res.ok) {
    throw new Error(`Failed to download generated image (${res.status})`);
  }
  return Buffer.from(await res.arrayBuffer());
}

function extractOutputUrl(output: unknown): string | null {
  if (typeof output === "string") return output;
  if (output && typeof output === "object" && "url" in output) {
    const url = (output as { url: unknown }).url;
    if (typeof url === "string") return url;
    if (typeof url === "function") {
      const value = (url as () => unknown)();
      if (typeof value === "string") return value;
    }
  }
  if (Array.isArray(output) && output.length > 0) {
    return extractOutputUrl(output[0]);
  }
  return null;
}

/** Local fallback: posterize / palette-quantize for demo without Replicate. */
export async function localFlatFallback(
  input: Buffer,
  options?: { seed?: number },
): Promise<Buffer> {
  const seed = options?.seed ?? 42;
  const colors = 12 + (seed % 8);

  const flattened = await sharp(input)
    .rotate()
    .ensureAlpha()
    .png()
    .toBuffer();

  // Quantize toward hard flats, then lightly boost contrast.
  return sharp(flattened)
    .modulate({ saturation: 1.35, brightness: 1.02 })
    .png({ palette: true, colors, dither: 0 })
    .toBuffer()
    .then(async (quantized) => {
      // Re-expand to full PNG (non-palette) for Photoshop-friendly output
      return sharp(quantized).png().toBuffer();
    });
}

async function postProcessFlats(inputLines: Buffer, aiOutput: Buffer) {
  // Composite original lines back on top for crisp ink.
  const linesMeta = await sharp(inputLines).rotate().metadata();
  const width = linesMeta.width ?? 1024;
  const height = linesMeta.height ?? 1024;

  const flats = await sharp(aiOutput)
    .resize(width, height, { fit: "fill" })
    .removeAlpha()
    .png()
    .toBuffer();

  // Extract dark line mask from original and multiply over flats.
  const lineMask = await sharp(inputLines)
    .rotate()
    .resize(width, height, { fit: "fill" })
    .greyscale()
    .normalize()
    .threshold(40)
    .negate()
    .png()
    .toBuffer();

  const blackLines = await sharp({
    create: {
      width,
      height,
      channels: 4,
      background: { r: 0, g: 0, b: 0, alpha: 1 },
    },
  })
    .png()
    .toBuffer();

  const linedOverlay = await sharp(blackLines)
    .composite([{ input: lineMask, blend: "dest-in" }])
    .png()
    .toBuffer();

  return sharp(flats)
    .ensureAlpha()
    .composite([{ input: linedOverlay, blend: "over" }])
    .png()
    .toBuffer();
}

export async function generateFlats(options: {
  inputBuffer: Buffer;
  inputUrl?: string;
  styleNotes?: string | null;
  seed?: number | null;
  regionHint?: number | null;
}): Promise<{ buffer: Buffer; provider: "replicate" | "local" }> {
  const promptParts = [FLATTING_PROMPT];
  if (options.styleNotes?.trim()) {
    promptParts.push(`Style notes from colorist: ${options.styleNotes.trim()}`);
  }
  if (options.regionHint) {
    promptParts.push(
      `Aim for roughly ${options.regionHint} distinct flat color regions.`,
    );
  }
  const prompt = promptParts.join("\n");

  const replicate = getReplicate();
  if (!replicate) {
    const buffer = await localFlatFallback(options.inputBuffer, {
      seed: options.seed ?? undefined,
    });
    return { buffer, provider: "local" };
  }

  const imageInput = options.inputUrl
    ? options.inputUrl
    : `data:image/png;base64,${options.inputBuffer.toString("base64")}`;

  try {
    const output = await replicate.run(DEFAULT_MODEL as `${string}/${string}`, {
      input: {
        prompt,
        input_image: imageInput,
        image: imageInput,
        output_format: "png",
        aspect_ratio: "match_input_image",
        safety_tolerance: 2,
        seed: options.seed ?? undefined,
      },
    });

    const url = extractOutputUrl(output);
    if (!url) {
      throw new Error("Replicate returned no image URL");
    }

    const aiBuffer = await fetchAsBuffer(url);
    const buffer = await postProcessFlats(options.inputBuffer, aiBuffer);
    return { buffer, provider: "replicate" };
  } catch (error) {
    // Graceful degradation so the paid flow still delivers something usable.
    console.error("Replicate flatting failed, using local fallback:", error);
    const buffer = await localFlatFallback(options.inputBuffer, {
      seed: options.seed ?? undefined,
    });
    return { buffer, provider: "local" };
  }
}

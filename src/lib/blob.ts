import { put, del } from "@vercel/blob";

export async function uploadImage(
  pathname: string,
  data: Buffer | Blob,
  contentType: string,
) {
  const token = process.env.BLOB_READ_WRITE_TOKEN;
  if (!token) {
    throw new Error("BLOB_READ_WRITE_TOKEN is not configured");
  }

  const blob = await put(pathname, data, {
    access: "public",
    contentType,
    token,
    addRandomSuffix: true,
  });

  return blob;
}

export async function deleteBlob(url: string) {
  const token = process.env.BLOB_READ_WRITE_TOKEN;
  if (!token) return;
  try {
    await del(url, { token });
  } catch {
    // Best-effort cleanup
  }
}

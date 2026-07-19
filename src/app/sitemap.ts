import type { MetadataRoute } from "next";
import { getAppUrl } from "@/lib/stripe";

export default function sitemap(): MetadataRoute.Sitemap {
  const base = getAppUrl();
  return [
    { url: base, changeFrequency: "weekly", priority: 1 },
    { url: `${base}/privacy`, changeFrequency: "yearly", priority: 0.3 },
    { url: `${base}/terms`, changeFrequency: "yearly", priority: 0.3 },
  ];
}

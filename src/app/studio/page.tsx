import type { Metadata } from "next";
import { StudioClient } from "@/components/studio-client";
import { isFreeMode } from "@/lib/flags";

export const metadata: Metadata = {
  title: "Studio",
  description: "Upload comic line art and generate professional flats.",
  robots: { index: false, follow: false },
};

export default function StudioPage() {
  return (
    <div className="bg-halftone">
      <StudioClient freeMode={isFreeMode()} />
    </div>
  );
}

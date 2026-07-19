"use client";

import { useState } from "react";
import { cn } from "@/lib/utils";

const demos = [
  {
    id: "hero",
    title: "Hero portrait",
    before: "/demos/lineart-hero.svg",
    after: "/demos/flats-hero.svg",
  },
  {
    id: "action",
    title: "Action panel",
    before: "/demos/lineart-action.svg",
    after: "/demos/flats-action.svg",
  },
  {
    id: "city",
    title: "City background",
    before: "/demos/lineart-city.svg",
    after: "/demos/flats-city.svg",
  },
] as const;

export function BeforeAfterGallery() {
  const [active, setActive] = useState(0);
  const demo = demos[active];

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap gap-2">
        {demos.map((item, index) => (
          <button
            key={item.id}
            type="button"
            onClick={() => setActive(index)}
            className={cn(
              "border-2 border-ink px-4 py-2 font-display text-lg tracking-wide transition",
              active === index
                ? "bg-cyan shadow-[3px_3px_0_0_#0a0a0a]"
                : "bg-paper hover:bg-yellow/60",
            )}
          >
            {item.title}
          </button>
        ))}
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <figure className="border-4 border-ink bg-white shadow-[8px_8px_0_0_#0a0a0a]">
          <div className="border-b-4 border-ink bg-ink px-3 py-2 font-mono text-xs uppercase tracking-[0.25em] text-paper">
            Before · line art
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={demo.before}
            alt={`${demo.title} line art`}
            className="aspect-[4/5] w-full object-cover"
          />
        </figure>
        <figure className="border-4 border-ink bg-white shadow-[8px_8px_0_0_#0a0a0a]">
          <div className="border-b-4 border-ink bg-cyan px-3 py-2 font-mono text-xs uppercase tracking-[0.25em] text-ink">
            After · flats
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={demo.after}
            alt={`${demo.title} flats`}
            className="aspect-[4/5] w-full object-cover"
          />
        </figure>
      </div>
    </div>
  );
}

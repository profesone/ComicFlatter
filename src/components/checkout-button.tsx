"use client";

import { useState } from "react";
import { Loader2 } from "lucide-react";
import { cn } from "@/lib/utils";

type Props = {
  className?: string;
  children?: React.ReactNode;
  freeMode?: boolean;
};

export function CheckoutButton({ className, children, freeMode = false }: Props) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function startCheckout() {
    setLoading(true);
    setError(null);
    try {
      const res = await fetch("/api/checkout", { method: "POST" });
      const data = (await res.json()) as {
        url?: string;
        error?: string;
        freeMode?: boolean;
      };
      if (!res.ok || !data.url) {
        throw new Error(data.error || "Could not start checkout");
      }
      window.location.href = data.url;
    } catch (err) {
      setError(err instanceof Error ? err.message : "Checkout failed");
      setLoading(false);
    }
  }

  const label =
    children ?? (freeMode ? "Try free — no payment" : "Get flats — $4.99");

  return (
    <div className="flex flex-col items-start gap-2">
      <button
        type="button"
        onClick={startCheckout}
        disabled={loading}
        className={cn(
          "inline-flex items-center justify-center gap-2 bg-yellow px-6 py-3 font-display text-2xl tracking-wide text-ink shadow-[6px_6px_0_0_#0a0a0a] transition hover:-translate-y-0.5 hover:shadow-[8px_8px_0_0_#0a0a0a] disabled:cursor-not-allowed disabled:opacity-70",
          className,
        )}
      >
        {loading ? <Loader2 className="size-5 animate-spin" /> : null}
        {label}
      </button>
      {freeMode ? (
        <p className="font-mono text-[11px] uppercase tracking-widest text-ink/50">
          FREE_MODE on · Stripe skipped
        </p>
      ) : null}
      {error ? (
        <p className="max-w-sm text-sm font-medium text-red-700">{error}</p>
      ) : null}
    </div>
  );
}

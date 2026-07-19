"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import {
  Download,
  Loader2,
  RefreshCw,
  Upload,
  AlertCircle,
  ImageIcon,
} from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Progress } from "@/components/ui/progress";
import { CheckoutButton } from "@/components/checkout-button";
import { cn } from "@/lib/utils";

type PublicJob = {
  id: string;
  status: string;
  attemptsUsed: number;
  maxAttempts: number;
  attemptsRemaining: number;
  inputUrl: string | null;
  outputUrls: string[];
  latestOutputUrl: string | null;
  styleNotes: string | null;
  regionHint: number | null;
  seed: number | null;
  errorMessage: string | null;
  expiresAt: string;
};

type Props = {
  freeMode?: boolean;
};

export function StudioClient({ freeMode = false }: Props) {
  const [job, setJob] = useState<PublicJob | null>(null);
  const [loadingJob, setLoadingJob] = useState(true);
  const [authError, setAuthError] = useState<string | null>(null);
  const [file, setFile] = useState<File | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const [styleNotes, setStyleNotes] = useState("");
  const [regionHint, setRegionHint] = useState("");
  const [seed, setSeed] = useState("");
  const [processing, setProcessing] = useState(false);
  const [progress, setProgress] = useState(0);
  const [error, setError] = useState<string | null>(null);
  const [dragOver, setDragOver] = useState(false);

  const loadJob = useCallback(async () => {
    setLoadingJob(true);
    setAuthError(null);
    try {
      const res = await fetch("/api/job");
      const data = (await res.json()) as { job?: PublicJob; error?: string };
      if (!res.ok || !data.job) {
        setAuthError(data.error || "No active paid session");
        setJob(null);
        return;
      }
      setJob(data.job);
      setStyleNotes(data.job.styleNotes ?? "");
      setRegionHint(data.job.regionHint?.toString() ?? "");
      setSeed(data.job.seed?.toString() ?? "");
      if (data.job.inputUrl) setPreviewUrl(data.job.inputUrl);
    } catch {
      setAuthError("Unable to load your job session.");
    } finally {
      setLoadingJob(false);
    }
  }, []);

  useEffect(() => {
    void loadJob();
  }, [loadJob]);

  useEffect(() => {
    if (!file) return;
    const url = URL.createObjectURL(file);
    setPreviewUrl(url);
    return () => URL.revokeObjectURL(url);
  }, [file]);

  useEffect(() => {
    if (!processing) {
      setProgress(0);
      return;
    }
    setProgress(8);
    const id = window.setInterval(() => {
      setProgress((p) => (p >= 92 ? p : p + Math.random() * 6));
    }, 800);
    return () => window.clearInterval(id);
  }, [processing]);

  const canProcess = useMemo(() => {
    if (!job) return false;
    if (job.attemptsRemaining <= 0) return false;
    return Boolean(file || job.inputUrl);
  }, [job, file]);

  function onFiles(files: FileList | null) {
    const next = files?.[0];
    if (!next) return;
    setFile(next);
    setError(null);
  }

  async function processFlats(regenerate = false) {
    if (!job) return;
    setProcessing(true);
    setError(null);
    try {
      const form = new FormData();
      if (file && !regenerate) {
        form.append("file", file);
      } else if (file) {
        form.append("file", file);
      }
      if (styleNotes.trim()) form.append("styleNotes", styleNotes.trim());
      if (regionHint) form.append("regionHint", regionHint);
      if (seed) form.append("seed", seed);
      else if (regenerate) {
        form.append("seed", String(Math.floor(Math.random() * 1_000_000_000)));
      }

      const res = await fetch("/api/process", { method: "POST", body: form });
      const data = (await res.json()) as {
        job?: PublicJob;
        error?: string;
      };
      if (!res.ok || !data.job) {
        throw new Error(data.error || "Processing failed");
      }
      setJob(data.job);
      setSeed(data.job.seed?.toString() ?? "");
      setProgress(100);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Processing failed");
    } finally {
      setProcessing(false);
    }
  }

  if (loadingJob) {
    return (
      <div className="flex min-h-[50vh] items-center justify-center gap-3 text-ink/70">
        <Loader2 className="size-5 animate-spin" />
        {freeMode ? "Opening free studio…" : "Loading your paid session…"}
      </div>
    );
  }

  if (authError || !job) {
    return (
      <div className="mx-auto max-w-xl space-y-6 px-4 py-16 text-center">
        <Alert variant="destructive" className="border-4 border-ink text-left">
          <AlertCircle />
          <AlertTitle>
            {freeMode ? "Studio unavailable" : "Payment required"}
          </AlertTitle>
          <AlertDescription>
            {authError ||
              (freeMode
                ? "Could not start a free test session. Check DATABASE_URL and SESSION_SECRET."
                : "Buy a $4.99 flatting job to unlock the upload studio.")}
          </AlertDescription>
        </Alert>
        <div className="flex justify-center">
          <CheckoutButton freeMode={freeMode}>
            {freeMode ? "Retry free unlock" : "Unlock studio — $4.99"}
          </CheckoutButton>
        </div>
      </div>
    );
  }

  return (
    <div className="mx-auto grid max-w-6xl gap-8 px-4 py-10 lg:grid-cols-[1.1fr_0.9fr] lg:px-6">
      <section className="space-y-6">
        <div className="border-4 border-ink bg-paper p-5 shadow-[8px_8px_0_0_#0a0a0a]">
          <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 className="font-display text-4xl tracking-wide text-ink">
              Studio
            </h1>
            <p className="font-mono text-xs uppercase tracking-widest text-ink/60">
              {job.attemptsRemaining} / {job.maxAttempts} attempts left
            </p>
          </div>

          <div
            onDragOver={(e) => {
              e.preventDefault();
              setDragOver(true);
            }}
            onDragLeave={() => setDragOver(false)}
            onDrop={(e) => {
              e.preventDefault();
              setDragOver(false);
              onFiles(e.dataTransfer.files);
            }}
            className={cn(
              "relative flex min-h-56 cursor-pointer flex-col items-center justify-center border-4 border-dashed border-ink/40 bg-halftone px-4 py-10 text-center transition",
              dragOver && "border-cyan bg-cyan/10",
            )}
            onClick={() => document.getElementById("lineart-input")?.click()}
          >
            <input
              id="lineart-input"
              type="file"
              accept="image/png,image/jpeg,image/tiff,image/webp,.tif,.tiff"
              className="hidden"
              onChange={(e) => onFiles(e.target.files)}
            />
            {previewUrl ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img
                src={previewUrl}
                alt="Upload preview"
                className="max-h-72 w-auto border-2 border-ink bg-white object-contain"
              />
            ) : (
              <>
                <Upload className="mb-3 size-8 text-ink" />
                <p className="font-display text-2xl tracking-wide">
                  Drop B&W line art
                </p>
                <p className="mt-2 max-w-sm text-sm text-ink/65">
                  PNG, JPG, TIFF, or WEBP · max 20MB · mostly black-and-white
                  scans work best
                </p>
              </>
            )}
          </div>

          <div className="mt-5 grid gap-4 sm:grid-cols-2">
            <div className="sm:col-span-2 space-y-2">
              <Label htmlFor="styleNotes">Style notes (optional)</Label>
              <Textarea
                id="styleNotes"
                value={styleNotes}
                onChange={(e) => setStyleNotes(e.target.value)}
                placeholder='e.g. "Marvel style, clean flats, avoid muddy browns"'
                rows={3}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="regionHint">Region hint</Label>
              <Input
                id="regionHint"
                type="number"
                min={2}
                max={64}
                value={regionHint}
                onChange={(e) => setRegionHint(e.target.value)}
                placeholder="e.g. 18"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="seed">Seed</Label>
              <Input
                id="seed"
                type="number"
                min={0}
                value={seed}
                onChange={(e) => setSeed(e.target.value)}
                placeholder="Random if empty"
              />
            </div>
          </div>

          <div className="mt-6 flex flex-wrap gap-3">
            <Button
              type="button"
              disabled={!canProcess || processing}
              onClick={() => void processFlats(false)}
              className="h-12 bg-cyan px-5 font-display text-xl tracking-wide text-ink shadow-[4px_4px_0_0_#0a0a0a] hover:bg-cyan/90"
            >
              {processing ? (
                <Loader2 className="size-4 animate-spin" />
              ) : (
                <ImageIcon className="size-4" />
              )}
              Generate flats
            </Button>
            {job.latestOutputUrl ? (
              <Button
                type="button"
                variant="outline"
                disabled={!canProcess || processing || job.attemptsRemaining <= 0}
                onClick={() => void processFlats(true)}
                className="h-12 border-4 border-ink font-display text-xl tracking-wide"
              >
                <RefreshCw className="size-4" />
                Regenerate
              </Button>
            ) : null}
          </div>

          {processing ? (
            <div className="mt-5 space-y-2">
              <Progress value={progress} className="h-3 border-2 border-ink" />
              <p className="font-mono text-xs uppercase tracking-widest text-ink/60">
                Flatting in progress · usually 15–60 seconds
              </p>
            </div>
          ) : null}

          {error ? (
            <Alert variant="destructive" className="mt-5 border-4 border-ink">
              <AlertCircle />
              <AlertTitle>Couldn’t process</AlertTitle>
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          ) : null}
        </div>
      </section>

      <section className="space-y-6">
        <div className="border-4 border-ink bg-paper p-5 shadow-[8px_8px_0_0_#0a0a0a]">
          <h2 className="font-display text-3xl tracking-wide">Result</h2>
          <p className="mt-1 text-sm text-ink/65">
            Download PNG flats for Photoshop / Clip Studio. Lines are composited
            on top for crisp selection.
          </p>

          {job.latestOutputUrl ? (
            <div className="mt-5 space-y-4">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src={job.latestOutputUrl}
                alt="Generated flats"
                className="w-full border-4 border-ink bg-[url('/halftone.svg')] object-contain"
              />
              <a
                href={job.latestOutputUrl}
                download={`comicflats-${job.id.slice(0, 8)}.png`}
                target="_blank"
                rel="noreferrer"
                className="inline-flex items-center gap-2 bg-yellow px-4 py-3 font-display text-xl tracking-wide text-ink shadow-[4px_4px_0_0_#0a0a0a] transition hover:-translate-y-0.5"
              >
                <Download className="size-4" />
                Download PNG
              </a>
            </div>
          ) : (
            <div className="mt-6 flex min-h-64 items-center justify-center border-4 border-dashed border-ink/30 bg-halftone text-sm text-ink/55">
              Your flats preview will show up here.
            </div>
          )}
        </div>

        {job.outputUrls.length > 1 ? (
          <div className="border-4 border-ink bg-paper p-5">
            <h3 className="font-display text-2xl tracking-wide">
              Previous attempts
            </h3>
            <div className="mt-4 grid grid-cols-2 gap-3">
              {job.outputUrls.slice(0, -1).map((url, i) => (
                <a key={url} href={url} target="_blank" rel="noreferrer">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src={url}
                    alt={`Attempt ${i + 1}`}
                    className="aspect-square w-full border-2 border-ink object-cover"
                  />
                </a>
              ))}
            </div>
          </div>
        ) : null}
      </section>
    </div>
  );
}

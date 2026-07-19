CREATE TABLE IF NOT EXISTS "jobs" (
  "id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
  "stripe_session_id" text UNIQUE,
  "stripe_payment_intent_id" text,
  "status" text DEFAULT 'pending_payment' NOT NULL,
  "attempts_used" integer DEFAULT 0 NOT NULL,
  "max_attempts" integer DEFAULT 4 NOT NULL,
  "input_url" text,
  "output_urls" jsonb DEFAULT '[]'::jsonb NOT NULL,
  "style_notes" text,
  "region_hint" integer,
  "seed" integer,
  "error_message" text,
  "expires_at" timestamp with time zone NOT NULL,
  "created_at" timestamp with time zone DEFAULT now() NOT NULL,
  "updated_at" timestamp with time zone DEFAULT now() NOT NULL
);

CREATE INDEX IF NOT EXISTS "jobs_stripe_session_id_idx" ON "jobs" ("stripe_session_id");
CREATE INDEX IF NOT EXISTS "jobs_status_idx" ON "jobs" ("status");

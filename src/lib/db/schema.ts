import { sql } from "drizzle-orm";
import {
  integer,
  jsonb,
  pgTable,
  text,
  timestamp,
  uuid,
} from "drizzle-orm/pg-core";

export type JobStatus =
  | "pending_payment"
  | "paid"
  | "processing"
  | "completed"
  | "failed"
  | "expired";

export const jobs = pgTable("jobs", {
  id: uuid("id").defaultRandom().primaryKey(),
  stripeSessionId: text("stripe_session_id").unique(),
  stripePaymentIntentId: text("stripe_payment_intent_id"),
  status: text("status").$type<JobStatus>().notNull().default("pending_payment"),
  attemptsUsed: integer("attempts_used").notNull().default(0),
  maxAttempts: integer("max_attempts").notNull().default(4),
  inputUrl: text("input_url"),
  outputUrls: jsonb("output_urls")
    .$type<string[]>()
    .notNull()
    .default(sql`'[]'::jsonb`),
  styleNotes: text("style_notes"),
  regionHint: integer("region_hint"),
  seed: integer("seed"),
  errorMessage: text("error_message"),
  expiresAt: timestamp("expires_at", { withTimezone: true }).notNull(),
  createdAt: timestamp("created_at", { withTimezone: true }).notNull().defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).notNull().defaultNow(),
});

export type Job = typeof jobs.$inferSelect;
export type NewJob = typeof jobs.$inferInsert;

/**
 * FREE_MODE=true → testing: unlock studio without Stripe.
 * FREE_MODE=false (or unset) → paying mode: Checkout required.
 *
 * Set NEXT_PUBLIC_FREE_MODE to the same value for client UI labels.
 */
export function isFreeMode() {
  const value = process.env.FREE_MODE ?? process.env.NEXT_PUBLIC_FREE_MODE;
  return value === "true" || value === "1";
}

export function isFreeModePublic() {
  return (
    process.env.NEXT_PUBLIC_FREE_MODE === "true" ||
    process.env.NEXT_PUBLIC_FREE_MODE === "1"
  );
}

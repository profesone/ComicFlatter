import type { Metadata } from "next";
import { Bangers, DM_Sans, Geist_Mono } from "next/font/google";
import { SiteFooter } from "@/components/site-footer";
import { SiteHeader } from "@/components/site-header";
import "./globals.css";

const display = Bangers({
  variable: "--font-display",
  subsets: ["latin"],
  weight: "400",
});

const body = DM_Sans({
  variable: "--font-body",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

const appUrl = process.env.NEXT_PUBLIC_APP_URL || "http://localhost:3000";

export const metadata: Metadata = {
  metadataBase: new URL(appUrl),
  title: {
    default: "ComicFlats — Professional comic flatting in seconds",
    template: "%s · ComicFlats",
  },
  description:
    "Upload B&W comic line art and get professional flats in seconds. Perfect for colorists using Photoshop, Clip Studio, and more. $4.99 per job, up to 4 attempts.",
  openGraph: {
    title: "ComicFlats — Professional comic flatting in seconds",
    description:
      "Upload B&W comic line art → get clean solid-color flats ready for Photoshop & Clip Studio.",
    url: appUrl,
    siteName: "ComicFlats",
    type: "website",
    images: [{ url: "/og.png", width: 1200, height: 630, alt: "ComicFlats" }],
  },
  twitter: {
    card: "summary_large_image",
    title: "ComicFlats",
    description:
      "Upload B&W comic line art → get professional flats in seconds.",
    images: ["/og.png"],
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="en"
      className={`${display.variable} ${body.variable} ${geistMono.variable} h-full antialiased`}
    >
      <body className="flex min-h-full flex-col bg-paper text-ink">
        <SiteHeader />
        <main className="flex-1">{children}</main>
        <SiteFooter />
      </body>
    </html>
  );
}

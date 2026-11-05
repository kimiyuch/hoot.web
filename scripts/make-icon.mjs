// Draws the Possum icon: a big-eyed brushtail possum peeking over a tear-off calendar page with the kimiyu bear
// stamped where the date would be, on a late-night plum square. Writes the SVGs to artwork/ and renders the PNGs
// with headless Chrome (no npm packages needed).
// Usage: node scripts/make-icon.mjs
import { execFileSync } from "node:child_process";
import { mkdirSync, mkdtempSync, readFileSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";

const root = new URL("../", import.meta.url).pathname;
const chrome = "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome";

// The kimiyu studio bear (copied from kimiyu.web), in soft greys like a stamp on the calendar page.
const stamp = {
  "#9d6b45": "#c4c3cb", // ears, head
  "#e3bb92": "#d2d1d8", // inside the ears
  "#ddb487": "#d0cfd6", // snout
  "#292929": "#a6a5ae", // eyes, nose, mouth
  "#fcfbfc": "#c4c3cb", // eye highlights blend into the head
};
const bearSource = readFileSync(root + "artwork/kimiyu-bear.svg", "utf8");
const bear = bearSource
  .replace(/^[\s\S]*?<svg[^>]*>|<\/svg>\s*$/g, "")
  .replace(/<!--.*?-->/g, "")
  .replace(/fill="(#[0-9a-f]{6})"/gi, (match, hex) => `fill="${stamp[hex.toLowerCase()] ?? "#c4c3cb"}"`);

const defs = `
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#9a6a9a"/><stop offset=".55" stop-color="#5a3560"/><stop offset="1" stop-color="#2a1631"/></linearGradient>
    <linearGradient id="sheen" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".18"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>
    <linearGradient id="fur" gradientUnits="userSpaceOnUse" x1="0" y1="150" x2="0" y2="520"><stop offset="0" stop-color="#b3b0ba"/><stop offset="1" stop-color="#7c7986"/></linearGradient>
    <linearGradient id="ear" gradientUnits="userSpaceOnUse" x1="0" y1="110" x2="0" y2="290"><stop offset="0" stop-color="#9c99a4"/><stop offset="1" stop-color="#7c7986"/></linearGradient>
    <linearGradient id="mask" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f4efea"/><stop offset="1" stop-color="#ddd3cc"/></linearGradient>
    <linearGradient id="page" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fdfcfb"/><stop offset="1" stop-color="#e9e6e9"/></linearGradient>
    <linearGradient id="header" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f06a5c"/><stop offset="1" stop-color="#d4473c"/></linearGradient>
    <linearGradient id="ring" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#6f6f78"/><stop offset=".45" stop-color="#e2e2e8"/><stop offset="1" stop-color="#77777f"/></linearGradient>
    <radialGradient id="eye" cx=".42" cy=".38" r=".7"><stop offset="0" stop-color="#3b2a3f"/><stop offset=".7" stop-color="#140c16"/></radialGradient>
    <filter id="drop" x="-20%" y="-20%" width="140%" height="160%"><feDropShadow dx="0" dy="14" stdDeviation="18" flood-color="#12061a" flood-opacity=".4"/></filter>
    <filter id="soft" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="10" stdDeviation="12" flood-color="#12061a" flood-opacity=".3"/></filter>
    <filter id="icon" x="-10%" y="-10%" width="120%" height="125%"><feDropShadow dx="0" dy="10" stdDeviation="12" flood-color="#000" flood-opacity=".3"/></filter>
    <clipPath id="squircle"><rect x="100" y="100" width="824" height="824" rx="185"/></clipPath>
    <clipPath id="pageClip"><rect x="304" y="486" width="416" height="392" rx="36"/></clipPath>`;

const background = `
  <rect width="1024" height="1024" fill="url(#bg)"/>
  <rect width="1024" height="560" fill="url(#sheen)"/>`;

// The possum behind the calendar page, the page with the bear stamped on it, then the paws on its top edge.
const art = `
  <g class="possum" filter="url(#soft)">
    <g fill="url(#ear)">
      <ellipse cx="370" cy="214" rx="66" ry="92" transform="rotate(-28 370 214)"/>
      <ellipse cx="654" cy="214" rx="66" ry="92" transform="rotate(28 654 214)"/>
    </g>
    <path d="M512 196C632 196 712 262 712 352C712 430 640 492 512 500C384 492 312 430 312 352C312 262 392 196 512 196Z" fill="url(#fur)"/>
  </g>
  <g class="ears" fill="#e8a7ac">
    <ellipse cx="374" cy="222" rx="38" ry="62" transform="rotate(-28 374 222)"/>
    <ellipse cx="650" cy="222" rx="38" ry="62" transform="rotate(28 650 222)"/>
  </g>
  <path class="cheeks" d="M512 300C590 300 664 346 676 404C650 462 586 494 512 498C438 494 374 462 348 404C360 346 434 300 512 300Z" fill="url(#mask)"/>
  <path class="stripe" d="M512 206C540 206 552 250 546 300C540 350 528 384 512 404C496 384 484 350 478 300C472 250 484 206 512 206Z" fill="#5f5b69" opacity=".55"/>
  <g class="eyes">
    <g fill="#4e4a58" opacity=".45"><ellipse cx="434" cy="338" rx="62" ry="58"/><ellipse cx="590" cy="338" rx="62" ry="58"/></g>
    <g fill="url(#eye)"><circle cx="436" cy="340" r="48"/><circle cx="588" cy="340" r="48"/></g>
    <g fill="#fff"><circle cx="452" cy="322" r="15"/><circle cx="604" cy="322" r="15"/></g>
    <g fill="#fff" opacity=".7"><circle cx="420" cy="362" r="6"/><circle cx="572" cy="362" r="6"/></g>
  </g>
  <path class="nose" d="M488 420Q512 410 536 420Q540 438 512 452Q484 438 488 420Z" fill="#e48a95"/>
  <path d="M512 452v14M512 466q-14 10-26 2M512 466q14 10 26 2" fill="none" stroke="#a47a80" stroke-width="5" stroke-linecap="round"/>
  <g fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".6">
    <path d="M462 444l-90-10M462 456l-86 12M562 444l90-10M562 456l86 12"/>
  </g>

  <g filter="url(#drop)">
    <rect x="304" y="486" width="416" height="392" rx="36" fill="url(#page)"/>
  </g>
  <g clip-path="url(#pageClip)">
    <rect x="304" y="486" width="416" height="104" fill="url(#header)"/>
    <rect x="304" y="586" width="416" height="6" fill="#000" opacity=".08"/>
  </g>
  <g transform="translate(512 738) scale(0.15) translate(-627 -632)">${bear}</g>

  <g class="rings">
    <rect x="452" y="460" width="20" height="62" rx="10" fill="url(#ring)"/>
    <rect x="552" y="460" width="20" height="62" rx="10" fill="url(#ring)"/>
    <g fill="#9b2f29" opacity=".6"><circle cx="462" cy="530" r="9"/><circle cx="562" cy="530" r="9"/></g>
  </g>

  <g fill="#8e8b97" filter="url(#soft)">
    <ellipse cx="370" cy="500" rx="50" ry="30"/><ellipse cx="654" cy="500" rx="50" ry="30"/>
  </g>
  <g fill="none" stroke="#e7c3c6" stroke-width="6" stroke-linecap="round" opacity=".9">
    <path d="M352 510v13M370 512v13M388 510v13M636 510v13M654 512v13M672 510v13"/>
  </g>`;

const svg = (viewBox, body) =>
  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${viewBox}">\n  <defs>${defs}\n  </defs>${body}\n</svg>\n`;

// Rounded square on transparent with a shadow, on the macOS icon grid like the siblings': the app icon, favicon, README.
const rounded = svg("0 0 1024 1024", `
  <rect x="100" y="100" width="824" height="824" rx="185" fill="#5a3560" filter="url(#icon)"/>
  <g clip-path="url(#squircle)"><g transform="translate(100 100) scale(0.8047)">${background}<g transform="translate(0 -6)">${art}</g></g></g>`);
// Full-bleed square for the apple-touch-icon (iOS rounds the corners itself).
const square = svg("0 0 1024 1024", background + art);
// The possum and its calendar page without a background.
const mascot = svg("150 100 724 820", art);

mkdirSync(root + "artwork", { recursive: true });
writeFileSync(root + "artwork/icon.svg", rounded);
writeFileSync(root + "artwork/icon-square.svg", square);
writeFileSync(root + "artwork/mascot.svg", mascot);

const renders = [
  [rounded, "artwork/icon.png", 1024],
  [square, "artwork/icon-square.png", 1024],
  [mascot, "artwork/mascot.png", 800],
];
const work = mkdtempSync(join(tmpdir(), "possum-icon-"));
for (const [source, out, size] of renders) {
  const page = join(work, "page.html");
  writeFileSync(page, `<style>html,body{margin:0;background:transparent}svg{display:block;width:${size}px;height:${size}px}</style>${source}`);
  execFileSync(chrome, [
    "--headless", "--disable-gpu", "--hide-scrollbars", "--force-device-scale-factor=1",
    `--window-size=${size},${size}`, "--default-background-color=00000000",
    `--user-data-dir=${join(work, "profile")}`, `--screenshot=${root + out}`, `file://${page}`,
  ], { stdio: "ignore" });
  console.log(out);
}

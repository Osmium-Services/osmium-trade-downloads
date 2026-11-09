# Trade Downloads Service

Status (2026-10-29): **built and installed on dev, v0.1.1**; no theme view yet. A page for logged-in trade customers listing the shop's
product documents and images by category, with a one-click ZIP of each product's images. Service id `osmium-trade-downloads` (repo
`Osmium-Services/osmium-trade-downloads`, category business). Needs [[Trade Accounts Service (design)|Trade Accounts]] and `osmium-shop`.
It stores nothing: no tables, no settings. Part of [[Osmium Architecture]].

## What SolarGo2 did (the reference)

Three controllers in its `trade` module: a documents list (products with any file, grouped by category, "Other" last), a per-product page
(datasheet, brochure and instructions PDFs, a main image and `extraN` images) and an image ZIP built from the `@2x` files. The grouping SQL
hard-coded SolarGo2's brands and category ids. Behind the trade login only hid the links: the files themselves were plain public static files.

## Decision (Gavin, 2026-10-29)

The files stay as public as they already are (the same files the public product pages link). Trade Downloads is a **login-gated
convenience** (the grouped list and the ZIPs), not a lock on the files. Moving files out of the public folder would change the shop's layout
and its public product pages, so it was ruled out.

## How it works

- **`shop.product.files`** (shop 0.40.0) lists live, non-discontinued products that have a datasheet, brochure, instructions, main image or
  extra images on disk, with the category each is listed under first (its path and sort numbers from the top of the tree). It reads the
  shop's own layout `/assets/images/shop/{brand}/{model}/...` using `ShopPaths`. See [[Hooks Reference]].
- **`trade.customer.current`** (Trade Accounts 0.7.0) says who is logged in, or nobody.
- **Page `trade/downloads`:** created on install, binned on disable or remove, restored on enable, protected from page tools (like Trade
  Accounts' pages). `page.claim` returns the theme view `trade/views/downloads.phtml` with `$tradeDownloads` (`loggedIn`, `loginUrl`,
  `customer`, `sections`, `zipAction`). A claim cannot redirect, so a visitor who is not logged in gets the view with `loggedIn` false.
- **Sections** follow the category sort order from the top of the tree; products with no category go in "Other", last; products by title.
- **Image ZIP:** `POST /api/trade-download-images` with field `id`. Logged-in customers only (403 otherwise), 404 for an unknown product or
  one with no images. It packs the `@2x` file when there is one, named without `@2x`, and sends `{product-slug}-images.zip`.

## Tested on dev (2026-10-29)

Logged in as a customer with throwaway sample files: the page lists the category, product and datasheet; the ZIP is a real attachment; an
unknown or missing id gives 404; a plain GET gives 403; a POST with no login gives 403. The test view and sample files were removed.
Found by testing: core's api routes accept **only same-origin POSTs**, so 0.1.0's GET link failed; 0.1.1 posts a small form. See [[Lessons Learned]].

## Left

- The theme view (waits for the SolarGo2 rebuild, like the other trade pages) and a link from the trade account page.
- Per-product pages (SolarGo2 had one): the single grouped page lists documents and images inline instead.
- Untested: a real shop's full product set (only the one seed product), and the Store update path to 0.1.1.

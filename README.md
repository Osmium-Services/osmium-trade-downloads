# Trade Downloads

A page for logged-in trade customers that lists the shop's product documents (datasheet, brochure, instructions) and images, grouped by the shop's own categories, with a one-click ZIP of each product's images. Builds on Trade Accounts (who is logged in) and the Osmium Shop (the products and their files). It stores nothing and has no tables.

The files are the shop's own and are as public as its product pages. This service is a login-gated convenience (the grouped list and the ZIPs), not a lock on the files.

## How it works

- `trade.customer.current` (Trade Accounts 0.7.0 or later) says who is logged in, or nobody.
- `shop.product.files` (shop 0.40.0 or later) lists every live product that has files, with its category (and the category's path and sort numbers from the top of the tree), documents and images.
- The page `/trade/downloads/` is created when the service is installed (put in the bin when disabled or removed). A theme view shows it.
- `POST /api/trade-download-images` (field `id`, the product id) sends that product's images as a ZIP (the 2x version of each image when there is one). It refuses anyone who is not logged in. Core accepts only same-origin POSTs on api routes, so the page offers it as a small form.

## Theme view

The theme supplies `trade/views/downloads.phtml` with `$tradeDownloads`:

- `loggedIn` (bool): false means show a "please log in" message linking to `loginUrl`
- `customer`: `company_name`, `contact_name`
- `sections`: list of `heading`, `path` (names from the top of the tree) and `products`; a product has `id`, `title`, `brandName`, `name`, `documents` (`type`, `label`, `url`) and `images` (`type`, `label`, `url`)
- `zipAction`: where a small form posts (`method="post"`, a hidden field `id` with the product id) to download a product's images

Sections follow the category sort order, with products that have no category in "Other" last; products are by title.

## One product, and a link for other services

Asking the page for one product (`/trade/downloads/?product=<id>`; core redirects the query string away and keeps it in the session, which the claim reads and clears) gives the view a `product` with its images and documents, or null when the shop has no such product. The theme draws it in the same `trade/views/downloads.phtml`. The `trade.documents.url` hook gives other services the address to put a product id after, so the trade price list can link to it without knowing this page. The list groups products by the sections the `trade.category.layout` hook answers (Trade Ordering does), falling back to each product's own category when nothing answers. The product view also carries `pageUrl`, the product's page on the site, when the shop gives one.

# UX/UI design direction

Sell Game 2 uses a dark arcade terminal visual language. Deep navy surfaces create focus, orange marks actions and purchase moments, and cyan marks wallet balance, availability, and system status. Rounded panels and restrained glow keep the store playful while preserving trust during payment.

## Customer path

1. Browse a game category or search the catalog.
2. Choose an account, service package, or gacha box.
3. Top up Wallet when needed.
4. Confirm recipient and terms.
5. Receive the account or follow delivery progress in account history.

## Route families

- Storefront: home, categories, accounts, and products for discovery and purchase.
- Gacha: gacha listing and detail for transparent rewards, pricing, eligibility, and reveal state.
- Wallet: wallet page for payment method, QR instructions, slip upload, and review status.
- Account: dashboard, orders, purchases, notifications, and password management.
- Support: contact and news for direct help and operational context.
- Admin: inventory, topups, orders, content, reports, and settings.

## Interaction rules

- Every purchase surface states price, availability, recipient requirements, and delivery expectation before the action.
- Available inventory shows a ready-to-deliver state. The existing controller continues to return 404 for sold accounts; the template also guards against rendering a purchase button for unavailable inventory.
- When a customer cannot afford an available account, the purchase panel offers a direct Wallet top-up action.
- Empty states explain what happened and offer a useful next route.
- Labels are visible for forms; placeholders provide examples rather than acting as labels.
- Keyboard focus is visible and mobile navigation remains horizontally reachable.

## Responsive system

The header wraps on narrow screens with horizontally scrollable navigation. Account cards use two columns on small screens and four on desktop; category cards use one column on mobile and two from the medium breakpoint. Account and admin utilities stay in compact horizontally scrollable bars so important destinations remain accessible without a desktop-only menu.

## Implementation and verification — 11 September 2026

GPT-6 Astra implemented the approved Game Vault visual system, storefront and mobile navigation, account/admin navigation, home purchase steps, catalog filters, authentication labels, Wallet flow, account purchase confirmation, credential delivery controls, and desktop/mobile admin workspace. Existing gacha, order, support, and conditional service pages inherit the shared shell, controls, panels, and table styling.

The parent review completed the interrupted final pass, including narrow-screen header wrapping, search visibility, Wallet sequencing, credential copy feedback, admin overflow containment, human-readable payment labels, checkbox styling, and reduced-motion precedence.

- Production asset build and Blade view compilation passed.
- Existing application suite: 66 tests passed, 614 assertions.
- Browser checks at widths 390, 900, and 1366: home, categories, gacha, contact, Wallet, admin overview, and topup review had no document-level horizontal overflow.
- Confirmed empty search results and filter reset, PromptPay/TrueMoney panel switching, and insufficient-balance topup CTA.
- Authenticated browser checks used the isolated local staging database. No purchase, payment approval, or gacha spin was submitted during this UX review.
- These checks cover representative pages and existing automated journeys; every individual admin form and delivery state was not manually exercised.

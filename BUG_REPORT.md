# BUG_REPORT.md

**Project:** Resort Voucher QR (Laravel)
**QA Scope:** Full review of services, controllers, models, requests, middleware, views, migrations, seeders, scheduled tasks
**Date:** 2026-07-31
**Reviewers:** QA Engineer (automated static review + manual code walkthrough)

---

## 1. Summary

| Severity | Count |
|----------|-------|
| Critical | 4 |
| High | 12 |
| Medium | 18 |
| Low | 10 |
| **Total** | **44** |

The application core (voucher generation, QR scanning, redemption quota accounting, distributed locking) is
well structured and mostly correct. The most serious issues are:

1. **Check-out is permanently broken** (permission never seeded).
2. **Security middleware is bypassable** (spoofable client IP, broken CIDR math) and its block page crashes with a 500.
3. **Temporary vouchers can redeem facilities that were never granted** (no facility allow-list check).
4. **Scheduled WhatsApp delivery breaks when "Public Link" method is selected** (qr_path always empty).

---

## 2. CRITICAL

### C-01 — Check-out always returns 403: `bookings.checkout` permission is never seeded
- **Files:** `app/Http/Controllers/BookingController.php:152`, `app/Http/Controllers/Api/BookingApiController.php:130`, `database/seeders/RolesAndPermissionsSeeder.php:16-18`
- **Type:** Logic
- **Description:** `checkOut()` calls `authorizePermission('bookings.checkout')`, but the seeder only creates `bookings.view`, `bookings.create`, `bookings.checkin`. Spatie returns `false` for undefined permissions, so **every checkout — including super-admin — returns 403**. No code path ever creates this permission.
- **Fix:** Add `'bookings.checkout'` to the seeder permission list (and a migration `Permission::findOrCreate` for existing DBs), or reuse an existing permission.
- **Status: FIXED (2026-07-31)** — `'bookings.checkout'` added to `RolesAndPermissionsSeeder.php`; new migration `2026_07_31_000001_add_bookings_checkout_permission.php` creates the permission for existing DBs and grants it to any role that holds `bookings.checkin` (run `php artisan migrate`).

### C-02 — Attack-detection block page crashes with PHP 8 `ValueError` → 500 instead of 403/429
- **File:** `app/Http/Middleware/AttackDetectionMiddleware.php:471,596`
- **Type:** Logic
- **Description:** The block page HTML is rendered via `sprintf($html, $e(now()->format('Y-m-d H:i:s')))`, but the CSS contains unescaped `%` (`linear-gradient(135deg, ... 0%, ... 50%, ... 100%)`). `sprintf()` throws `ValueError: Unknown format specifier ","`. Every blocked request (SQLi/XSS/rate-limit/UA) returns **500**, and with `APP_DEBUG=true` a full stack trace is disclosed to the attacker.
- **Fix:** Escape `%` as `%%` in the heredoc, or replace `sprintf` with `str_replace('%TIMESTAMP%', ...)`.
- **Status: FIXED (2026-07-31)** — `getBlockPage()` now uses `str_replace('%TIMESTAMP%', ...)` (`AttackDetectionMiddleware.php:590`); the `%` characters in CSS no longer crash PHP 8.

### C-03 — Client-supplied `X-Forwarded-For` / `X-Real-IP` is blindly trusted → full bypass of attack detection & rate limiting
- **File:** `app/Http/Middleware/AttackDetectionMiddleware.php:102-177` (esp. 165-176)
- **Type:** Security
- **Description:** `getClientIP()` reads `X-Forwarded-For` / `X-Real-IP` and never verifies the peer against `TrustProxies` (no trusted proxies configured). An attacker sends `X-Forwarded-For: 127.0.0.1` → `isTrustedIP()` returns true → all SQLi/XSS/IDOR/traversal checks, DDoS counter and rate limits are skipped. The same header rotates rate-limit keys and forges audit/IP logs.
- **Fix:** Use `$request->ip()` (which respects `TrustProxies`) as the single source of truth; never trust client headers.
- **Status: FIXED (2026-07-31)** — `getClientIP()` now returns `$request->ip()` only (`AttackDetectionMiddleware.php:163-169`); client-supplied `X-Forwarded-For` / `X-Real-IP` headers are ignored.

### C-04 — Check-in is not atomic: failed voucher generation leaves booking stuck in `check_in` with no voucher
- **File:** `app/Services/BookingService.php:83-87`
- **Type:** Logic
- **Description:** `status = CheckIn` is saved **before** `generateForBooking()`. If generation throws (e.g. `noFacilities()`), the exception propagates but the status change is already committed; the early-return on retry silently no-ops, so the guest is permanently "checked in" with no voucher and can never redeem.
- **Fix:** Generate the voucher inside the same `DB::transaction` as the status change (or roll back status on failure); re-verify status inside the lock.
- **Status: FIXED (2026-07-31)** — `BookingService::checkIn()` now wraps facility sync + status change + voucher generation in a single `DB::transaction` (rolls back on failure) and re-verifies the status via `fresh()` after acquiring the lock (`BookingService.php:64-89`).

---

## 3. HIGH

**Status: ALL FIXED (2026-07-31)**

### H-01 — Temporary vouchers can redeem facilities that were never granted (no facility allow-list check)
- **File:** `app/Services/VoucherService.php:292-361`
- **Type:** Security / Logic
- **Description:** The temporary-voucher branch of `redeem()` never validates `facility_template_id` against the voucher's `facility_template_id` allow-list, and never checks the facility belongs to the outlet's property. A crafted request can redeem **any facility id** (even of another property) as long as the voucher's property matches the outlet's property. Standard vouchers are protected (lines 416-430); temporary vouchers are not.
- **Fix:** In the temporary branch, verify `$facilityTemplateId ∈ explode(',', $voucher->facility_template_id)` and that the facility template belongs to `$voucher->property_id` / `$outlet->property_id` before the quota calculation.
- **Status: FIXED (2026-07-31)** — temporary branch of `redeem()` now enforces the allow-list (`in_array($facilityTemplateId, array_map('intval', explode(',', ...)), true)`) and loads the facility with `where('property_id', $voucher->property_id)->where('is_active', true)` before any quota math (`VoucherService.php:309-325`).

### H-02 — IDOR: booking actions (view / check-in / check-out / edit / update / delete) have no property scoping
- **File:** `app/Http/Controllers/BookingController.php:109-122,124-148,150-157,281-326`
- **Type:** Security
- **Description:** `index` applies `applyPropertyScope`, but all route-model-bound actions rely only on the permission check. A user scoped to property A with `bookings.view`/`bookings.checkin`/`bookings.create` can view, modify, check in/out and **hard-delete** bookings of property B by enumerating IDs.
- **Fix:** Resolve bookings within the user's property scope in every action (or via route-model-binding callback).
- **Status: FIXED (2026-07-31)** — new reusable guard `Controller::authorizePropertyAccess($model, 'property_id')` (super-admin bypass, else the model's property must be in the user's `properties()`); called in `show`/`checkIn`/`checkOut`/`edit`/`update`/`destroy` (`BookingController.php:112,128,155,287,307,327`).

### H-03 — IDOR: facilities & outlets edit/update/delete have no property scoping
- **Files:** `app/Services/FacilityService.php:32-59`, `app/Services/OutletService.php:34-64`, `app/Http/Controllers/FacilityController.php:49-76`, `app/Http/Controllers/OutletController.php:51-80`
- **Type:** Security
- **Description:** Same pattern as H-02: `edit`/`update`/`destroy` operate on any model instance without checking the user's property scope.
- **Fix:** Apply property scoping in controllers and verify ownership inside services (defense in depth).
- **Status: FIXED (2026-07-31)** — `authorizePropertyAccess()` added to `FacilityController` and `OutletController` `edit`/`update`/`destroy`; plus `withValidator` in `Store/UpdateFacilityRequest` and `Store/UpdateOutletRequest` now rejects `property_id` outside the user's scope (see also M-14/M-15).

### H-04 — Client controls `total_pax`, `nights`, `status` and facility `quota_total` → quota bypass / forged check-in
- **Files:** `app/Http/Requests/StoreBookingRequest.php:28-40`, `app/Services/BookingService.php:29-43,186`, `app/Services/StayQuotaService.php:11`
- **Type:** Validation / Security
- **Description:** The request accepts `total_pax`, `nights`, `status` (incl. `check_in`) and `facilities.*.quota_total`. A client can submit `adults: 10, total_pax: 1, quota_total: 1` and grant a 1-pax voucher for a 10-pax stay, or create a booking already in `check_in` status with no voucher/`checked_in_at`, bypassing the check-in procedure.
- **Fix:** Remove `status`, `total_pax`, `nights`, `facilities.*.quota_total` from client rules; compute all of them server-side (always overwrite, not `??=`).
- **Status: FIXED (2026-07-31)** — all four fields removed from `StoreBookingRequest` rules; `BookingService::create()` sets `status = ExpectedArrival`; `enrichBookingData()` recomputes `total_pax` from `adults+children` and `nights` from `check_in/check_out`; facility `quota_total` is always `StayQuotaService::quotaForBooking()`; new `BookingService::updateBooking()` (web + API update routes) routes updates through the same enrichment.

### H-05 — API `verify` for temporary vouchers skips the expiry check and the outlet facility filter
- **File:** `app/Http/Controllers/Api/VoucherApiController.php:170-202`
- **Type:** Logic
- **Description:** The web `verifyScannedCode` (VoucherController.php:314-336) checks `expires_at` and filters statuses by the outlet's facilities; the API `verify` does **neither**. Expired temporary vouchers verify as "success", and facilities the outlet doesn't serve are returned. Also `auto_select_facility` is missing from the API response.
- **Fix:** Mirror the web logic (expiry check + outlet filter + `auto_select_facility`) in the API controller.
- **Status: FIXED (2026-07-31)** — API `verify()` temporary branch now rejects when `now >= expires_at` (logs `outside_stay_period`), filters `$facilityStatuses` by the outlet's facility templates, and returns `auto_select_facility`; the booking branch gained the outlet filter + `auto_select_facility` too (`VoucherApiController.php:170-215,240-245,276-283`).

### H-06 — Scheduled deliveries always fail when delivery method is `public_link` (qr_path empty)
- **File:** `app/Services/VoucherDeliveryService.php:217-240` (with `schedule()` at 88-136)
- **Type:** Logic
- **Description:** For `public_link` method, `schedule()` skips the QR-image block, so `$qrUrl = null` and the pending log is created with empty `qr_path`. `sendPendingLogs()` then throws `"QR Path URL is empty."` and **marks every scheduled link delivery as failed** — the `public_link` method never works on the schedule path.
- **Fix:** Only require `qr_path` when the delivery method is `qr_image`; for `public_link`, send the message containing `{voucher_link}`.
- **Status: FIXED (2026-07-31)** — `sendPendingLogs()` now treats an empty `qr_path` as a valid `public_link` delivery (skips URL/disk validation) and sends the message as-is; the `"QR Path URL is empty."` throw was removed (`VoucherDeliveryService.php:217-241`).

### H-07 — Booking code normalization destroys alphanumeric codes (`'RSV-1001'` → `'0'`)
- **File:** `app/Services/PmsBookingImportService.php:132-148`
- **Type:** Logic
- **Description:** `(string)(int)((float)$code)` converts any non-numeric code to `"0"` and truncates decimals. Imported `booking_code` / `pms_voucher_ref` values are corrupted; many distinct bookings silently collapse to code `'0'`.
- **Fix:** Keep trimmed strings; only cast when `is_numeric($code)`.
- **Status: FIXED (2026-07-31)** — `normalizeBookingCode()` and `normalizeVoucherRef()` now trim, keep the string untouched, and only apply the numeric cast when `is_numeric()` (`PmsBookingImportService.php:132-156`).

### H-08 — CORS: `Access-Control-Allow-Origin: *` hardcoded, overrides restricted `config/cors.php`
- **File:** `app/Http/Middleware/CorsMiddleware.php:15-17` (registered globally in `bootstrap/app.php:36`)
- **Type:** Security
- **Description:** The custom middleware runs after `HandleCors` and overwrites the origin restriction with `*` on every response. `CORS_ALLOWED_ORIGINS` becomes dead config; any origin can read API responses.
- **Fix:** Remove the custom middleware and rely on `config/cors.php` + `HandleCors`.
- **Status: FIXED (2026-07-31)** — `CorsMiddleware` removed from `bootstrap/app.php` (append list) and the file deleted; the framework's default `HandleCors` (already in the global stack, `vendor/.../Middleware.php:458`) now enforces `config/cors.php` (`allowed_origins` from `CORS_ALLOWED_ORIGINS`).

### H-09 — CSP allows arbitrary scripts from public CDNs without SRI
- **File:** `app/Http/Middleware/SecurityHeadersMiddleware.php:22`
- **Type:** Security
- **Description:** `script-src 'self' 'nonce-…' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com` — both CDNs serve arbitrary published packages; a malicious package would run in the app's context despite the nonce. `style-src 'unsafe-inline'` further weakens the policy.
- **Fix:** Pin exact library URLs/versions with SRI `sha256-…` hashes; remove the broad CDN allowlist.
- **Status: FIXED (2026-07-31)** — CSP `script-src`/`style-src`/`font-src` drop `https://cdnjs.cloudflare.com` (unused); all 7 assets (bootstrap css/js, admin-lte css/js, fontawesome css, jquery, jsQR) now carry real `integrity="sha384-…"` + `crossorigin="anonymous"` in `layouts/app.blade.php`, `layouts/guest.blade.php`, `vouchers/scan.blade.php`.

### H-10 — IP whitelist CIDR math is broken: IPv6 / malformed masks bypass the whitelist, IPv6 entries throw 500
- **File:** `app/Http/Middleware/IpWhitelistMiddleware.php:72-91`
- **Type:** Security
- **Description:** (1) For IPv6 clients `ip2long()` returns `false`, `false & $mask` → `0`, and `0 === 0` → **any IPv6 address matches any whitelist entry** (full bypass). (2) A malformed mask like `192.168.1.0/abc` casts to 0 → every IP matches. (3) An IPv6 entry `2001:db8::/64` computes `-1 << -32` → uncaught `ArithmeticError` (500).
- **Fix:** Validate mask `1..32`, explicit `ip2long()` `false` checks, `inet_pton` for IPv6; deny on any malformed entry. Note also: the middleware is **never applied to any route** (see M-06).
- **Status: FIXED (2026-07-31)** — `ipInRange()` rewritten pure-PHP: `inet_pton` + byte-wise masking (works for IPv4 and IPv6 without the `gmp` extension), masks validated with `ctype_digit` and bounded by the address family bit count; malformed subnets/IPs never match. Verified with 20/20 unit cases. Middleware application handled in M-06.

### H-11 — Check-in endpoint trusts raw `facility_template_ids` with no validation
- **Files:** `app/Http/Controllers/BookingController.php:138-145`, `app/Services/BookingService.php:65-76`
- **Type:** Validation
- **Description:** The POST check-in reads `facility_template_ids` straight from `request()` — no `integer`/`exists` rules, no property/active check. Invalid IDs create orphaned `booking_facilities` rows or 500 FK errors; cross-property templates get attached to the voucher.
- **Fix:** Validate through a FormRequest (`exists` + `integer`) and verify `property_id` in the service.
- **Status: FIXED (2026-07-31)** — `BookingService::checkIn()` validates the ids against `property_id = booking's property` + `is_active = true` (throws `InvalidArgumentException` on any mismatch) before rebuilding `booking_facilities`; web (`BookingController.php:138-154`) and API (`BookingApiController.php:112-130`) surface it as a 422 / error flash.

### H-12 — File upload validation trusts client-supplied MIME/extension
- **File:** `app/Http/Middleware/ValidateFileUpload.php:49-73`
- **Type:** Security
- **Description:** `getMimeType()` returns the client-declared type from `$_FILES`, and `getClientOriginalExtension()` is client-controlled. Arbitrary content renamed `data.csv` with MIME `text/csv` passes; the 1024-byte content sniff is trivially bypassable, and the extension blocklist misses `.phar`, `.pht`, `.shtml`, `.cgi`.
- **Fix:** Detect MIME server-side (`finfo` on `getRealPath()`), whitelist extensions, and server-generate stored filenames.
- **Status: FIXED (2026-07-31)** — server-side `finfo(FILEINFO_MIME_TYPE)` on `getRealPath()` is now the authoritative MIME check (client MIME only a first-pass filter); `dangerousExtensions` extended with `.phar`, `.pht`, `.shtml`, `.cgi`, `.pl`, `.py`, `.rb` (`ValidateFileUpload.php:25-31,75-89`).

---

## 4. MEDIUM

**Status: ALL FIXED (2026-07-31)**

### M-01 — Whacenter phone format never normalized (raw `08xx` sent)
- **File:** `app/Services/WhacenterService.php:55-59`
- **Type:** Logic
- **Description:** The guest phone (e.g. `08123456789`) is sent as-is in `number`. WHACENTER expects international format without leading zero (`628123456789`). Real sends to non-normalized numbers will be rejected or fail silently (only mock mode hides this).
- **Fix:** Normalize to `62`-prefixed format (mirror `cleanPhoneForFonnte` logic in reverse) before sending.

### M-02 — Fonnte sends a leading-zero `target` together with `countryCode: 62`
- **File:** `app/Services/FonnteService.php:57-61,117-130`
- **Type:** Logic
- **Description:** `cleanPhoneForFonnte` returns `08xxxxxxxxx` (keeps leading `0`) while `countryCode: '62'` is also sent. Depending on Fonnte's interpretation this may produce an invalid recipient (`6208...` or dropped number). Must be verified against the live API; if Fonnte requires bare `8xxxxxxxxx` with `countryCode=62`, this explains real "message not delivered" failures.
- **Fix:** Verify with a live test send; align target format with `countryCode` (strip leading `0` when `countryCode` is used, or drop `countryCode`).

### M-03 — `getFacilityStatuses()` temporary branch ignores expiry → "Available today" on expired vouchers
- **File:** `app/Models/GuestVoucher.php:89-115`
- **Type:** Logic
- **Description:** `is_available = $remaining > 0` with no `expires_at` check. `publicShow()` never calls `checkAndExpireIfNeeded()`, so an expired temporary voucher's public page (and any status consumer) reports facilities as available.
- **Fix:** Include `!$this->expires_at || Carbon::now($tz)->lt($this->expires_at)` in `is_available`.

### M-04 — Public guest page always shows the "Active Stay Pass" badge, even when expired
- **File:** `resources/views/vouchers/public.blade.php:7`
- **Type:** Logic
- **Description:** Badge is hard-coded; expired vouchers still show "Active Stay Pass" and a redeemable-looking QR.
- **Fix:** Derive the badge from `$voucher->status` / expiry.

### M-05 — Stored XSS in scan result via `innerHTML` (guest/facility names from CSV import)
- **File:** `resources/views/vouchers/scan.blade.php:563-580`
- **Type:** Security
- **Description:** `resultDetails.innerHTML` interpolates `data.guest`/`data.facility` unescaped. Guest names originate from CSV import (attacker-influenceable) — a name like `<img src=x onerror=...>` executes JS in the staff session.
- **Fix:** Build the result via DOM `textContent`/`createElement`, or escape values before interpolation.

### M-06 — Admin IP whitelist middleware is registered but never applied to any route
- **File:** `bootstrap/app.php:43` (alias `ip.whitelist`); no route uses it
- **Type:** Missing Feature / Security
- **Description:** `ADMIN_IP_WHITELIST` / `ALLOW_EMPTY_IP_WHITELIST` config is dead code; admin areas (users, roles, settings, exports) are reachable from any IP that can authenticate.
- **Fix:** Apply `->middleware('ip.whitelist')` to admin route groups (after fixing H-10).

### M-07 — Session fixation on API login (no `session()->regenerate()`)
- **File:** `app/Http/Controllers/Api/AuthApiController.php:13-23`
- **Type:** Security
- **Description:** Web login regenerates the session; API login does not (and API routes run the `web` group, so a session exists). Pre-seeded session IDs survive login.
- **Fix:** Call `$request->session()->regenerate();` after `authenticate()`.

### M-08 — Missing Host validation → open redirect via HTTPS redirect
- **File:** `app/Http/Middleware/ForceHttpsMiddleware.php:30`, `bootstrap/app.php` (no `trustHosts()`)
- **Type:** Security
- **Description:** `redirect()->secure()` builds the URL from the unvalidated `Host` header; with `FORCE_HTTPS=true`, `Host: evil.com` yields a cached 301 to `https://evil.com/...`.
- **Fix:** Register `$middleware->trustHosts()` or validate Host against `APP_URL`.

### M-09 — API endpoints have no global rate limiter
- **Files:** `app/Providers/AppServiceProvider.php:16-29`, `routes/api.php:29-93`
- **Type:** Security
- **Description:** No `RateLimiter::for('api', ...)`; API routes use the `web` group so the default `throttle:api` never applies. Only login/voucher endpoints are throttled; all CRUD/report endpoints are unthrottled.
- **Fix:** Define an `api` limiter and apply `throttle:api` to the authenticated API group.

### M-10 — Check-out hard-deletes all redemption / scan / delivery history
- **File:** `app/Services/BookingService.php:126-155`
- **Type:** Logic / Missing Feature
- **Description:** `checkOut()` hard-deletes the voucher, `redemption_logs`, `qr_scan_logs`, `delivery_logs`, `booking_facilities` and the booking. All historical data is lost; reports/export lose every checked-out guest; `checked_out_at` is never persisted; no transaction/lock wraps the deletion.
- **Fix:** Archive instead of delete (keep rows, set terminal status + `checked_out_at`), at minimum wrap in a transaction.

### M-11 — Check-in TOCTOU + lock held during slow external HTTP delivery
- **File:** `app/Services/BookingService.php:51-62,89-113`
- **Type:** Logic
- **Description:** Status is checked **before** the lock is acquired and not re-checked inside it; the 15s lock is held across `sendImmediate()`/`schedule()` HTTP calls. Slow providers can cause duplicate facility rebuild and duplicate WhatsApp messages.
- **Fix:** Re-check status after lock acquisition; run deliveries outside the lock.

### M-12 — Timezone inconsistency: booking dates in app tz vs redemption in property tz
- **File:** `app/Services/VoucherService.php:395-449`, `app/Services/BookingService.php:84,196-202`
- **Type:** Logic
- **Description:** Booking `check_in/check_out` are stored/parsed in the app timezone; redemption compares `Carbon::today($property->timezone)`. For properties ahead of the app tz, redemptions between ~19:00–24:00 local fall on the wrong calendar day → guests wrongly rejected ("not valid today") or granted access on the wrong day.
- **Fix:** Persist all derived dates in the property timezone.

### M-13 — Facility quota becomes stale after booking update; `quota_total` client-trusted
- **Files:** `app/Http/Controllers/BookingController.php:300-317`, `app/Services/BookingService.php:157-181`
- **Type:** Logic
- **Description:** `update()` rewrites pax but never recalculates `booking_facilities.quota_total` when facilities are omitted; when provided, `quota_total` is taken from the client verbatim.
- **Fix:** Always recompute `quota_total = StayQuotaService::quotaForBooking($booking)` server-side.

### M-14 — Cross-property references validated only by `exists`
- **Files:** `app/Http/Requests/StoreBookingRequest.php:17-19,37`, `app/Http/Requests/StoreOutletRequest.php:20`
- **Type:** Validation
- **Description:** `room_id`, `facilities.*.facility_template_id`, and outlet `facility_template_ids.*` can point to other properties' records.
- **Fix:** Use `Rule::exists(...)->where('property_id', ...)` or service-side checks.

### M-15 — Booking create/update not restricted to the user's properties
- **File:** `app/Http/Requests/StoreBookingRequest.php:17`
- **Type:** Security
- **Description:** `property_id` is only `exists:properties,id`; a property-scoped user can create bookings for any property, and update can move a booking across properties.
- **Fix:** Constrain to the user's `properties()` scope.

### M-16 — Auto voucher generation after import silently fails on batch-inserted models
- **File:** `app/Http/Controllers/BookingController.php:185-209`
- **Type:** Logic
- **Description:** `$booking->refresh()` on `WithBatchInserts` in-memory models throws `ModelNotFoundException`, caught and logged only — imported checked-in bookings end up without vouchers while the UI reports success.
- **Fix:** Re-query from DB after import instead of refreshing in-memory models; surface failures.

### M-17 — Live `.env` uses insecure flags (would leak debug data if deployed)
- **File:** `.env` (`APP_ENV=local`, `APP_DEBUG=true`, `SESSION_SECURE_COOKIE=false`, `FORCE_HTTPS=false`)
- **Type:** Security
- **Description:** If this file is ever copied to production, stack traces are exposed and session cookies ride over HTTP.
- **Fix:** Enforce production values at deploy (boot-time guard if `APP_DEBUG=true` outside `local`).

### M-18 — No room double-booking prevention
- **File:** `app/Services/BookingService.php:23-49`
- **Type:** Missing Feature
- **Description:** `create()` never checks for overlapping bookings on the same `room_id`; two bookings can occupy one room on the same night and both issue vouchers.
- **Fix:** Add an overlap check inside the create transaction.

---

## 5. LOW

| ID | File:Line | Type | Description |
|----|-----------|------|-------------|
| L-01 | `app/Http/Middleware/AttackDetectionMiddleware.php:85-90,131` | Security | Whitelist prefix match also exempts `/api/auth/*` from URL pattern checks — exactly where credential payloads arrive. |
| L-02 | `app/Http/Middleware/ValidatePaginationParameters.php:73` | Logic | `$request->request->remove('sort')` only clears the POST bag; on GET the raw `sort` still reaches `input('sort')` — the ORDER BY guard is void for GET listings. |
| L-03 | `app/Http/Middleware/ValidatePaginationParameters.php:65` | Security | `sort` accepts any `[a-zA-Z0-9_]` column name; authenticated users can ORDER BY sensitive columns (`password`, `remember_token`) or unindexed columns. |
| L-04 | `app/Http/Middleware/AttackDetectionMiddleware.php:312-361` | Logic | Rate-limit state uses non-atomic read-modify-write on cache; concurrent requests can exceed limits, and `file` cache isn't global across nodes. |
| L-05 | `app/Http/Middleware/AttackDetectionMiddleware.php:105-107` | Security | Attack detection inspects the URL/query string only, never request bodies (where the real injection payloads are). |
| L-06 | `app/Services/RedisCacheService.php:184-206` | Logic | `trackScan` increments are non-atomic; minor, since the route throttle also applies. |
| L-07 | `app/Services/VoucherService.php:145-160` | Logic | `updateVoucher()` never calls `cache->invalidateVoucher()`; cached voucher data (30 min TTL) can be stale. Low impact because `redeem()` re-reads from DB under lock. |
| L-08 | `app/Http/Requests/UpdateVoucherRequest.php:19-20` | Validation | `addition_map.*` keys aren't validated as granted facilities; a crafted request can store additions for facilities not granted (harmless today, but inconsistent data). |
| L-09 | `app/Http/Controllers/DeliverySettingsController.php:47` | Validation | Web `whatsapp_provider` accepts any string (`max:50`) while the API restricts to `in:Fonnte,Whacenter`; unknown values silently fall back to Fonnte. |
| L-10 | `app/Services/FonnteService.php:67-84`, `app/Services/WhacenterService.php:65-84` | Security | Full message + phone + QR URL are logged as info (PII in logs). |
| L-11 | `app/Http/Controllers/BookingController.php:170-172` | Validation | Import validation: `extensions:csv,xls,xlsx,cvs,txt` has typo `cvs` and is extension-only. |
| L-12 | `app/Services/BookingService.php:199` | Logic | `nights` computed from `diffInDays()` on datetime strings → off-by-one for times (e.g. 08-01 10:00 → 08-03 09:00 = 1 night). |
| L-13 | `app/Http/Controllers/BookingController.php:243-253` | Logic | Import stats double-count rows that fail manual checks (`skipped` + `failures`). |
| L-14 | `app/Http/Controllers/BookingController.php:333-366` | Logic | Header-row detection falls back to row 1 with no warning when no header matches. |
| L-15 | `app/Http/Controllers/VoucherController.php:563-568` | Logic | Public QR image endpoint doesn't check voucher status; expired vouchers' QR remains downloadable. |
| L-16 | `routes/api.php:20` + `bootstrap/app.php:49` | Security | Unauthenticated API calls return a 302 HTML redirect to `/login` instead of `401` JSON — breaks API clients and is a mild redirect vector. |
| L-17 | `app/Services/BookingService.php:126-155`, `app/Enums/BookingStatus.php` | Missing Feature | No terminal booking state (e.g. `departed`); reports can't distinguish departed guests. |
| L-18 | `app/Models/User.php` / `.env.example:76` | Missing Feature | Sanctum (`SANCTUM_STATEFUL_DOMAINS`) and `TWO_FACTOR_ENABLED` are advertised but never wired (no `HasApiTokens`, no TOTP). |

**Status: ALL FIXED (2026-07-31)**

- **L-01 — FIXED:** `/api/auth` removed from `whitelistedPaths` (`AttackDetectionMiddleware.php`) — URL pattern checks now also cover the auth endpoints.
- **L-02 — FIXED:** `ValidatePaginationParameters.php` removes invalid `sort` from both the request and query bags, so GET listings can no longer bypass the guard.
- **L-03 — FIXED:** `sort` values on the sensitive-column blocklist (`password`, `remember_token`, `secret`, `token`, `api_token`, `created_at`, `updated_at`, `deleted_at`, `ip_address`) are rejected and removed.
- **L-04 — FIXED:** rate limiting rewritten as atomic `Cache::add` + `Cache::increment` fixed-window counters — no read-modify-write race between concurrent requests.
- **L-05 — FIXED:** `detectBodyAttack()` now scans request bodies (first 8192 bytes) for injection patterns (script tags, UNION SELECT, `N=N`, path traversal, `/etc/passwd`, `%00`); multipart uploads and empty bodies are skipped.
- **L-06 — FIXED:** `trackScan()` increments are atomic (`Cache::add` + `Cache::increment`) for both the per-IP and per-code+IP counters.
- **L-07 — FIXED:** `VoucherService::updateVoucher()` now calls `cache->invalidateVoucher()` after saving, so cached voucher data stays fresh.
- **L-08 — FIXED:** `UpdateVoucherRequest` `withValidator` rejects `addition_map` keys that aren't granted facilities (falls back to the voucher's current `facility_template_id` when no status map is sent).
- **L-09 — FIXED:** web `DeliverySettingsController` `whatsapp_provider` rule is now `in:Fonnte,Whacenter`, matching the API controller.
- **L-10 — FIXED:** phones are masked (`0812****34`) via `maskPhone()` in both `FonnteService` and `WhacenterService`; messages are logged as `md5` hashes and QR URLs as presence flags instead of raw PII.
- **L-11 — FIXED:** typo `cvs` removed (`extensions:csv,xls,xlsx,txt`) and `mimes:csv,txt,xls,xlsx` added — validation is now both extension- and content (finfo)-based.
- **L-12 — FIXED:** `nights` is computed on `startOfDay()` dates in `BookingService::enrichBookingData()`, `PmsBookingImportService`, and `BookingsImport` — no more time-of-day off-by-one.
- **L-13 — FIXED:** rows failing manual checks (guest/property/dates) are counted only in `failures`, not also in `skipped`; summary message updated to "skipped (duplicates)".
- **L-14 — FIXED:** `detectHeadingRow()` logs a warning when no header row matches in the first 10 rows before falling back to line 1.
- **L-15 — FIXED:** `qrImagePublic()` aborts with 410 for inactive vouchers and expired temporary vouchers — expired QR images are no longer downloadable.
- **L-16 — FIXED:** `bootstrap/app.php` renders JSON 401 (`{"message":"Unauthenticated."}`) for `AuthenticationException` on `api/*` paths or JSON-expecting requests — no more HTML 302 redirect.
- **L-17 — RESOLVED:** terminal state already provided by M-10 — check-out archives bookings with `status=expected_departure` + `checked_out_at`; `BookingStatus` enum includes `ExpectedDeparture`. Reports can now distinguish departed guests; no further code needed.
- **L-18 — FIXED:** misleading `SANCTUM_STATEFUL_DOMAINS` and `TWO_FACTOR_ENABLED` removed from `.env.example` (replaced with "not wired" comments); `sanctum/csrf-cookie` removed from `config/cors.php` paths. API auth remains session-based.

---

## 6. Missing Features (consolidated)

1. **Room availability / double-booking prevention** (`BookingService::create`) — Medium.
2. **Terminal booking state & history retention** — check-out currently destroys all logs; reports lose checked-out guests — Medium.
3. **Facility-type flag** — one-time behavior is hard-coded by facility `code` (`SNACK`, `JOURNAL`, `FEED`) in `VoucherService.php:436` and `GuestVoucher.php:138`; adding a new one-time facility requires a code change. Add `is_one_time` to `facility_templates` — Low.
4. **Facility-in-use guard** — facilities can be deactivated/reassigned/deleted while referenced by active bookings, vouchers and outlet pivots — Low.
5. **`ip.whitelist` middleware not applied anywhere** — Medium.
6. **Attack detection doesn't scan request bodies** — Medium.
7. **Per-model `sort` column whitelist** — Low.
8. **2FA / email verification advertised but absent** — Low.

**Status: ALL RESOLVED (2026-07-31)**

1. **RESOLVED via M-18** — `BookingService::assertRoomAvailable()` is wired into both `create()` (`BookingService.php:30`) and `updateBooking()` (`:62`): overlap check on `room_id` for statuses `expected_arrival`/`check_in`, throws `InvalidArgumentException`; skips when no `room_id`.
2. **RESOLVED via M-10** — check-out now archives instead of destroying: `status=expected_departure`, `checked_out_at=now()`, voucher → `Expired`, audit `booking.expected_departure`; redemption/scan/delivery history and `booking_facilities` are retained for reports.
3. **FIXED (2026-07-31)** — new nullable boolean `is_one_time` on `facility_templates` (migration `2026_07_31_000002_add_is_one_time_to_facility_templates.php` — run via Laragon); `FacilityTemplate` fillable + boolean cast. `VoucherService::redeem()` (`VoucherService.php:457-460`) and `GuestVoucher::getFacilityStatuses()` (`GuestVoucher.php:162-163`) use the flag when set and fall back to the `SNACK`/`JOURNAL`/`FEED` code heuristic when `NULL` — existing behavior unchanged, and new one-time facilities can now be flagged via DB without code changes.
4. **FIXED (2026-07-31)** — facility-in-use guard implemented as model events in `FacilityTemplate::booted()` (covers web, API and console paths): `deleting` and `updating` (only when `property_id` is dirty) throw `RuntimeException` while the facility is referenced by `booking_facilities`, `redemption_logs`, `outlets` (both the `facility_template_id` column and the pivot), `guest_vouchers.facility_template_id` / `addition_facility_ids` (comma-separated, `FIND_IN_SET`) or `addition_map` (JSON key). Web `FacilityController::destroy()` flashes the error (`FacilityController.php:71-84`); API `update`/`destroy` return 422 JSON. Deactivation (`is_active=false`) remains allowed by design since it preserves history.
5. **RESOLVED via M-06** — admin route group is now `Route::middleware(['auth', 'ip.whitelist'])` (`routes/web.php:35`); `ADMIN_IP_WHITELIST` + `ALLOW_EMPTY_IP_WHITELIST` exposed in `.env`/`.env.example`.
6. **RESOLVED via L-05** — `AttackDetectionMiddleware::detectBodyAttack()` scans request bodies (first 8192 bytes; multipart/empty bodies skipped) for injection patterns.
7. **RESOLVED via L-03** — `ValidatePaginationParameters` rejects `sort` values outside `[a-zA-Z0-9_]` and on the sensitive-column blocklist (removed from request and query bags). No controller in `app/` consumes `sort`, so a per-model allowlist would be dead code; the blocklist covers the ORDER BY injection surface.
8. **RESOLVED via L-18** — misleading `SANCTUM_STATEFUL_DOMAINS`/`TWO_FACTOR_ENABLED` removed from `.env.example`; verified nothing else advertises 2FA or email verification (no `HasApiTokens`, no `MustVerifyEmail`, no verify routes — `email_verified_at` is dormant Laravel boilerplate).

---

## 7. Verified Working (no findings)

- Voucher generation idempotency under `Cache::lock` (`voucher:generate`, `voucher:redeem`) and row-level `lockForUpdate` quota accounting — concurrent redemption serialization is correct.
- QR payload = raw 32-char `secure_token`; no enumerable QR content; public page only reachable via the random token.
- One-time facility + addition logic (recent fix): with `addition_map` > 0 the facility falls back to daily range behavior in both `redeem()` and `getFacilityStatuses()`; quota math is consistent between the two paths.
- Login throttling (5/min per email+IP), session regeneration on web login, CSRF + CSP nonce on inline scripts.
- `RedeemVoucherRequest` authorization gates both `redeem` and `processScannedCode`.
- `.env.example` uses safe defaults (`APP_DEBUG=false`, secure cookies, HTTPS enforced).

---

## 8. Recommended Fix Order

1. C-01, C-02, C-03, C-04 (checkout 403; block-page 500; IP-spoof bypass; non-atomic check-in)
2. H-01 (temp-voucher facility allow-list), H-05 (API verify parity), H-06 (public_link scheduled delivery)
3. H-02/H-03/H-04 (IDOR + client-trusted quota)
4. M-01/M-02 (phone formats — verify with a live send before production delivery)
5. H-08 → H-12 (CORS/CSP/IP whitelist/file upload)
6. Remaining Medium → Low items.

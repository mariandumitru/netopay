# Changelog

All notable changes to `netopay` will be documented in this file.

## Fixed statuses - 2026-11-04

### Fixed

- Unrecognised or missing NETOPIA status codes no longer map to `Failed`. They map to the new `PaymentStatus::Unknown`; the IPN fires no payment
  event and logs a warning with the raw code.
- In-progress codes (1, 2, 6, 14, 18) map to `PaymentStatus::Pending` instead of `Failed`. A hosted-page start (status 1) is now `Pending`.

### Changed

- The IPN fires `NetopiaPaymentPending` for `Pending` as well as `Awaiting3DS`. Check `$event->status->state` to tell them apart.
- `NetopiaPaymentFailed` fires only for codes 11, 12 and 13.

### Added

- `PaymentStatus::Unknown` case.
- `PaymentStatusDto::$providerStatusCode`: the raw NETOPIA code, `null` when missing.
- New `NetopiaStatus` cases: `New`, `Opened`, `Pending`, `Error`, `Fraud`, `PendingAuth`, `PendingAny`.

### Upgrading

- A `match` on `PaymentStatus` without a `default` arm must handle `Unknown`.
- An unknown status no longer fires `NetopiaPaymentFailed`.

## Bug fixes - 2026-11-04

### What's Changed

* bugs fixing by @mariandumitru in https://github.com/mariandumitru/netopay/pull/10

**Full Changelog**: https://github.com/mariandumitru/netopay/compare/1.0.2...1.0.3

## 1.0.2 - 2026-11-04

Fixed the changelog workflow failing when a release has no title.

## v1.0.1 - 2026-11-04

Bug fixes

## PHP 8.5 & Laravel 12/13 support - 2026-11-04

### Breaking changes

- Requires PHP 8.5+ (was 8.2+)
- Requires Laravel 12.69+ or 13.30+ — Laravel 11 support dropped

### Changes

- Dev tooling upgraded: Pest 5 (Pest 4 still supported for Laravel 12), Testbench 11, Larastan 3.14, Pint 1.32
- Added GitHub Actions test workflow plus PHPStan and Pint checks
- Code style updated to current Pint rules (no behaviour changes)

## Widened compatible Laravel versions - 2026-05-27

Widened compatible Laravel versions

# AGENTS.md

Guidance for AI coding agents (Claude Code, Codex, Junie, Cursor) working in this repository.

## What this is

**IP Blocker** (`Wobqqq.FortifyIpBlocker`) is a paid module of the Fortify security suite for October CMS 3.x/4.x (built and tested against 4.4 on Laravel 12, PHP 8.2+). It refuses every request from the IP addresses and subnets (IPv4 and IPv6, CIDR notation) the administrator lists, on the site and in the backend, answering 403 with the page the administrator chose.

It requires the core plugin [`Wobqqq.Fortify`](https://github.com/wobqqq/oc-fortify-plugin): the settings live in the core's `Wobqqq\Fortify\Models\Fortify` record under the `ip_firewall.ip_blocker_*` key and appear on **Settings → Fortify**, and the module draws its own item on the core's dashboard widget.

This is a **security product installed on production sites**. A bug here locks administrators or visitors out, or silently leaves a site unprotected. Security and safe upgrades come before everything else.

## The self-check gate (run before every commit)

Everything runs in Docker; the host needs no PHP.

```bash
make install        # composer install inside the php container (the core comes from its GitHub main branch)
make code.fix       # composer normalize, rector, php-cs-fixer
make code.check     # validate, normalize --dry-run, audit, php -l, yaml-lint, cs, rector, PHPStan max
make test           # Pest
make test.coverage  # Pest with pcov, fails below 90 %
make ready          # all of the above
```

`make ready` must pass. PHPStan runs at `level: max` with strict rules and **no baseline**: fix the type, never add an ignore. Advisories reported by `composer audit` are fixed by updating the package, never ignored.

## How the code is laid out

| Path | Holds |
|------|-------|
| `Plugin.php` | Wiring: console commands, the `ip_blocker_current_ip` validation rule, the listener, the middleware. |
| `services/IpBlockerService.php` | The block list check, the middleware registration and the console actions. |
| `http/middlewares/IpBlockerMiddleware.php` | Runs the check on every request of `cms.middleware_group` and `backend.middleware_group`. |
| `transformers/FortifyTransformer.php` | Turns the stored list into `IpBlockerDto` (exact addresses and subnets), skipping broken rows. |
| `cache/, instances/` | The cached DTO (cleared on every settings save) and its per-request memo. |
| `listeners/FortifyListener.php` | The settings form fields, their validation rules and defaults, the dashboard item, dropping empty rows on save, the cache clearing. |
| `validator/rules/IpBlockerCurrentIpRule.php` | Refuses a list that blocks the administrator saving it, by address or by subnet. |
| `console/` | `remove-ip` and `disable`, the recovery path. |
| `updates/version.yaml` | The version history the marketplace reads from `main`. |

### Working with the core

- The core is a separate plugin that sites update on their own schedule. Use only the core's public API (listed in the core's AGENTS.md: the `Fortify` settings model, `FortifyEvent`, `View`, `WidgetItemColor`, the widget DTOs and `FortifyTransformer::widget*Dto()`, `BasicCache::cacheKey()`/`TTL`). A new core API is used only behind a check (`method_exists`, `enum_exists`) with a fallback, so the module keeps working on every released core.
- Settings are validated by rules the module adds to the core model. Add them in `Fortify::extend()` **and** when the settings form is built: the settings instance may exist before the module extends the model.
- Caches are cleared on the `eloquent.saved` / `eloquent.deleted` events of the core model, never with `bindEvent()` on an instance, for the same reason.

## Upgrading installed sites safely

Read the `plugin-upgrades` skill before changing anything that reaches a site that already runs the module: a new version in `updates/version.yaml` for every shipped change, an update script for every change to what is stored, a new cache key for every change to a cached object's shape, and defaults that cannot lock anyone out.

## Security rules (always)

Read the `fortify-security` skill for the full checklist. For this module in particular:

- The IP is `Request::ip()`: behind a proxy or a CDN it is the proxy's unless October's trusted proxies are configured. Never read `X-Forwarded-For` yourself.
- Addresses are compared with `IpUtils::checkIp()`, never as strings: an IPv6 address has many spellings and a subnet covers many addresses.
- A block list is not a firewall: an attacker changes address. It stops a known source; the web server or a WAF stops traffic before PHP runs.
- Every request of every visitor runs the check: it reads one cached object, never the database.
- The administrator saving the list must not be able to block themselves (the validation rule): keep that true for every new way of listing addresses.

Recovery from the console, for an administrator who locked themselves out:

- `php artisan wobqqq.fortify:ip-blocker:remove-ip {ip}` — removes an address from the block list.
- `php artisan wobqqq.fortify:ip-blocker:disable` — turns the module off.

## Tests

Pest 4 on Orchestra Testbench (Laravel 12) with the real `october/rain` and the core plugin from Composer. The licensed October modules are not installable in CI, so `tests/Stubs/October.php` reproduces the classes the plugins touch with October 4.4's behaviour (keep it identical to the core's copy). `tests/TestCase.php` boots the core and the module like October does. Read the `plugin-testing` skill.

## Conventions

- `declare(strict_types=1);` in every PHP file; PSR-12 via php-cs-fixer (`(int)$x` without a space, imported classes).
- Code documents itself: names over comments. A comment explains a non-obvious *why*, in one sentence.
- DTOs are `final readonly`; services and transformers are `final`.
- October patterns over Laravel ones: model validation, form fields added in `backend.form.extendFields`, `Plugin.php` registration, the core's `lang` keys (read the `octobercms-*` skills).
- Commits: imperative subject saying what the change does for the site, a body with the why.

# IP Blocker

[![CI](https://github.com/wobqqq/oc-fortify-ip-blocker-plugin/actions/workflows/ci.yml/badge.svg)](https://github.com/wobqqq/oc-fortify-ip-blocker-plugin/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/wobqqq/fortifyipblocker-plugin)](https://packagist.org/packages/wobqqq/fortifyipblocker-plugin)
[![Downloads](https://img.shields.io/packagist/dt/wobqqq/fortifyipblocker-plugin)](https://packagist.org/packages/wobqqq/fortifyipblocker-plugin)
[![Marketplace](https://img.shields.io/badge/October%20CMS-Marketplace-e24848)](https://octobercms.com/plugin/wobqqq-fortifyipblocker)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)](https://github.com/wobqqq/oc-fortify-ip-blocker-plugin/blob/main/composer.json)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](https://github.com/wobqqq/oc-fortify-ip-blocker-plugin/blob/main/phpstan.neon.dist)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/wobqqq/oc-fortify-ip-blocker-plugin/blob/main/LICENSE.md)

**IP Blocker** allows administrators to manually block specific IP addresses directly from the admin panel.

It works as part of the [Fortify](https://octobercms.com/plugin/wobqqq-fortify) ecosystem and adds an extra layer of protection against malicious users.

## 📊 Security Dashboard Widget

Fortify includes a dashboard widget that gives you an overview of your application’s security status.

- Highlights critical vulnerabilities and misconfigurations
- Provides quick access to all security checks and tools
- Helps you identify and fix issues in one place

This widget acts as a central hub, allowing you to monitor and manage your application's security at a glance.

## 🚀 Features

- Manual blocking of IP addresses and subnets, on the site and in the backend
- Instant protection against malicious users
- Simple and intuitive interface
- Works together with [Smart IP Blocker](https://octobercms.com/plugin/wobqqq-fortifysmartipblocker) for automated protection

## 🔗 Related Plugins

- [Fortify](https://octobercms.com/plugin/wobqqq-fortify) – comprehensive security suite
- [Admin IP Access](https://octobercms.com/plugin/wobqqq-fortifyadminipaccess) – restrict admin panel access by IP
- [Smart IP Blocker](https://octobercms.com/plugin/wobqqq-fortifysmartipblocker) – automatic IP blocking based on request rate
- [Input Sanitizer](https://octobercms.com/plugin/wobqqq-fortifyinputsanitizer) – block and sanitize malicious input
- [CSP](https://octobercms.com/plugin/wobqqq-fortifycsp) – add Content Security Policy headers to prevent XSS

## 📦 Requirements

- PHP 8.2 or higher
- October CMS 3.x or 4.x
- [Fortify](https://octobercms.com/plugin/wobqqq-fortify)

## 📥 Installation

| From | How |
|---|---|
| **October CMS Marketplace** | [octobercms.com/plugin/wobqqq-fortifyipblocker](https://octobercms.com/plugin/wobqqq-fortifyipblocker), or **Settings → Updates & Plugins → Install plugins** in the backend and search for “Fortify IP Blocker” |
| **Artisan** | `php artisan plugin:install Wobqqq.FortifyIpBlocker` |
| **Composer** | `composer require wobqqq/fortifyipblocker-plugin` then `php artisan october:migrate` |

It needs the [Fortify](https://octobercms.com/plugin/wobqqq-fortify) core plugin: Composer installs it with the module; when installing from the marketplace, install **Fortify** first.

## 💻 Usage

All configuration and management is handled via the October CMS admin panel.

**Admin Panel:**
Navigate to `Settings -> Fortify` and enable **IP Blocker**. You can add or remove blocked IPs through the interface.

**Console Commands:**

- Remove a blocked IP:

```bash
php artisan wobqqq.fortify:ip-blocker:remove-ip {ip}
```

- Disable IP Blocker module:

```bash
php artisan wobqqq.fortify:ip-blocker:disable
```

## ⬆️ Upgrading

- **1.0.5** — internal refactoring. Nothing changes on an existing site.
- **1.0.4** — installing the module with Composer installs the Fortify core with it. Nothing changes on an existing site.
- **1.0.3** — an IPv6 address is blocked however it is written (`2001:db8::1` and `2001:0db8:0:0:0:0:0:1` are the same address). Saving a list is refused when one of its subnets covers your own address, as it already was for the address itself. The module's defaults are set even when another Fortify module set up the firewall settings first, and a saved list applies at once.

## ⚠️ Good to know

- Behind a load balancer, proxy or CDN, configure October's trusted proxies so that the visitor's IP, not the proxy's, is checked.
- Blocking addresses stops a known source, it does not replace a firewall: an attacker can change address, and traffic you never want to reach PHP is best refused by the web server.

## 🔒 Security

Please report a vulnerability privately, as described in [SECURITY.md](https://github.com/wobqqq/oc-fortify-ip-blocker-plugin/blob/main/SECURITY.md).

## 🛠️ Development

The toolchain runs in Docker, the host needs nothing but `docker` and `make`. The module is tested together with the [Fortify core](https://github.com/wobqqq/oc-fortify-plugin), which Composer installs from Packagist.

```bash
make install        # composer install
make code.fix       # composer normalize, Rector, PHP CS Fixer
make code.check     # composer validate/audit, php -l, YAML lint, PHP CS Fixer, Rector, PHPStan (level max)
make test.coverage  # Pest with coverage (90 % minimum)
make ready          # everything above
```

Every pull request runs the same checks on GitHub Actions, plus a syntax check on PHP 8.2 and a run against the latest core. Pushing a tag that matches the last version in `updates/version.yaml` publishes it as a GitHub release and to the October CMS marketplace once CI has passed.


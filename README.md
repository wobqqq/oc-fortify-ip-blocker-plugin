# IP Blocker

**IP Blocker** allows administrators to manually block specific IP addresses directly from the admin panel.

It works as part of the [Fortify](https://octobercms.com/plugin/wobqqq-fortify) ecosystem and adds an extra layer of protection against malicious users.

## 📊 Security Dashboard Widget

Fortify includes a built-in dashboard widget that gives you a real-time overview of your system’s security status.

- Highlights critical vulnerabilities and misconfigurations
- Provides quick access to all security checks and tools
- Helps you identify and fix issues in one place

This widget acts as a central hub, allowing you to monitor and manage your application's security at a glance.

## 🚀 Features

- Manual IP blocking
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
- October CMS 3.0 or higher

## 💻 Usage

All configuration and management is handled via the October CMS admin panel.

**Admin Panel:**
Navigate to `Settings -> Fortify` and enable **IP Blocker**. You can add or remove blocked IPs through the interface.

**Console Commands:**

- Remove a blocked IP:
-
```bash
php artisan wobqqq.fortify:ip-blocker:remove-ip {ip}
```

- Disable IP Blocker module:

```bash
php artisan wobqqq.fortify:ip-blocker:disable
```

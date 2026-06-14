---
name: Bug Report
about: Report a bug in laravel-navigation
title: "[Bug]: "
labels: bug
assignees: ''
---

### Description

A clear and concise description of the bug.

### Steps to Reproduce

Provide a minimal navigation config or builder call that reproduces the issue.

```php
// e.g. Item::make(...), nav_item(...), config/navigation.php, etc.
```

1. Configure '...'
2. Call '...'
3. See the error / unexpected output.

### Expected Behavior

Explain what you expected to happen (items rendered, breadcrumb shape, active state, etc.).

### Actual Behavior

What actually happened? Include stack traces, exception messages, or the actual `items()` / `breadcrumbs()` output.

```
// Paste stack trace / error output / unexpected array here
```

### Environment

- Package version: [e.g., 1.5.0]
- Laravel version: [e.g., 11.x, 12.x, 13.x]
- PHP version: [e.g., 8.3, 8.4, 8.5]
- Frontend stack (if relevant): [e.g., Inertia.js + React, Blade, Vue]
- OS: [e.g., macOS, Linux, Windows/WSL]

### Relevant Configuration

If applicable, share your `config/navigation.php`, runtime `Navigation::register(...)` calls, or the icon compilation setup.

### Additional Context

Add any other context about the problem here (Wayfinder usage, custom icon compiler, route parameters, etc.).

# Security Policy

## Supported versions

Security fixes are applied to the latest minor release line. Older minor versions may receive fixes for critical issues at the maintainers' discretion — when in doubt, please upgrade.

| Version       | Supported              |
| ------------- | ---------------------- |
| `1.5.x`       | ✅                     |
| `1.x` (older) | ⚠️ critical fixes only |
| `< 1.0`       | ❌ (please upgrade)    |

## Reporting a vulnerability

**Please do not open a public GitHub issue for security reports.**

Use [GitHub Security Advisories](https://github.com/offload-project/laravel-navigation/security/advisories/new) to report privately. This lets us discuss, fix, and coordinate disclosure before details become public.

When reporting, please include:

- A description of the issue and its potential impact.
- Steps to reproduce, or a minimal proof-of-concept.
- Affected package version(s), Laravel version, and PHP version.
- Any suggested fix or mitigation (optional).

## Response expectations

- **Acknowledgement:** within 5 business days.
- **Initial assessment:** within 10 business days.
- **Fix timeline:** depends on severity. Critical issues get prioritized; lower-severity issues may be batched into the next regular release.

We'll keep you updated on progress and credit you in the advisory unless you'd prefer to stay anonymous.

## Scope

Things in scope for this project:

- Vulnerabilities in any code published under `OffloadProject\Navigation\` (facade, manager, builder, item, icon compiler, console commands, Wayfinder adapter).
- Authorization bypass in the `can` / `visible` resolution path that would cause a navigation item to render for a user who shouldn't see it.
- SVG injection or XSS via the icon compiler — the package uses `enshrined/svg-sanitize`; bypasses are in scope.
- Information disclosure through breadcrumb generation (e.g., label closures leaking model data that shouldn't be visible to the current user).
- URL construction issues — open-redirect-style problems via the `url` / external link surface.
- Unsafe defaults in the published config.

Things **not** in scope (please report upstream or with the relevant project):

- Vulnerabilities in Laravel itself or other Composer dependencies — please file with the respective project.
- Issues in `enshrined/svg-sanitize` itself — report upstream.
- Application-level misconfiguration in a consuming app (e.g., wiring a navigation item to a route that doesn't gate access, or exposing privileged routes via `visible(true)`).
- Issues caused by user-supplied closures (`label`, `visible`, `can` callbacks) that leak data the closure itself fetched.
- Vulnerabilities in the host application's authentication, authorization layer, or frontend rendering of the items array.

## Disclosure

Once a fix is published, we will:

1. Publish a GitHub Security Advisory with details and credit.
2. Tag a patch release.
3. Update the changelog with a brief mention (without exploit details prior to the disclosure window).

Thanks for helping keep the project and its users safe.

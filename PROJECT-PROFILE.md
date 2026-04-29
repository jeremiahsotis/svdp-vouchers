# PROJECT PROFILE

## Project Name
SVdP Vouchers

## Purpose
WordPress plugin for St. Vincent de Paul voucher request, cashier redemption, furniture voucher, receipt, invoice, and conference workflow support.

## Architecture Type
- modular WordPress plugin

## Runtime Stack
- language: PHP, JavaScript
- framework: WordPress plugin APIs, WordPress REST API, jQuery
- DB: WordPress MySQL/MariaDB tables
- queue: none currently required
- cache: WordPress/runtime cache only
- provider(s): optional Monday.com sync

## Repo Strategy
- polyrepo/plugin repository deployed from the plugin folder

## Deployment Model
- Local by Flywheel development environment
- production deployment uses git commands from the plugin repository

## Environments
- dev: Local by Flywheel WordPress site
- test: local validation commands documented in `docs/governance/canonical-commands.json`
- staging: not formally defined in this repo
- prod: WordPress plugin deployment from this repository

## Canonical Commands Source
`docs/governance/canonical-commands.json`

## Environment Fidelity Notes
This repository has no `package.json` and does not require npm or pnpm for Slice G0 validation. WordPress commands are not assumed to be runnable directly from this plugin directory; Local by Flywheel manages the WordPress runtime and database access.

## Protected Surfaces
Protected surfaces are defined in:

- `contracts/protected-surfaces.json`
- `contracts/protected-contracts.json`
- `contracts/protected-surface-acceptance.json`

## Observability Stack
WordPress debug logs and Local by Flywheel logs are the current operational inspection surfaces. No dedicated observability service is required by Slice G0.

## Security Model
WordPress nonce verification, WordPress capabilities, role checks, and explicit protected-surface governance for future mutation and audit-sensitive work.

## Auth Model
Public voucher request endpoints remain intentionally public where documented. Cashier and admin actions rely on WordPress login state, roles, and capabilities.

## Policy Engine
Governance policies are document- and contract-driven through `MASTER-STANDARD.md`, this project profile, protected contracts, checkpoint files, and implementation briefs.

## Release Strategy
Changes are staged, validated, committed, and deployed from the plugin repository using git-based workflow. Protected-surface changes require implementation brief, checkpoint, bootstrap, and documented validation.

## Guardrail Auto-Detection Mode
- warn

## Risk Level
- high

## Special Constraints
- Do not introduce package.json, npm, or pnpm requirements unless a future approved slice explicitly changes repo tooling.
- Do not change voucher business logic, database schema, or runtime behavior during governance installation slices.
- Do not implement audit logs, voucher corrections, override-code redesign, catalog concurrency, dispatch, delivery attempts, tracking, drivers, or RouteShyft behavior in Slice G0.
- Preserve WordPress plugin runtime compatibility.

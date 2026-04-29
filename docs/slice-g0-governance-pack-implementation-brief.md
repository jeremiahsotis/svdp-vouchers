# Slice G0 - Governance Pack Installation

## Status

Authoritative implementation brief for Slice G0.

## Decision

Install the full `project-governance-pack-v5` governance pack into the `svdp-vouchers` WordPress plugin repository before any new authority, mutation, override-code, voucher-correction, catalog-concurrency, or audit-log work begins.

The governance pack must be adapted to this repository's actual constraints:

- WordPress plugin repo located at `wp-content/plugins/svdp-vouchers/`
- No `package.json`
- No pnpm/npm toolchain
- Existing `.github/workflows/pr-base-guard.yml` must be preserved
- Existing `AGENTS.md` must be preserved and extended, not replaced
- Deployment uses git commands directly in the plugin directory
- Governance files live at plugin repo root

## Problem

The next feature set introduces protected mutation and asynchronous/multi-user risks:

- manager override codes
- voucher corrections
- expiration-window changes
- eligibility-window changes
- DOB and household-size corrections
- catalog editing with future concurrency handling
- human-readable audit logging

Without governance installed first, these changes can create silent data mutation, unclear authority, roadmap drift, schema drift, and audit logs that are not trustworthy.

## Goal

Install governance infrastructure and bind it to this plugin so future slices are planned, executed, validated, and audited against explicit protected surfaces and repo-specific rules.

## Scope

This slice installs and adapts governance only.

Included:

1. Copy governance pack files into the plugin repo.
2. Preserve existing repo-specific files.
3. Extend `AGENTS.md` with governance authority and execution rules.
4. Create repo-specific protected surface contract files from governance templates.
5. Adapt canonical commands to this repo's no-Node environment.
6. Clean `.DS_Store` and ensure `.gitignore` blocks it.
7. Preserve `.github/workflows/pr-base-guard.yml`.
8. Install governance docs, standards, scripts, contracts, planning, and specs structure.
9. Create or adapt `PROJECT-PROFILE.md` from the governance template.
10. Validate that G0 is documentation/governance only.

## Out of Scope

Do not implement:

- voucher correction UI
- voucher correction backend
- audit event table
- override-code schema changes
- manager-code redesign
- catalog optimistic locking
- catalog audit logging
- RouteShyft, dispatch, delivery attempts, delivery tracking, or driver workflows
- package.json
- pnpm/npm scripts
- TypeScript execution as required validation

## Source Governance Pack

Use the provided `project-governance-pack-v5` file structure.

Expected source structure includes:

- `.editorconfig`
- `.gitattributes`
- `.markdownlint.json`
- `.codespellrc`
- `cspell.json`
- `.pre-commit-config.yaml`
- `AGENTS.template.md`
- `MASTER-STANDARD.md`
- `PROJECT-PROFILE-TEMPLATE.md`
- `config/`
- `contracts/`
- `docs/`
- `planning/`
- `scripts/`
- `specs/`
- `standards/`
- `.github/pull_request_template.md`
- `.github/workflows/*.yml`

## Required Target Files / Directories

At plugin repo root, G0 must result in:

```text
.editorconfig
.gitattributes
.markdownlint.json
.codespellrc
cspell.json
.pre-commit-config.yaml
AGENTS.md
MASTER-STANDARD.md
PROJECT-PROFILE.md
PROJECT-PROFILE-TEMPLATE.md

config/
contracts/
docs/
planning/
scripts/
specs/
standards/

.github/pull_request_template.md
.github/workflows/pr-base-guard.yml
.github/workflows/quality-check.yml
.github/workflows/execution-artifact-check.yml
.github/workflows/guardrails-check.yml
.github/workflows/ci.yml
```

## Preservation Rules

### AGENTS.md

Do not replace the existing `AGENTS.md`.

Instead:

1. Preserve all existing SVdP Vouchers project guidance.
2. Append a new governance section.
3. State that governance files are authoritative for future implementation work.
4. State that repo-specific instructions still apply where they are more specific.
5. State that business logic changes must not occur during G0.

### Existing PR Guard

Do not delete `.github/workflows/pr-base-guard.yml`.

The governance pack workflows may be added, but the existing PR base guard must remain intact.

If workflow names conflict, preserve both by filename.

### No Node Tooling Assumption

Do not create or require `package.json`.

The governance pack contains example pnpm commands and TypeScript scripts. In G0, these are installed for future use but not required for validation.

Validation must use repo-safe commands that work without Node.

## Repo-Specific Protected Surfaces

Create repo-specific protected contract files from the templates.

### `contracts/protected-surfaces.json`

Must register at minimum:

```json
{
  "protected_surfaces": [
    {
      "path": "includes/class-voucher.php",
      "type": "php",
      "reason": "Core voucher creation, duplicate detection, eligibility, expiration, notification, furniture request persistence, and cashier voucher formatting."
    },
    {
      "path": "includes/class-database.php",
      "type": "php",
      "reason": "Database schema, schema upgrades, protected data evolution, and migration-sensitive table definitions."
    },
    {
      "path": "includes/class-manager.php",
      "type": "php",
      "reason": "Manager override authority and override-code validation surface."
    },
    {
      "path": "includes/class-furniture-catalog.php",
      "type": "php",
      "reason": "Furniture catalog create/update/archive behavior and future multi-user catalog editing surface."
    },
    {
      "path": "includes/class-furniture-voucher.php",
      "type": "php",
      "reason": "Furniture fulfillment workflow, item completion/cancellation/substitution, document generation, and cashier mutation paths."
    },
    {
      "path": "public/js/voucher-request.js",
      "type": "javascript",
      "reason": "Public voucher request state model, catalog selection state, pricing summary, delivery address verification, and submission payload."
    },
    {
      "path": "public/js/cashier-shell.js",
      "type": "javascript",
      "reason": "Cashier mutation flow, override modal behavior, furniture item actions, session keepalive, and REST submission handling."
    },
    {
      "path": "public/templates/voucher-request-form.php",
      "type": "php-template",
      "reason": "Public request-form contract for neighbor, voucher type, furniture catalog, pricing, delivery, and redemption-rule display."
    },
    {
      "path": "public/templates/cashier/partials/voucher-detail-furniture.php",
      "type": "php-template",
      "reason": "Cashier furniture detail contract, delivery verification display, item resolution, and voucher completion UI."
    },
    {
      "path": "public/templates/documents/furniture-receipt.php",
      "type": "php-template",
      "reason": "Neighbor-facing furniture receipt contract and redemption-rule display."
    },
    {
      "path": "includes/class-invoice.php",
      "type": "php",
      "reason": "Conference invoice generation and financial document integrity."
    }
  ]
}
```

### `contracts/protected-contracts.json`

Must define contract categories for:

- voucher identity
- voucher eligibility
- voucher expiration
- voucher correction authority
- manager override authority
- audit logging
- furniture catalog item data
- pricing display and calculation
- receipt/invoice document generation
- delivery address verification

### `contracts/protected-surface-acceptance.json`

Must define acceptance checks for future protected changes:

- protected surface named
- change reason documented
- before/after impact described
- migration impact assessed if schema affected
- audit impact assessed if mutation behavior affected
- rollback path documented
- validation commands listed
- checkpoint updated

## Canonical Commands Adaptation

The governance pack's `docs/governance/canonical-commands.json` may reference pnpm.

For this repo, adapt it to a no-Node WordPress plugin baseline.

Required commands:

```json
{
  "commands": {
    "repo_status": "git status --short",
    "recent_commits": "git log --oneline -n 10",
    "php_syntax_check": "find includes public admin -name '*.php' -print0 | xargs -0 -n1 php -l",
    "python_scripts_compile": "python3 -m py_compile scripts/*.py",
    "governance_placeholder_check": "python3 scripts/check_no_placeholders.py",
    "ds_store_check": "find . -name '.DS_Store' -print",
    "protected_surface_search": "grep -RIn -e 'class SVDP_Voucher' -e 'class SVDP_Database' -e 'class SVDP_Manager' includes public admin",
    "schema_mutation_search": "grep -RIn -e 'CREATE TABLE' -e 'ALTER TABLE' -e 'dbDelta' includes",
    "voucher_mutation_search": "grep -RIn -e '$wpdb->update' -e 'UPDATE .*svdp_vouchers' -e 'status.*Redeemed' includes",
    "forbidden_delivery_scope_search": "grep -RIn -e 'delivery attempt' -e 'dispatch' -e 'driver' -e 'RouteShyft' includes public admin docs || true"
  }
}
```

Do not require npm/pnpm commands until a future slice explicitly introduces package tooling.

## `.DS_Store` Hygiene

G0 must:

1. Add `.DS_Store` to `.gitignore`.
2. Remove existing `.DS_Store` files.
3. Confirm none remain with:

```bash
find . -name ".DS_Store" -print
```

## Governance Binding in `AGENTS.md`

Append a section similar to:

```md
## Governance Pack Authority

This repository uses the governance pack installed at repo root.

Authoritative governance sources include:

- `MASTER-STANDARD.md`
- `PROJECT-PROFILE.md`
- `contracts/protected-surfaces.json`
- `contracts/protected-contracts.json`
- `contracts/protected-surface-acceptance.json`
- `docs/governance/canonical-commands.json`
- `docs/data-governance/migration-policy.md`
- `docs/architecture/concurrency-model.md`
- `docs/security/access-audit-model.md`
- `standards/execution/implementation-brief-contract.md`
- `standards/execution/checkpoint-verification-contract.md`
- `standards/execution/bootstrap-execution-contract.md`

Rules:

1. Do not modify protected surfaces without an implementation brief, checkpoint, and bootstrap.
2. Do not make schema changes without following `docs/data-governance/migration-policy.md`.
3. Do not introduce protected mutation without audit planning.
4. Do not introduce async/multi-user behavior without checking `docs/architecture/concurrency-model.md`.
5. Do not implement business logic during governance installation slices.
6. Preserve WordPress plugin runtime compatibility.
```

## Validation Commands

After implementation, run:

```bash
git status --short
git log --oneline -n 10

find . -name ".DS_Store" -print

python3 -m py_compile scripts/*.py

python3 scripts/check_no_placeholders.py || true

python3 - <<'PY'
import json
from pathlib import Path
for path in [
    "contracts/protected-surfaces.json",
    "contracts/protected-contracts.json",
    "contracts/protected-surface-acceptance.json",
    "docs/governance/canonical-commands.json",
    "cspell.json",
    ".markdownlint.json",
]:
    with open(path, "r", encoding="utf-8") as f:
        json.load(f)
    print(f"valid json: {path}")
PY

test -f MASTER-STANDARD.md
test -f PROJECT-PROFILE.md
test -f AGENTS.md
test -f contracts/protected-surfaces.json
test -f docs/governance/canonical-commands.json
test -f docs/data-governance/migration-policy.md
test -f docs/architecture/concurrency-model.md
test -f docs/security/access-audit-model.md
test -f standards/execution/implementation-brief-contract.md
test -f .github/workflows/pr-base-guard.yml
```

## Acceptance Criteria

G0 is complete when:

- Governance pack files exist at repo root.
- Existing SVdP-specific `AGENTS.md` content is preserved.
- `AGENTS.md` declares governance authority.
- Existing PR base guard remains present.
- `.DS_Store` is ignored and removed.
- Repo-specific protected surfaces are registered.
- Canonical commands are adapted for no package tooling.
- JSON files parse.
- Python governance scripts compile.
- No voucher business logic changed.
- No schema changed.
- No runtime behavior changed.

## Next Slice

After G0 passes, proceed to G1:

`Slice G1 - Governance Binding and Protected Surface Baseline`

G1 should verify that the governance pack is not merely present, but actually describes this repo accurately enough to govern Slice 8.

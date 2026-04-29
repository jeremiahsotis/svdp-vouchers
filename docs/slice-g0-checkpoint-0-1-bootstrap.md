# Bootstrap - Slice G0 Governance Pack Install

## Authoritative Execution Sources

1. `docs/slice-g0-governance-pack-implementation-brief.md`
2. `docs/slice-g0-checkpoint-0-1-governance-pack-install.md`
3. `project-governance-pack-v5/MASTER-STANDARD.md`
4. `project-governance-pack-v5/AGENTS.template.md`
5. `project-governance-pack-v5/PROJECT-PROFILE-TEMPLATE.md`
6. `project-governance-pack-v5/contracts/protected-surfaces.template.json`
7. `project-governance-pack-v5/contracts/protected-contracts.template.json`
8. `project-governance-pack-v5/contracts/protected-surface-acceptance.template.json`
9. `project-governance-pack-v5/docs/governance/canonical-commands.json`
10. `project-governance-pack-v5/docs/data-governance/migration-policy.md`
11. `project-governance-pack-v5/docs/architecture/concurrency-model.md`
12. `project-governance-pack-v5/docs/security/access-audit-model.md`
13. `project-governance-pack-v5/standards/execution/implementation-brief-contract.md`
14. `project-governance-pack-v5/standards/execution/checkpoint-verification-contract.md`
15. `project-governance-pack-v5/standards/execution/bootstrap-execution-contract.md`
16. Existing repo `AGENTS.md`
17. Existing repo `.github/workflows/pr-base-guard.yml`

## Task

Install and bind `project-governance-pack-v5` into the `svdp-vouchers` WordPress plugin repo as Slice G0.

## Repository Context

Repo root is:

```text
wp-content/plugins/svdp-vouchers/
```

This repo:

- is a WordPress plugin
- has no `package.json`
- has no pnpm/npm enforcement
- is deployed using git commands directly from the plugin folder
- already has `AGENTS.md`
- already has `.github/workflows/pr-base-guard.yml`
- currently has `.DS_Store` that must be cleaned

## Hard Rules

Do not:

- change voucher business logic
- change database schema
- change runtime behavior
- create `package.json`
- require npm
- require pnpm
- replace existing `AGENTS.md`
- delete existing `.github/workflows/pr-base-guard.yml`
- implement audit logs
- implement override-code redesign
- implement voucher corrections
- implement catalog concurrency
- implement delivery attempts, dispatch, tracking, drivers, or RouteShyft behavior

## Required Steps

### Step 1 - Confirm clean starting state

Run:

```bash
git status --short
git log --oneline -n 10
```

If there are unrelated modified runtime files, stop and report.

### Step 2 - Copy governance pack files

Copy the governance pack into the repo root.

Preserve structure:

```text
config/
contracts/
docs/
planning/
scripts/
specs/
standards/
.github/
```

Copy root files:

```text
.editorconfig
.gitattributes
.markdownlint.json
.codespellrc
cspell.json
.pre-commit-config.yaml
MASTER-STANDARD.md
PROJECT-PROFILE-TEMPLATE.md
```

Create:

```text
PROJECT-PROFILE.md
```

from:

```text
PROJECT-PROFILE-TEMPLATE.md
```

and adapt it to `svdp-vouchers`.

### Step 3 - Preserve and extend AGENTS.md

Do not replace `AGENTS.md`.

Append a section titled:

```md
## Governance Pack Authority
```

Include:

```md
This repository uses the governance pack installed at repo root. For future implementation work, governance files are authoritative unless a repo-specific instruction in this file is more specific.

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
4. Do not introduce asynchronous or multi-user behavior without checking `docs/architecture/concurrency-model.md`.
5. Do not implement business logic during governance installation slices.
6. Preserve WordPress plugin runtime compatibility.
7. This repo does not currently require package.json, npm, or pnpm.
```

### Step 4 - Preserve PR base guard

Ensure this file still exists:

```text
.github/workflows/pr-base-guard.yml
```

Do not replace it.

If governance pack workflows are copied, preserve them alongside it.

### Step 5 - Create repo-specific protected contract files

Use the template files as shape references, but create real files:

```text
contracts/protected-surfaces.json
contracts/protected-contracts.json
contracts/protected-surface-acceptance.json
```

#### `contracts/protected-surfaces.json`

Use this repo-specific content:

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
      "path": "includes/class-invoice.php",
      "type": "php",
      "reason": "Conference invoice generation and financial document integrity."
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
    }
  ]
}
```

#### `contracts/protected-contracts.json`

Create this content:

```json
{
  "protected_contracts": [
    {
      "name": "voucher_identity",
      "surfaces": ["includes/class-voucher.php"],
      "rules": [
        "Neighbor identity fields must remain traceable.",
        "DOB changes must be audited in future correction workflows.",
        "Original values must not be silently overwritten by privileged corrections."
      ]
    },
    {
      "name": "voucher_eligibility",
      "surfaces": ["includes/class-voucher.php", "includes/class-database.php"],
      "rules": [
        "90-day eligibility behavior is protected.",
        "Eligibility override behavior must be explicit and audited.",
        "Eligibility changes must not erase original voucher history."
      ]
    },
    {
      "name": "voucher_expiration",
      "surfaces": ["includes/class-voucher.php"],
      "rules": [
        "30-day redemption expiration behavior is protected.",
        "Expiration correction must be explicit and audited.",
        "Expired-to-active transitions must require override authority."
      ]
    },
    {
      "name": "manager_override_authority",
      "surfaces": ["includes/class-manager.php", "public/js/cashier-shell.js"],
      "rules": [
        "Override code authority is separate from the logged-in operator identity.",
        "Override codes must be hashed when redesigned.",
        "Override use must capture both logged-in user and manager authority."
      ]
    },
    {
      "name": "audit_logging",
      "surfaces": ["includes/class-voucher.php", "includes/class-furniture-catalog.php", "includes/class-database.php"],
      "rules": [
        "Protected mutation must create a human-readable audit event.",
        "Audit events must include before, after, actor, override authority when applicable, reason, and timestamp.",
        "Audit logs must be understandable without reading raw JSON."
      ]
    },
    {
      "name": "furniture_catalog_data",
      "surfaces": ["includes/class-furniture-catalog.php", "public/js/voucher-request.js"],
      "rules": [
        "Catalog item changes must preserve request-form stability.",
        "Future catalog multi-user editing must include conflict handling.",
        "Catalog edits must be audited."
      ]
    },
    {
      "name": "pricing_calculation_display",
      "surfaces": ["includes/class-voucher.php", "public/js/voucher-request.js", "includes/class-invoice.php"],
      "rules": [
        "Maximum Conference commitment language is protected.",
        "User-facing pricing must not reintroduce 'may vary' language.",
        "Invoice generation must use actual fulfilled prices where applicable."
      ]
    },
    {
      "name": "receipt_invoice_generation",
      "surfaces": ["public/templates/documents/furniture-receipt.php", "includes/class-invoice.php", "includes/class-furniture-voucher.php"],
      "rules": [
        "Neighbor receipt must not show prices.",
        "Conference invoice must preserve financial integrity.",
        "Generated document links must remain tied to completed vouchers."
      ]
    },
    {
      "name": "delivery_address_verification",
      "surfaces": ["public/js/voucher-request.js", "includes/class-voucher.php", "public/templates/cashier/partials/voucher-detail-furniture.php"],
      "rules": [
        "Unverified delivery address must warn but not block.",
        "Delivery verification must not introduce dispatch or routing logic.",
        "Delivery attempts and RouteShyft behavior remain out of scope."
      ]
    }
  ]
}
```

#### `contracts/protected-surface-acceptance.json`

Create this content:

```json
{
  "acceptance_required_for_protected_surface_changes": [
    "implementation_brief_exists",
    "checkpoint_exists",
    "bootstrap_exists",
    "protected_surface_named",
    "change_reason_documented",
    "before_after_impact_described",
    "migration_impact_assessed_if_schema_affected",
    "audit_impact_assessed_if_mutation_behavior_affected",
    "concurrency_impact_assessed_if_async_or_multi_user_behavior_affected",
    "rollback_path_documented",
    "validation_commands_listed",
    "checkpoint_updated"
  ]
}
```

### Step 6 - Adapt canonical commands

Overwrite or adapt:

```text
docs/governance/canonical-commands.json
```

Use no-Node commands:

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
  },
  "notes": {
    "package_tooling": "This WordPress plugin repo does not currently use package.json, npm, or pnpm. Node-based governance validators are installed from the governance pack but are not required in Slice G0 validation."
  }
}
```

### Step 7 - Clean `.DS_Store`

Ensure `.gitignore` includes:

```text
.DS_Store
```

Run:

```bash
find . -name ".DS_Store" -delete
```

### Step 8 - Validate

Run:

```bash
git status --short
git log --oneline -n 10
find . -name ".DS_Store" -print
python3 -m py_compile scripts/*.py
```

Run JSON validation:

```bash
python3 - <<'PY'
import json
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
```

Run protected runtime diff check:

```bash
git diff -- includes public admin svdp-vouchers.php
```

Expected:

```text
No business logic changes.
```

### Step 9 - Complete checkpoint

Update:

```text
docs/slice-g0-checkpoint-0-1-governance-pack-install.md
```

Mark PASS / FAIL items.

### Step 10 - Commit

Commit only after validation passes.

Suggested commit message:

```text
Install governance pack baseline
```

## Expected Output Back to User

Return:

```text
G0 status:
- PASS / FAIL / BLOCKED

Files changed:
<summary>

Validation output:
<commands + result>

Notes:
<any deviations>
```

## Completion Rule

Do not proceed to Slice 8 until G0 checkpoint passes.

# Checkpoint G0.1 - Governance Pack Installed

## Purpose

Verify that the full governance pack is installed in the `svdp-vouchers` WordPress plugin repo and adapted to this repo without introducing runtime, schema, or business-logic changes.

## Authoritative Sources

- `docs/slice-g0-governance-pack-implementation-brief.md`
- `MASTER-STANDARD.md`
- `PROJECT-PROFILE.md`
- `AGENTS.md`
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

## Required Verification

### A. File Presence

Mark each item PASS / FAIL.

- [x] `.editorconfig` exists
- [x] `.gitattributes` exists
- [x] `.markdownlint.json` exists
- [x] `.codespellrc` exists
- [x] `cspell.json` exists
- [x] `.pre-commit-config.yaml` exists
- [x] `MASTER-STANDARD.md` exists
- [x] `PROJECT-PROFILE.md` exists
- [x] `PROJECT-PROFILE-TEMPLATE.md` exists
- [x] `config/cspell/domain-terms.txt` exists
- [x] `config/codespell/ignore-words.txt` exists
- [x] `contracts/protected-surfaces.json` exists
- [x] `contracts/protected-contracts.json` exists
- [x] `contracts/protected-surface-acceptance.json` exists
- [x] `contracts/errors/error-taxonomy.md` exists
- [x] `contracts/system/refusal-envelope.md` exists
- [x] `docs/governance/canonical-commands.json` exists
- [x] `docs/governance/risk-register.md` exists
- [x] `docs/governance/release-readiness-checklist.md` exists
- [x] `docs/governance/slice-archival-policy.md` exists
- [x] `docs/governance/planning-approval-policy.md` exists
- [x] `docs/data-governance/migration-policy.md` exists
- [x] `docs/data-governance/backward-compatibility.md` exists
- [x] `docs/data-governance/data-evolution-log.md` exists
- [x] `docs/architecture/concurrency-model.md` exists
- [x] `docs/security/access-audit-model.md` exists
- [x] `docs/environment/environment-contract.md` exists
- [x] `docs/environment/environment-matrix.md` exists
- [x] `docs/environment/config-governance.md` exists
- [x] `docs/operations/safe-mode.md` exists
- [x] `docs/decisions/current-state.md` exists
- [x] `docs/decisions/architecture-snapshot.md` exists
- [x] `docs/decisions/active-invariants.md` exists
- [x] `docs/decisions/active-constraints.md` exists
- [x] `docs/state/repo-digest.md` exists
- [x] `docs/state/change-impact-log.md` exists
- [x] `planning/` exists
- [x] `scripts/` exists
- [x] `specs/slice-template/` exists
- [x] `standards/` exists
- [x] `.github/pull_request_template.md` exists
- [x] `.github/workflows/pr-base-guard.yml` still exists
- [x] `.github/workflows/quality-check.yml` exists
- [x] `.github/workflows/execution-artifact-check.yml` exists
- [x] `.github/workflows/guardrails-check.yml` exists
- [x] `.github/workflows/ci.yml` exists

### B. AGENTS.md Preservation and Binding

- [x] Existing SVdP Vouchers guidance remains present
- [x] Existing Local by Flywheel / WordPress context remains present
- [x] Existing architecture notes remain present
- [x] New governance authority section exists
- [x] `MASTER-STANDARD.md` is referenced
- [x] `PROJECT-PROFILE.md` is referenced
- [x] Protected surface contracts are referenced
- [x] Data governance and migration policy are referenced
- [x] Concurrency model is referenced
- [x] Access audit model is referenced
- [x] G0 explicitly forbids runtime/business/schema changes

### C. Protected Surfaces

`contracts/protected-surfaces.json` must include at least:

- [x] `includes/class-voucher.php`
- [x] `includes/class-database.php`
- [x] `includes/class-manager.php`
- [x] `includes/class-furniture-catalog.php`
- [x] `includes/class-furniture-voucher.php`
- [x] `includes/class-invoice.php`
- [x] `public/js/voucher-request.js`
- [x] `public/js/cashier-shell.js`
- [x] `public/templates/voucher-request-form.php`
- [x] `public/templates/cashier/partials/voucher-detail-furniture.php`
- [x] `public/templates/documents/furniture-receipt.php`

Each protected surface must include:

- [x] path
- [x] type
- [x] reason

### D. Protected Contracts

`contracts/protected-contracts.json` must include contract categories for:

- [x] voucher identity
- [x] voucher eligibility
- [x] voucher expiration
- [x] voucher correction authority
- [x] manager override authority
- [x] audit logging
- [x] furniture catalog item data
- [x] pricing calculation/display
- [x] receipt/invoice generation
- [x] delivery address verification

### E. Canonical Commands

`docs/governance/canonical-commands.json` must be adapted to this repo and include:

- [x] `git status --short`
- [x] `git log --oneline -n 10`
- [x] PHP syntax check using `php -l`
- [x] Python governance script compile check
- [x] `.DS_Store` search
- [x] protected surface search
- [x] schema mutation search
- [x] voucher mutation search
- [x] forbidden delivery scope search

It must not require:

- [x] package.json
- [x] npm
- [x] pnpm

### F. Repo Hygiene

- [x] `.DS_Store` is included in `.gitignore`
- [x] no `.DS_Store` files remain
- [x] no governance file was placed outside plugin repo root
- [x] no generated temp folders are committed
- [x] no runtime plugin files changed except `AGENTS.md` and governance files

### G. Runtime Safety

Confirm no G0 changes were made to:

- [x] `includes/class-voucher.php`
- [x] `includes/class-database.php`
- [x] `includes/class-manager.php`
- [x] `includes/class-furniture-catalog.php`
- [x] `includes/class-furniture-voucher.php`
- [x] `includes/class-invoice.php`
- [x] `public/js/voucher-request.js`
- [x] `public/js/cashier-shell.js`
- [x] `public/templates/voucher-request-form.php`
- [x] `public/templates/cashier/partials/voucher-detail-furniture.php`
- [x] `public/templates/documents/furniture-receipt.php`

Exception:

- Only documentation references or comments are allowed if explicitly required, but the default expectation is no changes.

### H. Validation Commands

Run:

```bash
git status --short
git log --oneline -n 10
```

Run:

```bash
find . -name ".DS_Store" -print
```

Expected:

```text
no output
```

Run:

```bash
python3 -m py_compile scripts/*.py
```

Expected:

```text
no fatal compile errors
```

Run:

```bash
python3 scripts/check_no_placeholders.py || true
```

Expected:

```text
Either PASS or known placeholder findings documented in checkpoint notes.
```

Run:

```bash
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
```

Expected:

```text
valid json output for each listed file
```

Run:

```bash
git diff -- includes public admin svdp-vouchers.php
```

Expected:

```text
no business logic diff
```

Run:

```bash
grep -RIn -e "pnpm" -e "npm" docs/governance/canonical-commands.json .github/workflows/*.yml
```

Expected:

```text
Any pnpm/npm references are either examples from governance pack or documented as not required for G0.
No required G0 validation path depends on npm/pnpm.
```

## PASS Criteria

Checkpoint G0.1 passes only if:

- all required governance files exist
- `AGENTS.md` is preserved and extended
- repo-specific protected surfaces are registered
- canonical commands are adapted for no package tooling
- `.DS_Store` is removed and ignored
- JSON files parse
- Python scripts compile
- existing PR guard is preserved
- no runtime behavior changed
- no schema changed
- no voucher logic changed

## FAIL Conditions

Checkpoint fails if any of these occur:

- `AGENTS.md` is replaced instead of extended
- existing PR guard is removed
- G0 changes voucher logic
- G0 changes schema
- G0 creates package.json
- G0 requires npm/pnpm
- `.DS_Store` remains
- protected surfaces are missing
- canonical commands remain generic and unusable for this repo
- governance files are installed outside plugin root

## Result

- [x] PASS
- [ ] FAIL
- [ ] BLOCKED

## Notes

Record any deviations here:

```text
Deviation: `python3 scripts/check_no_placeholders.py || true` reports `Placeholder found in MASTER-STANDARD.md: placeholder`.
Reason: The copied governance pack standard contains the literal term in the G0 hardening requirement text.
Risk: Low for Slice G0; this is source governance documentation text, not an unfilled implementation placeholder.
Mitigation: Documented as a known placeholder finding; no runtime files changed.
Follow-up slice: Harden reusable validators if future governance slices require zero literal placeholder terms in pack standards.

Deviation: Copied governance workflow examples reference npm installs.
Reason: The full governance pack includes Node-oriented reference workflows, while this WordPress plugin has no `package.json`.
Risk: Low for Slice G0 validation because canonical commands explicitly do not require npm or pnpm.
Mitigation: `docs/governance/canonical-commands.json` is adapted to no-Node validation and documents package tooling as not required.
Follow-up slice: Adapt or disable Node-oriented workflow jobs if CI enforcement is activated for this repo.

Deviation: `git diff -- includes public admin svdp-vouchers.php` shows only deleted `.DS_Store` files under `admin/` and `public/`.
Reason: Slice G0 explicitly required `.DS_Store` cleanup.
Risk: No runtime behavior, schema, or business logic impact.
Mitigation: `.gitignore` already includes `.DS_Store`; `find . -name ".DS_Store" -print` returns no output.
Follow-up slice: None.
```

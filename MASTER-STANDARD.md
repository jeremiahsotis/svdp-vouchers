# PROJECT GOVERNANCE AND EXECUTION CONTROL STANDARD

## Purpose
This framework defines the required governance, execution, validation, planning, and drift-prevention model for software projects regardless of stack, infrastructure, repo model, or deployment shape.

## Core Rules

### Definition of Done
A change is not complete unless it updates, in proportion to the change:
1. code
2. relevant canonical docs
3. ADR or reconciliation entry
4. contracts/schemas if affected
5. migrations if persistence changed
6. observability if critical paths changed
7. tests
8. protected-surface enforcement if affected
9. roadmap/version state if slice or sequencing changed

### No Silent Drift
If implementation differs from documented truth:
1. fix code
2. or update docs/contracts/ADR/reconciliation
3. or stop and report

### Real-Tenant-First
The system is built for real tenants from day one. Demo, test, and seed data are temporary aids only and must be removable without architectural rewrite.

### Provider Scope Decision
Whenever a provider is introduced or changed, the implementation must explicitly decide whether it is system-wide or per-tenant. Per-tenant operational settings must be configurable in tenant-admin-capable application surfaces and persistence.

### Human Approval Lock
Brainstorming and planning assistance may propose features, roadmap changes, and slices, but may not write directly to roadmap state, dependency graphs, or active slice folders without explicit human approval.

## Editorial and Formatting Gate
A change is not done unless:
1. Ruff / Prettier formatting passes
2. Lint passes
3. Spellcheck passes
4. Markdown validation passes
5. No placeholder text remains
6. User-facing text is reviewed for clarity if changed

## Feature Freeze Rule
When a version is in Feature Freeze:
Allowed slice types:
- B (bugfix)
- H (hotfix)
- S (stabilization)

Forbidden:
- new feature slices
- expansion slices

## Top-Level Governance Domains
- Execution Governance
- Repository Guardrails
- Quality Enforcement
- Environment Fidelity
- Roadmap Governance
- Planning Intelligence
- Roadmap Integrity
- Slice Integrity
- Security / Policy / Auth
- Operations / Recovery


## v5 Additions

### Tier 1
- Data evolution governance
- Error taxonomy
- Refusal envelope
- Concurrency model

### Tier 2
- Environment drift control
- Access auditability
- Safe mode

### Tier 3 documented only
- Time model
- Cross-slice impact
- Cognitive load
- System boundaries

### G0 hardening requirement
Reusable reference validators in the governance pack must be hardened for each target repo during G0, especially:
- check_slice_completion_integrity.py
- check_unmapped_changes.py
- check_dependency_graph.py

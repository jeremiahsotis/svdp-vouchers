EXECUTION CONTEXT:
- repo:
- branch:
- version:
- slice:
- checkpoint:

AUTHORITATIVE SOURCES:
- implementation brief
- codepack
- checkpoint
- docs/decisions/current-state.md
- docs/governance/canonical-commands.json
- contracts/*
- docs/*

DEPRECATED:
- prior drafts
- inferred patterns

ANTI-DRIFT RULE:
Code + Docs + ADR/Reconciliation + Contracts required

PROTECTED SURFACES:
Before editing ANY file:
1. Read AGENTS.md
2. Read protected-surfaces.json
3. Read full file
4. Apply minimal patch only

If violation required:
STOP

AST CONTRACT RULE:
If protected contract files are touched:
- preserve export names
- preserve required vs optional semantics
- preserve protected nested structure
- do not rename/remove fields unless explicitly specified

PROVIDER SCOPE RULE:
If a provider is introduced or modified:
- determine whether it is system-wide or per-tenant
- if per-tenant, do not place tenant-specific operational settings in global .env
- require admin-surface-configurable tenant settings where applicable

DEPENDENCY RULE:
Do not execute slice if dependencies are incomplete.

FEATURE FREEZE RULE:
If freeze active:
- reject feature slices

TESTING RULE:
Local success is irrelevant.
CI success required.

ENVIRONMENT RULE:
Respect environment fidelity model.

EXECUTION RULE:
Execute ONLY this checkpoint.
STOP after completion.

OUTPUT:
- exact file changes only
- no summaries

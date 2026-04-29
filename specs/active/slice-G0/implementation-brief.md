# IMPLEMENTATION BRIEF

## 1. Objective

## 2. Scope
### In Scope
### Out of Scope

## 3. Architectural Context

## 4. Affected Systems

## 5. Data Model Impact

## 6. Contracts
List affected OR explicitly state NONE

## 7. Execution Flow

## 8. State Transitions

## 9. Failure Modes

## 10. Idempotency

## 11. Security / Policy / Permissions

## 12. Provider Scope Decision
- provider involved:
- scope: system-wide / per-tenant / not applicable
- rationale:
- tenant-admin surface required:
- tenant-specific settings required:
- tenant-specific secret/webhook handling required:

## 13. Protected Surface Impact
- surfaces touched
- rules applied
- patch-only required?
- lock violation risk?

## 14. Protected Contract Impact
- protected contract files touched:
- exports affected:
- AST contract validation required:
- required/optional field semantics changed:
- response envelope impact:

## 15. Guardrail Registration Impact
- new high-risk surfaces introduced:
- protected-surface registry updated:
- auto-detection check expected to pass:

## 16. Anti-Drift Mapping
- docs
- ADR
- reconciliation
- contracts
- migrations
- roadmap

## 17. Observability

## 18. Environment Fidelity

## 19. Testing Requirements

## 20. Version Context
- version:
- phase:
- slice type:

## 21. Dependencies
- depends on:
- blocks:

## 22. Finalization Pass Required Before Completion

1. Run formatter(s)
2. Run lint checks
3. Run spellcheck
4. Review changed markdown, comments, UI copy, and docstrings for:
   - typos
   - duplicated words
   - malformed lists
   - heading consistency
   - awkward phrasing
5. Do not mark work complete until all checks pass

## 23. Non-Goals

## 24. Future Work

## 25. G0 Repo Binding Requirements

G0 must harden reusable reference validators for the target repo.

Required validator hardening:
- check_slice_completion_integrity.py
- check_unmapped_changes.py
- check_dependency_graph.py

For each, G0 must bind the validator to:
- actual slice folder layout
- actual roadmap files and status model
- actual codepack structure
- actual canonical commands
- actual CI behavior

G0 is not complete until these validators are trustworthy for the target repo.

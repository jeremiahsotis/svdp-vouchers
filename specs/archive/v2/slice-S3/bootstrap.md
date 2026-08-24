# S3 Bootstrap — Cashier Correction Modal + Recent Audit Display

## Active Slice

S3: Cashier Correction Modal + Recent Audit Display

## Authoritative Execution Sources

Read these files before coding, in this order:

1. `specs/active/slice-S3/implementation-brief.md`
2. `specs/active/slice-S3/codepack.md`
3. `specs/active/slice-S3/checkpoint-01.md`
4. `specs/active/slice-S2/implementation-brief.md`
5. `specs/active/slice-S2/codepack.md`
6. `specs/active/slice-S2/checkpoint.md`
7. `contracts/protected-surfaces.json`
8. `contracts/protected-contracts.json`
9. `contracts/protected-surface-acceptance.json`
10. `docs/governance/canonical-commands.json`

## Execution Rules

- Do not expand S3 beyond cashier correction UI and recent audit display.
- Do not build full admin audit explorer.
- Do not introduce catalog audit.
- Do not introduce delivery dispatch, delivery attempts, driver assignment, RouteShyft, or routing language.
- Do not expose manager override codes.
- Do not edit legacy `public/js/cashier-station.js` unless repo evidence proves it is active.
- Use S2 correction endpoint as the backend authority.
- Keep recent audit display human-readable.

## Required Implementation Order

1. Add recent correction retrieval to cashier voucher payload.
2. Add Recent Corrections display to clothing detail.
3. Add Recent Corrections display to furniture detail.
4. Add Correct Voucher UI to clothing detail.
5. Add Correct Voucher UI to furniture detail.
6. Add `voucher-correct` JavaScript submit handler.
7. Validate manager authority before correction submit.
8. Submit correction to S2 endpoint.
9. Refresh voucher detail after success.
10. Run checkpoint validation.

## Completion Output Required

When complete, provide:

```bash
git status --short
git log --oneline -n 10

grep -RIn "recent_corrections\|voucher-correct\|submitVoucherCorrection\|human_summary" includes public/templates public/js | head -n 300

wp db query "
SELECT id, voucher_id, field_name, before_value, after_value, manager_id, manager_name_snapshot, reason_text_snapshot, human_summary, created_at
FROM wp_svdp_voucher_corrections
ORDER BY id DESC
LIMIT 10;
"

find includes public admin -name '*.php' -print0 | xargs -0 -n1 php -l
python3 scripts/check_required_doc_sections.py
find . -name ".DS_Store" -print

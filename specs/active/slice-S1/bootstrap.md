# Bootstrap — Slice S1

## Authoritative Execution Sources

- specs/active/slice-S1/implementation-brief.md
- specs/active/slice-S1/codepack.md
- specs/active/slice-S1/checkpoint-01.md
- specs/active/slice-S1/bootstrap.md

## Step 1 — Create branch

git checkout -b slice/S1-override-authority

---

## Step 2 — Apply DB changes

- update class-database.php
- run plugin activation or dbDelta

---

## Step 3 — Update manager class

- replace generate_code
- add code_exists
- add validate_code_with_audit
- add lockout logic

---

## Step 4 — Update REST endpoint

- modify /managers/validate handler

---

## Step 5 — Update frontend

- extend modal payload
- include managerName + reasonId

---

## Step 6 — Run validation

python3 scripts/check_no_placeholders.py
python3 scripts/check_required_doc_sections.py

---

## Step 7 — Manual validation

- create manager
- test valid code
- test invalid code
- trigger lockout
- inspect audit table

---

## Step 8 — Commit

git add .
git commit -m "S1: Override authority foundation implemented"
```

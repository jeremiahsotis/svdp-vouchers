Slice 2 — Checkpoint 2.1: Layout Contract Fix

Objective

Fix structural layout issues in the Voucher Request Form so that:

* category cards do not overlap
* badge/pill elements remain contained
* summary column does not break layout
* behavior is stable across breakpoints

⸻

Problem Statement

Current UI issues:

* category cards visually merge or overlap
* badge elements overflow card boundaries
* summary column compresses grid at mid-width
* inconsistent spacing and alignment

Root cause:
Lack of enforced layout contract between grid, cards, and summary column.

⸻

Scope

Included

* category grid CSS
* card container structure
* badge/pill containment
* summary column responsive behavior
* breakpoint handling

Excluded

* catalog logic
* pricing logic
* search behavior
* interaction model changes (Checkpoint 2.2)

⸻

Required Layout Behavior

Grid

* uses consistent column system
* prevents overlap at all widths
* enforces spacing between cards

Cards

* fully self-contained
* no overflow beyond boundaries
* consistent padding and vertical rhythm

Summary Column

| Screen Size | Behavior                      |
| ----------- | ----------------------------- |
| Mobile      | stacked                       |
| Tablet      | stacked                       |
| Desktop     | right column, optional sticky |

⸻

Implementation Targets

Likely files:

* public/templates/voucher-request-form.php
* public/css/voucher-forms.css

⸻

Constraints

* DO NOT modify any pricing calculations
* DO NOT modify catalog queries
* DO NOT introduce JS unless strictly necessary
* DO NOT redesign UI beyond layout stabilization

⸻

Acceptance Criteria

* no overlapping category cards
* no visual overflow from badges
* consistent spacing between cards
* summary never compresses grid
* layout works cleanly on:
    * mobile
    * tablet
    * desktop

⸻

Done When

* layout behaves predictably at all breakpoints
* UI is visually stable without hacks or overrides
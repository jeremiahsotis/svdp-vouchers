Slice 2 — Checkpoint 2.3: Browse + Search Improvements

Objective

Improve furniture catalog browsing and search behavior without changing catalog data, pricing logic, or voucher submission behavior.

Scope

Included

* Singular/plural search normalization
* Basic search token normalization
* Clear browse vs search state
* Clear empty-results message
* Search reset behavior
* Reduce confusion from duplicated category/header labels where possible

Excluded

* Pricing logic
* Catalog schema
* Backend APIs
* Approval modal behavior
* Category layout contract
* Category card interaction model

Requirements

Search Normalization

Search must treat common singular/plural forms as equivalent.

Examples:

* bed matches beds
* beds matches bed
* chair matches chairs
* towel matches towels

Use light normalization only:

* lowercase
* trim
* collapse whitespace
* remove basic punctuation
* normalize simple plural endings

Do not add fuzzy search or external libraries.

Browse/Search State

When search is empty:

* category browsing is restored
* previous open categories remain reasonable and stable
* all available categories return

When search has text:

* show matching items
* hide categories with zero matches
* show clear empty state if no matches

UX

Search should support browsing, not replace it.

Likely Files

* public/js/voucher-request.js
* public/templates/voucher-request-form.php
* public/css/voucher-forms.css

Constraints

* Do not modify pricing calculations
* Do not modify item selection quantity logic
* Do not modify voucher submission payload
* Do not modify backend catalog API
* Do not introduce dependencies

Acceptance Criteria

* Searching beds finds items containing bed
* Searching bed finds items containing beds if present
* Clearing search restores category browsing
* Empty search results show a clear message
* No selected items are lost during search/filter changes
* Quantity controls still work after search and after reset

Done When

* search is more forgiving
* browse/search transitions are predictable
* no business logic changed
# Slice 6.1 - Category State Stability

## Goal

Fix duplicate category / ghost category behavior in the furniture catalog by making browse mode and search mode explicit and stable.

## Problem

The category template markup is not duplicated. The likely issue is JavaScript state synchronization between:

- category cards
- category sections
- search mode
- browse mode
- `state.openCategories`
- re-rendered section bodies

Search mode currently opens matching sections automatically. Browse mode uses `state.openCategories`. If those states bleed into each other, the UI can appear duplicated, ghosted, or confusing after search/reset.

## Locked Behavior

### Browse mode

Browse mode means no active search tokens.

Expected behavior:

- Category cards are visible.
- Category sections are hidden unless explicitly opened by the user.
- Clicking a category card toggles only that category section.
- Clearing search returns to this mode.
- Previously selected item quantities remain selected.

### Search mode

Search mode means one or more active search tokens.

Expected behavior:

- Category cards are hidden.
- Matching category sections are visible.
- Non-matching category sections are hidden.
- Matching item rows appear once.
- `state.openCategories` is not changed by search mode.
- Clearing search exits search mode cleanly.

## Required Code Changes

Modify only:

- `public/js/voucher-request.js`

Review only if needed:

- `public/templates/voucher-request-form.php`

### Required implementation details

1. Add a helper to resolve a category section by key:

```js
function getCategorySection(categoryKey) {
    return catalogContainer.find('[data-category-section="' + categoryKey + '"]').first();
}
```

2. Update `syncCategorySectionState()` so search mode and browse mode are separated.

Search mode:

```js
const shouldShowSection = visibleCount > 0;
section.prop('hidden', !shouldShowSection);
section.toggleClass('is-open', shouldShowSection);
card.attr('aria-expanded', shouldShowSection ? 'true' : 'false');
card.toggleClass('is-open', shouldShowSection);
card.toggleClass('is-selected', shouldShowSection);
```

Browse mode:

```js
const isOpen = !!state.openCategories[categoryKey];
section.prop('hidden', !isOpen);
section.toggleClass('is-open', isOpen);
card.attr('aria-expanded', isOpen ? 'true' : 'false');
card.toggleClass('is-open', isOpen);
card.toggleClass('is-selected', isOpen);
```

3. Do not mutate `state.openCategories` during search mode.

4. Ensure `renderCatalog()` only replaces this element:

```js
.svdp-furniture-category-section-body
```

5. Do not append, duplicate, or recreate:

```text
#svdpFurnitureCategoryCards
#svdpFurnitureCategorySections
[data-category-card]
[data-category-section]
```

6. Use `.first()` when resolving a category section from a category key.

## Out of Scope

Do not change:

- pricing copy
- address verification
- delivery logic
- delivery tracking
- receipt templates
- cashier flow
- database schema
- voucher submission payloads

## Acceptance Criteria

This checkpoint is complete when:

- Initial form load shows category cards once.
- Clicking a category opens that category once.
- Clicking the same category closes it.
- Searching `bed` shows matching results once.
- Searching `beds` shows matching results once.
- Clearing search restores category cards cleanly.
- Clearing search does not duplicate sections.
- Selected quantities persist through search and clear.
- Switching voucher type away from furniture and back does not duplicate categories.
- Submitting a furniture voucher still works.
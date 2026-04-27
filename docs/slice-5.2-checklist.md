# Slice 5.2 — Checklist

## Backend

[ ] delivery_address_display still uses user-entered address  
[ ] "(verified)" appended only when normalized differs  
[ ] delivery_address_verified returned  
[ ] delivery_address_normalized returned  

---

## Cashier UI

[ ] Verified address → no warning  
[ ] Unverified address → shows "Address not verified"  

---

## Receipt

[ ] Verified → no warning  
[ ] Unverified → warning printed  

---

## Frontend Behavior

[ ] Selecting suggestion sets:
    - lat
    - lng
    - verified = 1
    - normalized

[ ] Editing line1/city/state/zip clears verification  

[ ] Editing line2 does NOT clear verification  

---

## Submission

[ ] Unverified address submits successfully  
[ ] Verified address submits successfully  

---

## Regression

[ ] No break in voucher submission  
[ ] No JS errors  
[ ] No API errors  
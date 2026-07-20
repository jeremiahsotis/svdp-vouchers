(function ($) {
  "use strict";

  $(document).ready(function () {
    const form = $("#svdpVoucherForm");
    if (!form.length) {
      return;
    }

    const STOCK_MESSAGE =
      "Stock fluctuates. Items are NOT guaranteed to be in stock. The neighbor will need to visit the store to see what is currently available. Staff are not able to verify availability.";
    const TYPE_LABELS = {
      clothing: "Clothing Voucher",
      furniture: "Furniture Voucher",
      household_goods: "Household Goods Voucher",
    };
    const STEP_LABELS = {
      assistance: "Assistance Needed",
      household: "Household Information",
      clothing: "Clothing Voucher",
      furniture: "Choose Furniture Items",
      household_goods: "Household Goods",
      delivery: "Delivery",
      requestor: "Requestor / Organization",
      review: "Review Request",
    };
    const deliveryFee = Number(
      window.svdpVouchers && svdpVouchers.deliveryFee
        ? svdpVouchers.deliveryFee
        : 50,
    );
    const availableTypes = parseJsonAttribute(
      form.attr("data-available-voucher-types"),
      ["clothing"],
    );
    const deliveryCapabilities = parseJsonAttribute(
      form.attr("data-delivery-capabilities"),
      {},
    );
    const defaultRequestorLabels = {
      entity: form.attr("data-requestor-entity-label") || "Conference",
      name:
        form.attr("data-requestor-name-label") ||
        "Conference Member / Vincentian Name",
      email:
        form.attr("data-requestor-email-label") ||
        "Conference Member / Vincentian Email",
    };

    let maxCostModalReturnFocus = null;

    const state = {
      stepIndex: 0,
      visibleSteps: [],
      selectedTypes:
        availableTypes.indexOf("clothing") !== -1
          ? ["clothing"]
          : [availableTypes[0]],
      clothingVisited: false,
      furniture: {
        loaded: false,
        loading: false,
        groups: [],
        byId: {},
        activeGroup: "all",
        search: "",
        selected: {},
      },
      householdGoods: {
        loaded: false,
        loading: false,
        groups: [],
        byId: {},
        limits: {
          selected_category_limit: 0,
          voucher_quantity_max: 0,
        },
        activeGroup: "all",
        search: "",
        selected: {},
      },
      deliveryRequested: false,
      addressSearchTimer: null,
      addressSearchRequest: null,
      addressSearchQuery: "",
      addressSuggestions: [],
      eligibility: {
        checkedSignature: "",
        issues: [],
      },
      confirmation: null,
    };

    initializeDateInput();
    bindEvents();
    recalculateSteps();
    render();

    function bindEvents() {
      form.on("click", "[data-voucher-type-option]", function () {
        toggleVoucherType($(this).attr("data-voucher-type-option"));
      });

      $("#svdpBuilderBack").on("click", goBack);
      $("#svdpBuilderNext").on("click", goNext);
      $("#svdpSummaryAction").on("click", function () {
        const currentStep = state.visibleSteps[state.stepIndex];
        if (currentStep === "review") {
          form.trigger("submit");
          return;
        }
        goNext();
      });

      $("#svdpMobileSummaryAction").on("click", function () {
        const currentStep = state.visibleSteps[state.stepIndex];
        if (currentStep === "review") {
          form.trigger("submit");
          return;
        }
        goNext();
      });

      $("#svdpBuilderSubmit").on("click", function (event) {
        if ($(this).attr("data-confirmation-action") === "request-another") {
          event.preventDefault();
          window.location.reload();
        }
      });

      $("#svdpMaxCostConfirm").on("click", function () {
        closeMaxCostConfirmation(false);
        submitRequest(true);
      });

      form.on("click", "[data-max-cost-cancel]", function () {
        closeMaxCostConfirmation(true);
      });

      $(document).on("keydown", function (event) {
        if (event.key === "Escape" && !$("#svdpMaxCostModal").prop("hidden")) {
          closeMaxCostConfirmation(true);
        }
      });

      form.on("submit", function (event) {
        event.preventDefault();
        submitRequest();
      });

      form.on(
        "input change",
        '[name="firstName"], [name="lastName"], [name="dob"], [name="adults"], [name="children"]',
        function () {
          clearEligibilityState();

          if (
            $(this).is('[name="adults"]') ||
            $(this).is('[name="children"]')
          ) {
            updateHouseholdCount();
            updateSummary();
          }
        },
      );

      form.on("change", '[name="conference"]', function () {
        syncConferenceTypeAvailability();
        recalculateSteps();
        render();
      });

      $("#svdpFurnitureSearch").on("input", function () {
        state.furniture.search = $(this).val();
        renderFurnitureCatalog();
      });

      $("#svdpHouseholdGoodsSearch").on("input", function () {
        state.householdGoods.search = $(this).val();
        renderHouseholdGoodsCatalog();
      });

      form.on("click", "[data-furniture-filter]", function () {
        state.furniture.activeGroup = $(this).attr("data-furniture-filter");
        renderFurnitureCatalog();
      });

      form.on("click", "[data-household-goods-filter]", function () {
        state.householdGoods.activeGroup = $(this).attr(
          "data-household-goods-filter",
        );
        renderHouseholdGoodsCatalog();
      });

      form.on("click", "[data-furniture-adjust]", function () {
        adjustFurnitureQuantity(
          Number($(this).attr("data-item-id")),
          $(this).attr("data-furniture-adjust") === "increment" ? 1 : -1,
        );
      });

      form.on("click", "[data-household-goods-adjust]", function () {
        adjustHouseholdGoodsQuantity(
          Number($(this).attr("data-category-id")),
          $(this).attr("data-household-goods-adjust") === "increment" ? 1 : -1,
        );
      });

      form.on("change", "[data-household-goods-quantity]", function () {
        setHouseholdGoodsQuantity(
          Number($(this).attr("data-household-goods-quantity")),
          Number($(this).val() || 0),
        );
      });

      form.on("click", "[data-delivery-choice]", function () {
        state.deliveryRequested =
          $(this).attr("data-delivery-choice") === "needed";
        syncDeliveryControls();
        updateSummary();
      });

      form.on(
        "input",
        '[name="deliveryLine1"], [name="deliveryCity"], [name="deliveryState"], [name="deliveryZip"]',
        function () {
          clearAddressVerificationFields();
          scheduleAddressSearch();
        },
      );

      form.on(
        "focus",
        '[name="deliveryLine1"], [name="deliveryCity"], [name="deliveryState"], [name="deliveryZip"]',
        function () {
          if (state.addressSuggestions.length > 0 && state.deliveryRequested) {
            getAddressSuggestionDropdown().prop("hidden", false);
          }
        },
      );

      $(document).on(
        "mousedown",
        "[data-address-suggestion-index]",
        function (event) {
          event.preventDefault();
          selectAddressSuggestion(
            Number($(this).attr("data-address-suggestion-index")),
          );
        },
      );

      $(document).on("click", function (event) {
        const target = $(event.target);
        if (
          target.closest("#svdpDeliveryAddressSuggestions").length ||
          target.closest('[name="deliveryLine1"]').length
        ) {
          return;
        }
        hideAddressSuggestions();
      });

      form.on("click", "[data-edit-step]", function () {
        const step = $(this).attr("data-edit-step");
        const index = state.visibleSteps.indexOf(step);
        if (index !== -1) {
          state.stepIndex = index;
          render();
        }
      });

      form.on("click", "[data-pill-arrow]", function () {
        const shell = $(this).closest("[data-pill-scroll]");
        const row = shell.find(".svdp-filter-pills").first();
        const direction = $(this).attr("data-pill-arrow") === "left" ? -1 : 1;
        row[0].scrollBy({ left: direction * 180, behavior: "smooth" });
      });

      form.on("scroll", ".svdp-filter-pills", updateAllPillArrows);
      $(window).on(
        "resize orientationchange",
        debounce(updateAllPillArrows, 120),
      );
    }

    function initializeDateInput() {
      const dateInput = document.createElement("input");
      dateInput.setAttribute("type", "date");
      const supportsDateInput = dateInput.type === "date";
      const mobile =
        /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
          navigator.userAgent,
        );

      if (!supportsDateInput || mobile) {
        const dobField = $("#svdp-dob-input");
        dobField.attr("type", "text").attr("pattern", "\\d{2}/\\d{2}/\\d{4}");
        dobField.on("input", function (event) {
          let value = event.target.value.replace(/\D/g, "");
          if (value.length >= 2) {
            value = value.substring(0, 2) + "/" + value.substring(2);
          }
          if (value.length >= 5) {
            value = value.substring(0, 5) + "/" + value.substring(5, 9);
          }
          event.target.value = value;
        });
      }
    }

    function toggleVoucherType(type) {
      if (
        availableTypes.indexOf(type) === -1 ||
        !isTypeAllowedByConference(type)
      ) {
        return;
      }

      const selected = state.selectedTypes.indexOf(type) !== -1;
      clearEligibilityState();

      if (!selected) {
        state.selectedTypes.push(type);
        sortSelectedTypes();
        recalculateSteps();
        render();
        return;
      }

      if (state.selectedTypes.length === 1) {
        showInlineError(
          "assistance",
          "At least one voucher type must remain selected.",
        );
        return;
      }

      if (typeHasEnteredData(type)) {
        const confirmed = window.confirm(
          "Remove " +
            TYPE_LABELS[type] +
            "?\n\nRemoving this selection will remove the information entered for that voucher type.",
        );
        if (!confirmed) {
          return;
        }
      }

      state.selectedTypes = state.selectedTypes.filter(function (selectedType) {
        return selectedType !== type;
      });
      clearTypeData(type);
      clearDeliveryIfNoEligibleTypes();
      recalculateSteps();
      state.stepIndex = Math.min(
        state.stepIndex,
        state.visibleSteps.length - 1,
      );
      render();
    }

    function typeHasEnteredData(type) {
      if (type === "clothing") {
        return state.clothingVisited;
      }
      if (type === "furniture") {
        return Object.keys(state.furniture.selected).length > 0;
      }
      if (type === "household_goods") {
        return Object.keys(state.householdGoods.selected).length > 0;
      }
      return false;
    }

    function clearTypeData(type) {
      if (type === "clothing") {
        state.clothingVisited = false;
      }
      if (type === "furniture") {
        state.furniture.selected = {};
      }
      if (type === "household_goods") {
        state.householdGoods.selected = {};
      }
    }

    function clearDeliveryIfNoEligibleTypes() {
      if (getDeliveryEligibleTypes().length > 0) {
        return;
      }
      if (!state.deliveryRequested && !hasDeliveryAddressData()) {
        return;
      }
      state.deliveryRequested = false;
      clearDeliveryAddress();
    }

    function recalculateSteps() {
      if (state.selectedTypes.length === 0) {
        state.visibleSteps = ["assistance"];
        return;
      }

      const steps = ["assistance", "household"];

      if (isSelected("clothing")) {
        steps.push("clothing");
      }
      if (isSelected("furniture")) {
        steps.push("furniture");
      }
      if (isSelected("household_goods")) {
        steps.push("household_goods");
      }
      if (getDeliveryEligibleTypes().length > 0) {
        steps.push("delivery");
      }

      steps.push("requestor", "review");
      state.visibleSteps = steps;
    }

    function render() {
      const currentStep =
        state.visibleSteps[state.stepIndex] || state.visibleSteps[0];

      $("[data-step-panel]").prop("hidden", true);
      $('[data-step-panel="' + currentStep + '"]').prop("hidden", false);

      $("#svdpStepCounter").text(
        "Step " + (state.stepIndex + 1) + " of " + state.visibleSteps.length,
      );
      $("#svdpStepTitle").text(STEP_LABELS[currentStep] || "");
      syncRequestorLabels();
      renderStepper(currentStep);
      renderAssistanceCards();
      syncConferenceTypeAvailability();
      updateHouseholdCount();
      syncDeliveryControls();

      if (currentStep === "clothing") {
        state.clothingVisited = true;
      }
      if (currentStep === "furniture") {
        ensureFurnitureCatalogLoaded();
      }
      if (currentStep === "household_goods") {
        ensureHouseholdGoodsCatalogLoaded();
      }
      if (currentStep === "review") {
        renderReview();
      }

      $("#svdpBuilderBack").prop("disabled", state.stepIndex === 0);
      $("#svdpBuilderNext").prop("hidden", currentStep === "review");
      $("#svdpBuilderSubmit").prop("hidden", currentStep !== "review");
      $("#svdpSummaryAction").text(
        currentStep === "review" ? "Submit" : "Continue",
      );
      form.attr("data-current-step", currentStep);
      updateSummary();
      window.setTimeout(updateAllPillArrows, 0);
    }

    function renderStepper(currentStep) {
      $("#svdpBuilderSteps").html(
        state.visibleSteps
          .map(function (step, index) {
            const stateClass =
              step === currentStep
                ? " is-current"
                : index < state.stepIndex
                  ? " is-complete"
                  : "";
            const label = STEP_LABELS[step] || "";
            return (
              '<li class="' +
              stateClass +
              '" aria-label="' +
              escapeHtml(label) +
              '">' +
              '<span class="svdp-step-number">' +
              (index + 1) +
              "</span>" +
              "</li>"
            );
          })
          .join(""),
      );
    }

    function renderAssistanceCards() {
      $("[data-voucher-type-option]").each(function () {
        const card = $(this);
        const type = card.attr("data-voucher-type-option");
        const selected = isSelected(type);
        const allowed = isTypeAllowedByConference(type);
        card
          .toggleClass("is-selected", selected)
          .toggleClass("is-disabled", !allowed)
          .attr("aria-pressed", selected ? "true" : "false")
          .prop("disabled", !allowed);
      });
    }

    function goBack() {
      clearAllInlineErrors();
      if (state.stepIndex > 0) {
        state.stepIndex -= 1;
        render();
      }
    }

    function goNext() {
      clearAllInlineErrors();
      const currentStep = state.visibleSteps[state.stepIndex];
      const valid = validateStep(currentStep);
      if (!valid) {
        return;
      }

      if (currentStep === "household") {
        runEligibilityBeforeNextStep();
        return;
      }

      advanceStep();
    }

    function advanceStep() {
      if (state.stepIndex < state.visibleSteps.length - 1) {
        state.stepIndex += 1;
        render();
      }
    }

    function runEligibilityBeforeNextStep() {
      const nextButton = $("#svdpBuilderNext");
      nextButton.prop("disabled", true).text("Checking eligibility...");

      checkEligibilityAfterHousehold()
        .then(function (canContinue) {
          if (canContinue) {
            advanceStep();
          }
        })
        .catch(function (xhr) {
          const response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
          const message =
            response && response.message
              ? response.message
              : xhr.message || "Eligibility could not be checked right now.";
          showInlineError("household", message);
        })
        .always(function () {
          nextButton.prop("disabled", false).text("Continue");
        });
    }

    function clearEligibilityState() {
      state.eligibility.checkedSignature = "";
      state.eligibility.issues = [];
    }

    function getEligibilitySignature() {
      return JSON.stringify({
        firstName: $.trim(form.find('[name="firstName"]').val()),
        lastName: $.trim(form.find('[name="lastName"]').val()),
        dob: getFormattedDob(),
        conference: form.find('[name="conference"]').val() || "",
        voucherTypes: state.selectedTypes.slice().sort(),
      });
    }

    function checkEligibilityAfterHousehold() {
      const signature = getEligibilitySignature();

      if (state.eligibility.checkedSignature === signature) {
        return $.Deferred().resolve(true).promise();
      }

      if (state.selectedTypes.length === 0) {
        return $.Deferred().resolve(false).promise();
      }

      const checks = state.selectedTypes.map(function (type) {
        return $.ajax({
          url: svdpVouchers.restUrl + "svdp/v1/vouchers/check-duplicate",
          method: "POST",
          headers: { "X-WP-Nonce": svdpVouchers.nonce },
          data: JSON.stringify({
            firstName: $.trim(form.find('[name="firstName"]').val()),
            lastName: $.trim(form.find('[name="lastName"]').val()),
            dob: getFormattedDob(),
            conference: form.find('[name="conference"]').val(),
            voucherType: type,
            createdBy: "Vincentian",
          }),
          contentType: "application/json",
        }).then(function (response) {
          return {
            voucherType: type,
            response: response || { found: false },
          };
        });
      });

      return $.when.apply($, checks).then(function () {
        const results =
          checks.length === 1
            ? [arguments[0]]
            : Array.prototype.slice.call(arguments);
        const issues = results
          .map(function (result) {
            return buildEligibilityIssue(result.voucherType, result.response);
          })
          .filter(Boolean);

        state.eligibility.checkedSignature = signature;
        state.eligibility.issues = issues;

        if (issues.length === 0) {
          return true;
        }

        const blockedTypes = issues.map(function (issue) {
          return issue.voucherType;
        });

        state.selectedTypes = state.selectedTypes.filter(function (type) {
          return blockedTypes.indexOf(type) === -1;
        });

        blockedTypes.forEach(clearTypeData);
        clearDeliveryIfNoEligibleTypes();
        recalculateSteps();

        showEligibilityWarning(issues);

        if (state.selectedTypes.length === 0) {
          state.stepIndex = 0;
          render();
          showInlineError(
            "assistance",
            "None of the selected voucher types are currently eligible for this household.",
          );
          return false;
        }

        state.stepIndex = Math.min(
          state.stepIndex,
          Math.max(0, state.visibleSteps.length - 1),
        );
        render();

        return true;
      });
    }

    function buildEligibilityIssue(type, response) {
      if (!response || !response.found) {
        return null;
      }

      return {
        voucherType: type,
        label: TYPE_LABELS[type] || typeShortLabel(type),
        matchType: response.matchType || "exact",
        response: response,
      };
    }

    function showEligibilityWarning(issues) {
      const remainingLabels = state.selectedTypes.map(typeShortLabel);
      const lines = [
        "One or more selected voucher types are not currently eligible for this household.",
        "",
      ];

      issues.forEach(function (issue) {
        lines.push(issue.label);

        if (issue.matchType === "similar" && issue.response.matches) {
          issue.response.matches.forEach(function (match) {
            lines.push("• Issuing entity: " + safeText(match.conference));
            lines.push(
              "• Issued date: " +
                formatDateForDisplay(match.voucherCreatedDate),
            );
            lines.push("• Issued by: " + safeText(match.vincentianName));
            lines.push(
              "• Next eligible date: " +
                formatDateForDisplay(match.nextEligibleDate),
            );
          });
        } else {
          lines.push(
            "• Issuing entity: " + safeText(issue.response.conference),
          );
          lines.push(
            "• Issued date: " +
              formatDateForDisplay(issue.response.voucherCreatedDate),
          );
          lines.push("• Issued by: " + safeText(issue.response.vincentianName));
          lines.push(
            "• Next eligible date: " +
              formatDateForDisplay(issue.response.nextEligibleDate),
          );
        }

        lines.push("");
      });

      if (remainingLabels.length > 0) {
        lines.push(
          "The ineligible voucher type" +
            (issues.length === 1 ? " has" : "s have") +
            " been removed. The request can continue with: " +
            formatTypeList(remainingLabels) +
            ".",
        );
      } else {
        lines.push(
          "All selected voucher types were removed because none are currently eligible.",
        );
      }

      window.alert(lines.join("\n"));
    }

    function safeText(value) {
      return value ? String(value) : "Not recorded";
    }

    function formatDateForDisplay(value) {
      if (!value) {
        return "Not recorded";
      }

      const parts = String(value).split("-");
      if (parts.length === 3) {
        return parts[1] + "/" + parts[2] + "/" + parts[0];
      }

      return String(value);
    }

    function validateStep(step) {
      if (step === "assistance") {
        if (state.selectedTypes.length === 0) {
          return showInlineError(
            "assistance",
            "Select at least one voucher type.",
          );
        }
        return true;
      }

      if (step === "household") {
        const required = [
          ["firstName", "Enter the first name."],
          ["lastName", "Enter the last name."],
          ["dob", "Enter a valid date of birth."],
        ];
        for (let index = 0; index < required.length; index += 1) {
          if (!$.trim(form.find('[name="' + required[index][0] + '"]').val())) {
            return showInlineError(
              "household",
              required[index][1],
              form.find('[name="' + required[index][0] + '"]'),
            );
          }
        }
        if (getFormattedDob() === "") {
          return showInlineError(
            "household",
            "Enter a valid date of birth.",
            form.find('[name="dob"]'),
          );
        }
        if (getHouseholdSize() < 1) {
          return showInlineError(
            "household",
            "Enter at least one adult or child in the household.",
          );
        }
        return true;
      }

      if (step === "furniture") {
        if (!state.furniture.loaded) {
          return showInlineError(
            "furniture",
            "Furniture catalog items are still loading. Please wait a moment and try again.",
          );
        }
        if (getSelectedFurnitureItems().length === 0) {
          return showInlineError(
            "furniture",
            "Select at least one furniture item.",
          );
        }
        return true;
      }

      if (step === "household_goods") {
        return validateHouseholdGoods();
      }

      if (step === "delivery") {
        if (!state.deliveryRequested) {
          return true;
        }
        const requiredFields = [
          ["deliveryLine1", "Enter the delivery address line 1."],
          ["deliveryCity", "Enter the delivery city."],
          ["deliveryState", "Enter the delivery state."],
          ["deliveryZip", "Enter the delivery ZIP code."],
        ];
        for (let i = 0; i < requiredFields.length; i += 1) {
          if (
            !$.trim(form.find('[name="' + requiredFields[i][0] + '"]').val())
          ) {
            return showInlineError(
              "delivery",
              requiredFields[i][1],
              form.find('[name="' + requiredFields[i][0] + '"]'),
            );
          }
        }
        return true;
      }

      if (step === "requestor") {
        if (!$.trim(form.find('[name="conference"]').val())) {
          return showInlineError(
            "requestor",
            "Select the Conference or Partner Organization.",
            form.find('[name="conference"]'),
          );
        }
        if (!selectedTypesAllowedByConference()) {
          return showInlineError(
            "requestor",
            "This organization is not configured for every selected voucher type.",
          );
        }
        if (!$.trim(form.find('[name="vincentianName"]').val())) {
          return showInlineError(
            "requestor",
            "Enter the " + getRequestorLabels().name + ".",
            form.find('[name="vincentianName"]'),
          );
        }
        if (!$.trim(form.find('[name="vincentianEmail"]').val())) {
          return showInlineError(
            "requestor",
            "Enter the " + getRequestorLabels().email + ".",
            form.find('[name="vincentianEmail"]'),
          );
        }
        return true;
      }

      return true;
    }

    function validateHouseholdGoods() {
      if (!state.householdGoods.loaded) {
        return showInlineError(
          "household_goods",
          "Household Goods catalog categories are still loading. Please wait a moment and try again.",
        );
      }

      const selected = getSelectedHouseholdGoodsCategories();
      const limits = state.householdGoods.limits;
      const categoryLimit = Number(limits.selected_category_limit || 0);
      const voucherMax = Number(limits.voucher_quantity_max || 0);
      const totalUnits = selected.reduce(function (sum, category) {
        return sum + Number(category.quantity || 0);
      }, 0);

      if (selected.length === 0) {
        return showInlineError(
          "household_goods",
          "Select at least one Household Goods category.",
        );
      }
      if (categoryLimit > 0 && selected.length > categoryLimit) {
        return showInlineError(
          "household_goods",
          "Select no more than " +
            categoryLimit +
            " Household Goods categories.",
        );
      }

      for (let index = 0; index < selected.length; index += 1) {
        const category = selected[index];
        if (category.quantity <= 0) {
          return showInlineError(
            "household_goods",
            category.name + " quantity must be greater than zero.",
          );
        }
        if (
          category.quantityMax > 0 &&
          category.quantity > category.quantityMax
        ) {
          return showInlineError(
            "household_goods",
            category.name + " is limited to " + category.quantityMax + ".",
          );
        }
      }

      if (voucherMax > 0 && totalUnits > voucherMax) {
        return showInlineError(
          "household_goods",
          "This Household Goods voucher is limited to " +
            voucherMax +
            " total requested items.",
        );
      }

      return true;
    }

    function ensureFurnitureCatalogLoaded() {
      if (state.furniture.loaded || state.furniture.loading) {
        return;
      }

      state.furniture.loading = true;
      $.ajax({
        url: svdpVouchers.restUrl + "svdp/v1/catalog-items",
        method: "GET",
        headers: { "X-WP-Nonce": svdpVouchers.nonce },
        success: function (response) {
          state.furniture.groups = response.categories || [];
          state.furniture.byId = {};
          state.furniture.groups.forEach(function (group) {
            (group.items || []).forEach(function (item) {
              state.furniture.byId[Number(item.id)] = item;
            });
          });
          state.furniture.loaded = true;
          $("#svdpFurnitureSearch").prop("disabled", false);
          renderFurnitureCatalog();
        },
        error: function () {
          $("#svdpFurnitureCatalog").html(
            '<div class="svdp-message error">Unable to load furniture catalog items right now. Please try again.</div>',
          );
        },
        complete: function () {
          state.furniture.loading = false;
        },
      });
    }

    function ensureHouseholdGoodsCatalogLoaded() {
      if (state.householdGoods.loaded || state.householdGoods.loading) {
        return;
      }

      state.householdGoods.loading = true;
      $.ajax({
        url: svdpVouchers.restUrl + "svdp/v1/household-goods/catalog",
        method: "GET",
        headers: { "X-WP-Nonce": svdpVouchers.nonce },
        success: function (response) {
          state.householdGoods.groups = response.groups || [];
          state.householdGoods.limits =
            response.limits || state.householdGoods.limits;
          state.householdGoods.byId = {};
          state.householdGoods.groups.forEach(function (group) {
            (group.categories || []).forEach(function (category) {
              state.householdGoods.byId[Number(category.id)] = category;
            });
          });
          state.householdGoods.loaded = true;
          $("#svdpHouseholdGoodsSearch").prop("disabled", false);
          renderHouseholdGoodsCatalog();
        },
        error: function () {
          $("#svdpHouseholdGoodsCatalog").html(
            '<div class="svdp-message error">Unable to load Household Goods categories right now. Please try again.</div>',
          );
        },
        complete: function () {
          state.householdGoods.loading = false;
        },
      });
    }

    function renderFurnitureCatalog() {
      const groups = getFurnitureGroups();
      $("#svdpFurniturePills").html(
        renderFilterPills(
          groups,
          state.furniture.activeGroup,
          "data-furniture-filter",
        ),
      );

      const items = getFilteredFurnitureItems();
      $("#svdpFurnitureCatalog")
        .attr("data-catalog-loaded", "true")
        .html(
          items.length
            ? items.map(function (item) {
                return renderFurnitureItem(item, !state.furniture.activeGroup || state.furniture.activeGroup === "all");
              }).join("")
            : '<div class="svdp-empty-state">No furniture items match this search.</div>',
        );
      updateSummary();
      window.setTimeout(updateAllPillArrows, 0);
    }

    function renderHouseholdGoodsCatalog() {
      const groups = getHouseholdGoodsGroups();
      $("#svdpHouseholdGoodsPills").html(
        renderFilterPills(
          groups,
          state.householdGoods.activeGroup,
          "data-household-goods-filter",
        ),
      );

      const categories = getFilteredHouseholdGoodsCategories();
      $("#svdpHouseholdGoodsCatalog")
        .attr("data-catalog-loaded", "true")
        .html(
          categories.length
            ? categories.map(function (category) {
                return renderHouseholdGoodsCategory(category, !state.householdGoods.activeGroup || state.householdGoods.activeGroup === "all");
              }).join("")
            : '<div class="svdp-empty-state">No Household Goods categories match this search.</div>',
        );
      updateHouseholdGoodsCounts();
      updateSummary();
      window.setTimeout(updateAllPillArrows, 0);
    }

    function renderFilterPills(groups, active, attrName) {
      const pills = [{ key: "all", label: "All" }].concat(groups);
      return pills
        .map(function (group) {
          const key = group.key || group.id || group.slug;
          const label = group.label || group.name;
          return (
            '<button type="button" class="svdp-filter-pill' +
            (String(active) === String(key) ? " is-active" : "") +
            '" ' +
            attrName +
            '="' +
            escapeHtml(key) +
            '">' +
            escapeHtml(label) +
            "</button>"
          );
        })
        .join("");
    }

    function getFurnitureGroups() {
      return state.furniture.groups.map(function (group) {
        return { key: group.key, label: group.label };
      });
    }

    function getHouseholdGoodsGroups() {
      return state.householdGoods.groups.map(function (group) {
        return { key: String(group.id), label: group.name };
      });
    }

    function getFilteredFurnitureItems() {
      const search = normalizeSearch(state.furniture.search);
      return state.furniture.groups.reduce(function (items, group) {
        if (
          state.furniture.activeGroup !== "all" &&
          state.furniture.activeGroup !== group.key
        ) {
          return items;
        }
        return items.concat(
          (group.items || []).filter(function (item) {
            return matchesSearch(
              [item.name, item.categoryLabel, item.priceDisplay].join(" "),
              search,
            );
          }),
        );
      }, []);
    }

    function getFilteredHouseholdGoodsCategories() {
      const search = normalizeSearch(state.householdGoods.search);
      return state.householdGoods.groups.reduce(function (categories, group) {
        if (
          state.householdGoods.activeGroup !== "all" &&
          String(state.householdGoods.activeGroup) !== String(group.id)
        ) {
          return categories;
        }
        return categories.concat(
          (group.categories || []).filter(function (category) {
            return matchesSearch([category.name, group.name].join(" "), search);
          }),
        );
      }, []);
    }

    function renderFurnitureItem(item, showCategory) {
      const quantity = Number(state.furniture.selected[item.id] || 0);
      const estimate = getFurnitureEstimate(item);
      const categoryLabel = item.categoryLabel || "";
      const titleText = item.name + (showCategory && categoryLabel ? " (" + categoryLabel + ")" : "");
      const pricingText = "Retail price: " + (item.priceDisplay || "") + " • " + getSelectedOrganizationName() + " pays up to " + formatMoney(estimate);
      return (
        '<article class="svdp-catalog-item' +
        (quantity > 0 ? " is-selected" : "") +
        '">' +
        '<div class="svdp-catalog-item-main">' +
        '<div class="svdp-catalog-item-copy">' +
        '<h5 class="svdp-catalog-item-title" title="' + escapeHtml(titleText) + '">' +
        escapeHtml(item.name) +
        (showCategory && categoryLabel ? ' <span class="svdp-catalog-item-category">(' + escapeHtml(categoryLabel) + ")</span>" : "") +
        "</h5>" +
        '<p class="svdp-catalog-pricing-line" title="' + escapeHtml(pricingText) + '">' + escapeHtml(pricingText) + "</p>" +
        "</div>" +
        "</div>" +
        '<div class="svdp-catalog-item-controls">' +
        '<button type="button" class="svdp-qty-btn" data-furniture-adjust="decrement" data-item-id="' +
        item.id +
        '"' +
        (quantity <= 0 ? " disabled" : "") +
        ' aria-label="Remove one ' +
        escapeHtml(item.name) +
        '">-</button>' +
        '<span class="svdp-qty-value">' +
        quantity +
        "</span>" +
        '<button type="button" class="svdp-qty-btn" data-furniture-adjust="increment" data-item-id="' +
        item.id +
        '" aria-label="Add one ' +
        escapeHtml(item.name) +
        '">+</button>' +
        "</div>" +
        "</article>"
      );
    }

    function renderHouseholdGoodsCategory(category, showCategory) {
      const quantity = Number(state.householdGoods.selected[category.id] || 0);
      const estimate = Number(category.estimatedConferencePartnerCostPerUnit || 0);
      const categoryLabel = category.browseGroupName || "Household Goods";
      const titleText = category.name + (showCategory && categoryLabel ? " (" + categoryLabel + ")" : "");
      const pricingText = "Retail price: " + (category.priceDisplay || "") + " • " + getSelectedOrganizationName() + " pays up to " + formatMoney(estimate);

      return (
        '<article class="svdp-catalog-item' +
        (quantity > 0 ? " is-selected" : "") +
        '">' +
        '<div class="svdp-catalog-item-main">' +
        '<div class="svdp-catalog-item-copy">' +
        '<h5 class="svdp-catalog-item-title" title="' + escapeHtml(titleText) + '">' +
        escapeHtml(category.name) +
        (showCategory && categoryLabel ? ' <span class="svdp-catalog-item-category">(' + escapeHtml(categoryLabel) + ")</span>" : "") +
        "</h5>" +
        '<p class="svdp-catalog-pricing-line" title="' + escapeHtml(pricingText) + '">' + escapeHtml(pricingText) + "</p>" +
        "</div>" +
        "</div>" +
        '<div class="svdp-catalog-item-controls">' +
        '<button type="button" class="svdp-qty-btn" data-household-goods-adjust="decrement" data-category-id="' +
        category.id +
        '"' +
        (quantity <= 0 ? " disabled" : "") +
        ' aria-label="Remove one ' +
        escapeHtml(category.name) +
        '">-</button>' +
        '<span class="svdp-qty-value">' + quantity + "</span>" +
        '<button type="button" class="svdp-qty-btn" data-household-goods-adjust="increment" data-category-id="' +
        category.id +
        '"' +
        (Number(category.quantityMax || 0) > 0 && quantity >= Number(category.quantityMax) ? " disabled" : "") +
        ' aria-label="Add one ' +
        escapeHtml(category.name) +
        '">+</button>' +
        "</div>" +
        "</article>"
      );
    }

    function adjustFurnitureQuantity(itemId, delta) {
      const current = Number(state.furniture.selected[itemId] || 0);
      const next = Math.max(0, current + delta);
      if (next > 0) {
        state.furniture.selected[itemId] = next;
      } else {
        delete state.furniture.selected[itemId];
      }
      renderFurnitureCatalog();
    }

    function adjustHouseholdGoodsQuantity(categoryId, delta) {
      const current = Number(state.householdGoods.selected[categoryId] || 0);
      setHouseholdGoodsQuantity(categoryId, Math.max(0, current + delta));
    }

    function setHouseholdGoodsQuantity(categoryId, quantity) {
      quantity = Math.max(0, Math.floor(Number(quantity || 0)));
      const category = state.householdGoods.byId[Number(categoryId)];
      const categoryMax = category ? Number(category.quantityMax || 0) : 0;
      if (categoryMax > 0 && quantity > categoryMax) {
        showInlineError("household_goods", (category.name || "This category") + " is limited to " + categoryMax + ".");
        quantity = categoryMax;
      }
      if (quantity > 0 && !state.householdGoods.selected[categoryId]) {
        const selectedCount = Object.keys(state.householdGoods.selected).length;
        const limit = Number(
          state.householdGoods.limits.selected_category_limit || 0,
        );
        if (limit > 0 && selectedCount >= limit) {
          showInlineError(
            "household_goods",
            "Select no more than " + limit + " Household Goods categories.",
          );
          renderHouseholdGoodsCatalog();
          return;
        }
      }
      if (quantity > 0) {
        state.householdGoods.selected[categoryId] = quantity;
      } else {
        delete state.householdGoods.selected[categoryId];
      }
      renderHouseholdGoodsCatalog();
    }

    function syncDeliveryControls() {
      const showFields =
        state.deliveryRequested &&
        state.visibleSteps.indexOf("delivery") !== -1;
      $("#svdpDeliveryRequired").prop("checked", showFields);
      $('[data-delivery-choice="none"]')
        .toggleClass("is-selected", !showFields)
        .attr("aria-pressed", showFields ? "false" : "true");
      $('[data-delivery-choice="needed"]')
        .toggleClass("is-selected", showFields)
        .attr("aria-pressed", showFields ? "true" : "false");
      $("#svdpDeliveryAddressFields")
        .prop("hidden", !showFields)
        .attr("aria-hidden", showFields ? "false" : "true");
      $("#svdpDeliveryAddressFields")
        .find("input")
        .prop("disabled", !showFields);
      $("#svdpDeliveryEligibleText").text(
        "Delivery is available for: " +
          formatTypeList(getDeliveryEligibleTypes().map(typeShortLabel)),
      );
      if (showFields) {
        scheduleAddressSearch();
      } else {
        hideAddressSuggestions();
      }
    }

    function renderReview() {
      const sections = [];
      const entityLabel = getRequestorLabels().entity;
      const maxCost = getMaximumCostTotal();
      const hasDeliveryAvailable = getDeliveryEligibleTypes().length > 0;

      sections.push(
        renderReviewSection("Neighbor", "household", [
          '<strong class="svdp-review-primary-line">' +
            escapeHtml(getHouseholdName()) +
            "</strong>",
          "Date of Birth: " + escapeHtml(getFormattedDob()),
          "Household size: " +
            getHouseholdSize() +
            " " +
            (getHouseholdSize() === 1 ? "person" : "people"),
        ]),
      );

      const voucherRows = [];

      if (isSelected("clothing")) {
        voucherRows.push(
          '<div class="svdp-review-voucher-card">' +
            "<h5>Clothing Voucher</h5>" +
            "<p>Redeem in one visit within 30 days of issue date.</p>" +
            "</div>",
        );
      }

      if (isSelected("furniture")) {
        voucherRows.push(
          '<div class="svdp-review-voucher-card">' +
            "<h5>Furniture Voucher</h5>" +
            '<ul class="svdp-review-compact-list">' +
            getSelectedFurnitureItems()
              .map(function (item) {
                return (
                  "<li>" +
                  escapeHtml(item.name) +
                  " × " +
                  item.quantity +
                  " · " +
                  escapeHtml(
                    formatMoney(getFurnitureEstimate(item) * item.quantity),
                  ) +
                  "</li>"
                );
              })
              .join("") +
            "</ul>" +
            "<p><strong>Maximum " +
            escapeHtml(entityLabel) +
            " Furniture Cost: " +
            escapeHtml(formatMoney(getFurnitureEstimateTotal())) +
            "</strong></p>" +
            "</div>",
        );
      }

      if (isSelected("household_goods")) {
        voucherRows.push(
          '<div class="svdp-review-voucher-card">' +
            "<h5>Household Goods Voucher</h5>" +
            '<ul class="svdp-review-compact-list">' +
            getSelectedHouseholdGoodsCategories()
              .map(function (category) {
                return (
                  "<li>" +
                  escapeHtml(category.name) +
                  " × " +
                  category.quantity +
                  " · " +
                  escapeHtml(
                    formatMoney(
                      Number(
                        category.estimatedConferencePartnerCostPerUnit || 0,
                      ) * category.quantity,
                    ),
                  ) +
                  "</li>"
                );
              })
              .join("") +
            "</ul>" +
            "<p><strong>Maximum " +
            escapeHtml(entityLabel) +
            " Household Goods Cost: " +
            escapeHtml(formatMoney(getHouseholdGoodsEstimate())) +
            "</strong></p>" +
            "</div>",
        );
      }

      sections.push(
        renderReviewSection("Vouchers to Create", "assistance", voucherRows),
      );

      if (hasDeliveryAvailable) {
        const deliveryRows = [
          state.deliveryRequested
            ? "Delivery requested."
            : "No delivery requested.",
        ];

        if (state.deliveryRequested) {
          deliveryRows.push(
            "Delivery Address: " + escapeHtml(getDeliveryAddressDisplay()),
          );
          deliveryRows.push(
            "Delivery Fee: " + escapeHtml(formatMoney(deliveryFee)),
          );
        }

        sections.push(
          renderReviewSection("Delivery", "delivery", deliveryRows),
        );
      }

      sections.push(
        renderReviewSection(entityLabel + " Requestor", "requestor", [
          "Organization: " + escapeHtml(getSelectedConferenceLabel()),
          getRequestorLabels().name +
            ": " +
            escapeHtml($.trim(form.find('[name="vincentianName"]').val())),
          getRequestorLabels().email +
            ": " +
            escapeHtml($.trim(form.find('[name="vincentianEmail"]').val())),
        ]),
      );

      if (isSelected("furniture") || isSelected("household_goods")) {
        sections.push(
          '<section class="svdp-review-section svdp-review-total-section">' +
            "<h4>Maximum " +
            escapeHtml(entityLabel) +
            " Cost</h4>" +
            "<p>This is the maximum amount the " +
            escapeHtml(entityLabel) +
            " may be responsible for based on the selected voucher items" +
            (state.deliveryRequested && hasDeliveryAvailable
              ? " and delivery."
              : ".") +
            "</p>" +
            '<strong class="svdp-review-grand-total">' +
            escapeHtml(formatMoney(maxCost)) +
            "</strong>" +
            "</section>",
        );
      }

      $("#svdpReviewContent").html(sections.join(""));
    }

    function renderReviewSection(title, step, rows) {
      return (
        '<section class="svdp-review-section">' +
        '<div class="svdp-review-section-header"><h4>' +
        escapeHtml(title) +
        '</h4><button type="button" class="svdp-link-button" data-edit-step="' +
        escapeHtml(step) +
        '">Edit</button></div>' +
        "<ul>" +
        rows
          .map(function (row) {
            return "<li>" + row + "</li>";
          })
          .join("") +
        "</ul>" +
        "</section>"
      );
    }

    function submitRequest(confirmedMaxCost) {
      clearAllInlineErrors();
      for (let i = 0; i < state.visibleSteps.length; i += 1) {
        if (!validateStep(state.visibleSteps[i])) {
          state.stepIndex = i;
          render();
          return;
        }
      }

      if (
        !confirmedMaxCost &&
        (isSelected("furniture") || isSelected("household_goods"))
      ) {
        showMaxCostConfirmation();
        return;
      }

      const submitBtn = $("#svdpBuilderSubmit");
      submitBtn.prop("disabled", true).text("Submitting...");
      $("#svdpMobileSummaryAction, #svdpSummaryAction").prop("disabled", true);
      $.ajax({
        url: svdpVouchers.restUrl + "svdp/v1/vouchers/request-group",
        method: "POST",
        headers: { "X-WP-Nonce": svdpVouchers.nonce },
        data: JSON.stringify(buildSubmissionPayload()),
        contentType: "application/json",
      })
        .then(function (response) {
          state.confirmation = response;
          renderConfirmation(response);
          $("[data-step-panel]").prop("hidden", true);
          $('[data-step-panel="confirmation"]').prop("hidden", false);
          $(".svdp-builder-progress").prop("hidden", true);
          $(".svdp-builder-actions").prop("hidden", false);
          $("#svdpBuilderBack, #svdpBuilderNext").prop("hidden", true);
          $("#svdpBuilderSubmit")
            .prop("hidden", false)
            .prop("disabled", false)
            .attr("data-confirmation-action", "request-another")
            .text("Request Another Voucher");
          $(".svdp-builder-summary").prop("hidden", true);
          $("#svdpMobileSummaryBar").prop("hidden", true);
          $("body").removeClass("svdp-mobile-summary-visible");
          form.removeClass("svdp-summary-is-visible");
          $("html, body").animate({ scrollTop: form.offset().top - 20 }, 300);
        })
        .catch(function (xhr) {
          showMessage(getSubmitErrorMessage(xhr), "error");
        })
        .always(function () {
          if (
            submitBtn.attr("data-confirmation-action") !== "request-another"
          ) {
            submitBtn.prop("disabled", false).text("Submit Request");
          }
          $("#svdpMobileSummaryAction, #svdpSummaryAction").prop(
            "disabled",
            false,
          );
        });
    }

    function getSubmitErrorMessage(xhr) {
      const response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
      const message =
        response && response.message
          ? response.message
          : xhr && xhr.message
            ? xhr.message
            : "The request could not be submitted.";

      if (xhr && xhr.status === 409) {
        return (
          message +
          " Go back and adjust the selected voucher types, or use a different household if this was only a test."
        );
      }

      return message;
    }

    function checkDuplicatesForSelectedTypes() {
      let chain = $.Deferred().resolve().promise();
      state.selectedTypes.forEach(function (type) {
        chain = chain.then(function () {
          return $.ajax({
            url: svdpVouchers.restUrl + "svdp/v1/vouchers/check-duplicate",
            method: "POST",
            headers: { "X-WP-Nonce": svdpVouchers.nonce },
            data: JSON.stringify({
              firstName: $.trim(form.find('[name="firstName"]').val()),
              lastName: $.trim(form.find('[name="lastName"]').val()),
              dob: getFormattedDob(),
              conference: form.find('[name="conference"]').val(),
              voucherType: type,
              createdBy: "Vincentian",
            }),
            contentType: "application/json",
          }).then(function (response) {
            if (response && response.found) {
              return $.Deferred()
                .reject({
                  message:
                    "A recent " +
                    typeShortLabel(type) +
                    " voucher already exists for this household.",
                })
                .promise();
            }
            return response;
          });
        });
      });
      return chain;
    }

    function getMaximumCostTotal() {
      const deliveryTotal =
        state.deliveryRequested && state.visibleSteps.indexOf("delivery") !== -1
          ? deliveryFee
          : 0;

      return (
        getFurnitureEstimateTotal() +
        getHouseholdGoodsEstimate() +
        deliveryTotal
      );
    }

    function showMaxCostConfirmation() {
      const entityLabel = getRequestorLabels().entity;
      const furnitureTotal = getFurnitureEstimateTotal();
      const householdGoodsTotal = getHouseholdGoodsEstimate();
      const deliveryTotal =
        state.deliveryRequested && state.visibleSteps.indexOf("delivery") !== -1
          ? deliveryFee
          : 0;
      const maxTotal = furnitureTotal + householdGoodsTotal + deliveryTotal;

      const rows = [
        "<p>Please confirm the maximum " +
          escapeHtml(entityLabel) +
          " cost before submitting this voucher request.</p>",
        '<div class="svdp-modal-cost-rows">',
      ];

      if (isSelected("furniture")) {
        rows.push(
          '<div class="svdp-modal-cost-row"><span>Furniture</span><strong>' +
            escapeHtml(formatMoney(furnitureTotal)) +
            "</strong></div>",
        );
      }

      if (isSelected("household_goods")) {
        rows.push(
          '<div class="svdp-modal-cost-row"><span>Household Goods</span><strong>' +
            escapeHtml(formatMoney(householdGoodsTotal)) +
            "</strong></div>",
        );
      }

      if (deliveryTotal > 0) {
        rows.push(
          '<div class="svdp-modal-cost-row"><span>Delivery</span><strong>' +
            escapeHtml(formatMoney(deliveryTotal)) +
            "</strong></div>",
        );
      }

      rows.push(
        '<div class="svdp-modal-cost-row svdp-modal-cost-total"><span>Maximum ' +
          escapeHtml(entityLabel) +
          " Cost</span><strong>" +
          escapeHtml(formatMoney(maxTotal)) +
          "</strong></div>",
        "</div>",
        "<p>By submitting this request, you are creating the selected voucher or vouchers. The neighbor will still need to visit the store to see what is available.</p>",
      );

      $("#svdpMaxCostContent").html(rows.join(""));
      openMaxCostConfirmation();
    }

    function openMaxCostConfirmation() {
      maxCostModalReturnFocus = document.activeElement;
      $("#svdpMaxCostModal").prop("hidden", false);
      $("#svdpMaxCostConfirm").trigger("focus");
    }

    function closeMaxCostConfirmation(restoreFocus) {
      $("#svdpMaxCostModal").prop("hidden", true);

      if (
        restoreFocus &&
        maxCostModalReturnFocus &&
        typeof maxCostModalReturnFocus.focus === "function"
      ) {
        maxCostModalReturnFocus.focus();
      }

      maxCostModalReturnFocus = null;
    }

    function buildSubmissionPayload() {
      const payload = {
        firstName: $.trim(form.find('[name="firstName"]').val()),
        lastName: $.trim(form.find('[name="lastName"]').val()),
        dob: getFormattedDob(),
        adults: Number(form.find('[name="adults"]').val() || 0),
        children: Number(form.find('[name="children"]').val() || 0),
        conference: form.find('[name="conference"]').val(),
        voucherTypes: state.selectedTypes.slice(),
        vincentianName: $.trim(form.find('[name="vincentianName"]').val()),
        vincentianEmail: $.trim(form.find('[name="vincentianEmail"]').val()),
        deliveryRequested:
          state.deliveryRequested &&
          state.visibleSteps.indexOf("delivery") !== -1,
        deliveryAddress: {
          line1: $.trim(form.find('[name="deliveryLine1"]').val()),
          line2: $.trim(form.find('[name="deliveryLine2"]').val()),
          city: $.trim(form.find('[name="deliveryCity"]').val()),
          state: $.trim(form.find('[name="deliveryState"]').val()),
          zip: $.trim(form.find('[name="deliveryZip"]').val()),
        },
        deliveryLat: state.deliveryRequested
          ? $.trim(form.find('[name="deliveryLat"]').val())
          : "",
        deliveryLng: state.deliveryRequested
          ? $.trim(form.find('[name="deliveryLng"]').val())
          : "",
        deliveryVerified: state.deliveryRequested
          ? $.trim(form.find('[name="deliveryVerified"]').val())
          : "0",
        deliveryNormalized: state.deliveryRequested
          ? $.trim(form.find('[name="deliveryNormalized"]').val())
          : "",
      };

      if (isSelected("furniture")) {
        payload.furnitureItems = getSelectedFurnitureItems().map(
          function (item) {
            return { catalogItemId: item.id, quantity: item.quantity };
          },
        );
      }

      if (isSelected("household_goods")) {
        payload.householdGoodsCategories =
          getSelectedHouseholdGoodsCategories().map(function (category) {
            return { categoryId: category.id, quantity: category.quantity };
          });
      }

      return payload;
    }

    function renderConfirmation(response) {
      const rows = [];
      const voucherInfo = response.voucher_info || {};
      const storeHours = form.attr("data-store-hours") || "";
      const redemptionInstructions =
        form.attr("data-redemption-instructions") || "";
      const hasDeliveryAvailable = getDeliveryEligibleTypes().length > 0;

      rows.push(
        '<section class="svdp-review-section svdp-success-panel">' +
          "<h4>Voucher Request Submitted</h4>" +
          "<p>The voucher request was created successfully.</p>" +
          "</section>",
      );

      rows.push(
        renderReceiptSection("Neighbor", [
          '<strong class="svdp-review-primary-line">' +
            escapeHtml(getHouseholdName()) +
            "</strong>",
          "Date of Birth: " + escapeHtml(getFormattedDob()),
          "Household size: " +
            getHouseholdSize() +
            " " +
            (getHouseholdSize() === 1 ? "person" : "people"),
          "Organization: " + escapeHtml(getSelectedConferenceLabel()),
        ]),
      );

      rows.push(
        '<section class="svdp-review-section svdp-created-vouchers-section">' +
          "<h4>Created Vouchers</h4>" +
          state.selectedTypes
            .map(function (type) {
              const info = voucherInfo[type] || {};
              const voucherId =
                info.voucher_id || response.voucher_ids?.[type] || "";

              return (
                '<div class="svdp-created-voucher-card">' +
                "<h5>" +
                escapeHtml(typeShortLabel(type)) +
                "</h5>" +
                '<div class="svdp-receipt-row"><span>Voucher #</span><strong>' +
                escapeHtml(String(voucherId)) +
                "</strong></div>" +
                '<div class="svdp-receipt-row"><span>Redeem by</span><strong>' +
                escapeHtml(formatDateForDisplay(info.expiration_date)) +
                "</strong></div>" +
                '<div class="svdp-receipt-row"><span>Next eligible</span><strong>' +
                escapeHtml(formatDateForDisplay(info.next_eligible_date)) +
                "</strong></div>" +
                "</div>"
              );
            })
            .join("") +
          "</section>",
      );

      const instructionRows = [];

      if (storeHours) {
        instructionRows.push(
          "<strong>Store Hours</strong><br>" + escapeHtml(storeHours),
        );
      }

      if (redemptionInstructions) {
        instructionRows.push(
          "<strong>Instructions for the Neighbor</strong><br>" +
            escapeHtml(redemptionInstructions),
        );
      }

      if (hasDeliveryAvailable && state.deliveryRequested) {
        instructionRows.push(
          "<strong>Delivery</strong><br>Delivery was requested for:<br>" +
            escapeHtml(getDeliveryAddressDisplay()) +
            "<br>Delivery fee: " +
            escapeHtml(formatMoney(deliveryFee)),
        );
      }

      if (instructionRows.length > 0) {
        rows.push(
          renderReceiptSection("What to Tell the Neighbor", instructionRows),
        );
      }

      if (isSelected("furniture") || isSelected("household_goods")) {
        rows.push(
          '<section class="svdp-review-section svdp-stock-message">' +
            "<h4>Item Availability</h4>" +
            "<p>" +
            escapeHtml(STOCK_MESSAGE) +
            "</p>" +
            "</section>",
        );
      }

      $("#svdpConfirmationContent").html(rows.join(""));
    }

    function renderReceiptSection(title, rows) {
      return (
        '<section class="svdp-review-section svdp-receipt-section">' +
        "<h4>" +
        escapeHtml(title) +
        "</h4>" +
        rows
          .map(function (row) {
            return '<p class="svdp-receipt-line">' + row + "</p>";
          })
          .join("") +
        "</section>"
      );
    }

    function updateMobileSummaryBar(showSummary, currentStep) {
      const mobileBar = $("#svdpMobileSummaryBar");
      const mobileAction = $("#svdpMobileSummaryAction");
      const isMobile = window.matchMedia("(max-width: 960px)").matches;
      const shouldShowMobileBar = showSummary && isMobile;

      mobileBar.prop("hidden", !shouldShowMobileBar);
      $("body").toggleClass("svdp-mobile-summary-visible", shouldShowMobileBar);

      if (!shouldShowMobileBar) {
        return;
      }

      const types = state.selectedTypes
        .filter(function (type) {
          return type === "furniture" || type === "household_goods";
        })
        .map(typeShortLabel);

      const furnitureCount = getFurnitureItemCount();
      const householdGoodsCount = getHouseholdGoodsUnitCount();
      const totalCount = furnitureCount + householdGoodsCount;
      const estimate =
        getFurnitureEstimateTotal() + getHouseholdGoodsEstimate();
      const hasDeliveryAvailable = getDeliveryEligibleTypes().length > 0;
      const deliveryText =
        hasDeliveryAvailable && state.deliveryRequested
          ? "Delivery " + formatMoney(deliveryFee)
          : "No delivery requested";

      $("#svdpMobileSummaryTypes").text(formatTypeList(types));
      $("#svdpMobileSummaryTotal").text(
        totalCount +
          " " +
          (totalCount === 1 ? "item" : "items") +
          " • Maximum " +
          formatMoney(estimate),
      );
      $("#svdpMobileSummaryDelivery")
        .prop("hidden", !hasDeliveryAvailable)
        .text(deliveryText);

      mobileAction.text(currentStep === "review" ? "Submit" : "Continue");
      mobileAction.prop(
        "disabled",
        currentStep !== "review" && $("#svdpBuilderNext").prop("disabled"),
      );
    }

    function updateSummary() {
      const currentStep = state.visibleSteps[state.stepIndex] || "";
      const showSummary = shouldShowSummary(currentStep);
      const furnitureSelected = isSelected("furniture");
      const householdGoodsSelected = isSelected("household_goods");
      const hasDeliveryStep = state.visibleSteps.indexOf("delivery") !== -1;
      const hasDeliveryAvailable = getDeliveryEligibleTypes().length > 0;

      $(".svdp-builder-summary").prop("hidden", !showSummary);
      form.toggleClass("svdp-summary-is-visible", showSummary);
      updateMobileSummaryBar(showSummary, currentStep);

      $('[data-summary-row="furniture"]').prop("hidden", !furnitureSelected);
      $('[data-summary-row="household_goods"]').prop(
        "hidden",
        !householdGoodsSelected,
      );
      $('[data-summary-row="delivery"]').prop("hidden", !hasDeliveryAvailable);

      $("#svdpSelectedTypesSummary").html(
        state.selectedTypes
          .filter(function (type) {
            return type === "furniture" || type === "household_goods";
          })
          .map(function (type) {
            return (
              '<span class="svdp-summary-chip">' +
              escapeHtml(typeShortLabel(type)) +
              "</span>"
            );
          })
          .join(""),
      );

      $("#svdpSummaryFurnitureCount").text(getFurnitureItemCount());
      $("#svdpSummaryHouseholdGoodsUnits").text(getHouseholdGoodsUnitCount());

      $("#svdpSummaryEstimatedCost").text(
        formatMoney(getFurnitureEstimateTotal() + getHouseholdGoodsEstimate()),
      );

      $("#svdpSummaryDelivery").text(
        state.deliveryRequested && hasDeliveryAvailable
          ? formatMoney(deliveryFee)
          : "No delivery requested",
      );

      updateHouseholdGoodsCounts();
    }

    function shouldShowSummary(step) {
      if (!isSelected("furniture") && !isSelected("household_goods")) {
        return false;
      }

      return (
        [
          "furniture",
          "household_goods",
          "delivery",
          "requestor",
          "review",
        ].indexOf(step) !== -1
      );
    }

    function updateHouseholdCount() {
      const total = getHouseholdSize();
      $("#svdpHouseholdCount").text(
        "Household size: " + total + " " + (total === 1 ? "person" : "people"),
      );
    }

    function updateHouseholdGoodsCounts() {
      const selected = getSelectedHouseholdGoodsCategories();
      const categoryLimit = Number(
        state.householdGoods.limits.selected_category_limit || 0,
      );
      const unitCount = getHouseholdGoodsUnitCount();
      const voucherMax = Number(
        state.householdGoods.limits.voucher_quantity_max || 0,
      );
      $("#svdpHouseholdGoodsCategoryCount").text(
        categoryLimit > 0 ? "Selected categories: " + selected.length + " of " + categoryLimit : "Selected categories: " + selected.length,
      );
      $("#svdpHouseholdGoodsUnitCount").text(
        voucherMax > 0
          ? "Requested units: " + unitCount + " of " + voucherMax
          : "Requested units: " + unitCount,
      );
    }

    function syncConferenceTypeAvailability() {
      const allowed = getAllowedTypesForConference();
      state.selectedTypes = state.selectedTypes.filter(function (type) {
        return allowed.indexOf(type) !== -1;
      });
      if (state.selectedTypes.length === 0) {
        const fallback = availableTypes.find(function (type) {
          return allowed.indexOf(type) !== -1;
        });
        state.selectedTypes = fallback ? [fallback] : [availableTypes[0]];
      }
      sortSelectedTypes();
    }

    function getSelectedOrganizationType() {
      const select = form.find('select[name="conference"]');
      const hidden = form.find('input[type="hidden"][name="conference"]');

      if (select.length) {
        return (
          select.find("option:selected").attr("data-organization-type") ||
          "conference"
        );
      }

      return hidden.attr("data-organization-type") || "conference";
    }

    function getRequestorLabels() {
      const type = getSelectedOrganizationType();

      if (type === "partner") {
        return {
          entity: "Partner",
          name: "Partner Representative Name",
          email: "Partner Representative Email",
        };
      }

      return defaultRequestorLabels;
    }

    function getSelectedOrganizationName() {
      const typeLabels = {
        conference: "Conference",
        partner: "Partner",
        store: "Store",
      };
      const select = form.find('select[name="conference"]');
      if (select.length) {
        const option = select.find("option:selected");
        if (!option.val()) {
          return "Organization";
        }
        return typeLabels[option.attr("data-organization-type")] || "Organization";
      }
      const hidden = form.find('input[type="hidden"][name="conference"]');
      return typeLabels[hidden.attr("data-organization-type")] || "Organization";
    }

    function syncRequestorLabels() {
      const labels = getRequestorLabels();

      $("#svdpRequestorNameLabel").text(labels.name + " *");
      $("#svdpRequestorEmailLabel").text(labels.email + " *");
      $("#svdpSummaryEntityLabel").text(labels.entity);
      STEP_LABELS.requestor = labels.entity + " Requestor";
    }

    function selectedTypesAllowedByConference() {
      return state.selectedTypes.every(isTypeAllowedByConference);
    }

    function isTypeAllowedByConference(type) {
      return getAllowedTypesForConference().indexOf(type) !== -1;
    }

    function getAllowedTypesForConference() {
      const select = form.find('select[name="conference"]');
      const hidden = form.find('input[type="hidden"][name="conference"]');
      const raw = select.length
        ? select.find("option:selected").attr("data-allowed-voucher-types")
        : hidden.attr("data-allowed-voucher-types");
      return parseJsonAttribute(raw, availableTypes.slice());
    }

    function getDeliveryEligibleTypes() {
      return state.selectedTypes.filter(function (type) {
        return !!deliveryCapabilities[type];
      });
    }

    function sortSelectedTypes() {
      const order = ["clothing", "furniture", "household_goods"];
      state.selectedTypes.sort(function (a, b) {
        return order.indexOf(a) - order.indexOf(b);
      });
    }

    function getSelectedFurnitureItems() {
      return Object.keys(state.furniture.selected)
        .map(function (id) {
          const item = state.furniture.byId[Number(id)];
          if (!item) {
            return null;
          }
          return Object.assign({}, item, {
            quantity: Number(state.furniture.selected[id] || 0),
          });
        })
        .filter(Boolean);
    }

    function getSelectedHouseholdGoodsCategories() {
      return Object.keys(state.householdGoods.selected)
        .map(function (id) {
          const category = state.householdGoods.byId[Number(id)];
          if (!category) {
            return null;
          }
          return Object.assign({}, category, {
            quantity: Number(state.householdGoods.selected[id] || 0),
          });
        })
        .filter(Boolean);
    }

    function getFurnitureItemCount() {
      return getSelectedFurnitureItems().reduce(function (sum, item) {
        return sum + item.quantity;
      }, 0);
    }

    function getHouseholdGoodsUnitCount() {
      return getSelectedHouseholdGoodsCategories().reduce(function (
        sum,
        category,
      ) {
        return sum + category.quantity;
      }, 0);
    }

    function getFurnitureEstimateTotal() {
      return getSelectedFurnitureItems().reduce(function (sum, item) {
        return sum + getFurnitureEstimate(item) * item.quantity;
      }, 0);
    }

    function getHouseholdGoodsEstimate() {
      return getSelectedHouseholdGoodsCategories().reduce(function (
        sum,
        category,
      ) {
        return (
          sum +
          Number(category.estimatedConferencePartnerCostPerUnit || 0) *
            category.quantity
        );
      }, 0);
    }

    function getFurnitureEstimate(item) {
      const price =
        item.pricingType === "fixed"
          ? Number(item.priceFixed || 0)
          : Number(item.priceMax || 0);
      const discountType = item.discountType === "fixed" ? "fixed" : "percent";
      const discountValue = Number(
        item.discountValue != null ? item.discountValue : 50,
      );
      if (discountType === "fixed") {
        return Math.min(Math.max(discountValue, 0), price);
      }
      return price * (Math.min(Math.max(discountValue, 0), 100) / 100);
    }

    function getFormattedDob() {
      const input = form.find('[name="dob"]');
      const value = input.val();
      if (!value) {
        return "";
      }
      if (input.attr("type") === "date") {
        return value;
      }
      const parts = value.split("/");
      if (
        parts.length !== 3 ||
        parts[0].length !== 2 ||
        parts[1].length !== 2 ||
        parts[2].length !== 4
      ) {
        return "";
      }
      return parts[2] + "-" + parts[0] + "-" + parts[1];
    }

    function getHouseholdSize() {
      return (
        Math.max(0, Number(form.find('[name="adults"]').val() || 0)) +
        Math.max(0, Number(form.find('[name="children"]').val() || 0))
      );
    }

    function getHouseholdName() {
      return $.trim(
        form.find('[name="firstName"]').val() +
          " " +
          form.find('[name="lastName"]').val(),
      );
    }

    function getSelectedConferenceLabel() {
      const select = form.find('select[name="conference"]');
      if (select.length) {
        return select.find("option:selected").text().trim();
      }
      return form
        .find('input[name="conference"]')
        .closest("section")
        .find("p strong")
        .parent()
        .text()
        .replace("Organization:", "")
        .trim();
    }

    function getDeliveryAddressDisplay() {
      return [
        $.trim(form.find('[name="deliveryLine1"]').val()),
        $.trim(form.find('[name="deliveryLine2"]').val()),
        $.trim(form.find('[name="deliveryCity"]').val()),
        $.trim(form.find('[name="deliveryState"]').val()),
        $.trim(form.find('[name="deliveryZip"]').val()),
      ]
        .filter(Boolean)
        .join(", ");
    }

    function hasDeliveryAddressData() {
      return getDeliveryAddressDisplay() !== "";
    }

    function clearDeliveryAddress() {
      form
        .find(
          '[name="deliveryLine1"], [name="deliveryLine2"], [name="deliveryCity"], [name="deliveryState"], [name="deliveryZip"]',
        )
        .val("");
      clearAddressVerificationFields();
    }

    function scheduleAddressSearch() {
      window.clearTimeout(state.addressSearchTimer);
      if (!state.deliveryRequested) {
        hideAddressSuggestions();
        return;
      }
      const query = getDeliveryAddressDisplay();
      if (query.length < 3) {
        hideAddressSuggestions();
        return;
      }
      state.addressSearchTimer = window.setTimeout(function () {
        fetchAddressSuggestions(query);
      }, 300);
    }

    function fetchAddressSuggestions(query) {
      if (
        state.addressSearchRequest &&
        state.addressSearchRequest.readyState !== 4
      ) {
        state.addressSearchRequest.abort();
      }
      state.addressSearchQuery = query;
      state.addressSearchRequest = $.ajax({
        url: svdpVouchers.restUrl + "svdp/v1/address/search",
        method: "GET",
        headers: { "X-WP-Nonce": svdpVouchers.nonce },
        data: { q: query },
        success: function (response) {
          if (query !== state.addressSearchQuery || !state.deliveryRequested) {
            return;
          }
          renderAddressSuggestions(
            Array.isArray(response) ? response : response.results || [],
          );
        },
        error: hideAddressSuggestions,
      });
    }

    function renderAddressSuggestions(results) {
      const line1 = form.find('[name="deliveryLine1"]');
      let suggestions = getAddressSuggestionDropdown();
      if (!suggestions.length) {
        suggestions = $("<div>", {
          id: "svdpDeliveryAddressSuggestions",
          class: "svdp-address-suggestions",
          role: "listbox",
          hidden: true,
        });
        line1.after(suggestions);
      }
      state.addressSuggestions = results.slice(0, 5);
      if (!state.addressSuggestions.length) {
        suggestions.prop("hidden", true).empty();
        return;
      }
      suggestions
        .html(
          state.addressSuggestions
            .map(function (result, index) {
              const label = result.label || result.normalized_address || "";
              return (
                '<button type="button" role="option" data-address-suggestion-index="' +
                index +
                '">' +
                escapeHtml(label) +
                "</button>"
              );
            })
            .join(""),
        )
        .prop("hidden", false);
    }

    function selectAddressSuggestion(index) {
      const suggestion = state.addressSuggestions[index];
      if (!suggestion) {
        return;
      }
      const line2 = form.find('[name="deliveryLine2"]').val();
      form
        .find('[name="deliveryLine1"]')
        .val(
          suggestion.line1 ||
            suggestion.address_line_1 ||
            suggestion.label ||
            "",
        );
      form.find('[name="deliveryCity"]').val(suggestion.city || "");
      form.find('[name="deliveryState"]').val(suggestion.state || "");
      form
        .find('[name="deliveryZip"]')
        .val(suggestion.zip || suggestion.postcode || "");
      form.find('[name="deliveryLine2"]').val(line2);
      form.find('[name="deliveryLat"]').val(suggestion.latitude || "");
      form.find('[name="deliveryLng"]').val(suggestion.longitude || "");
      form.find('[name="deliveryVerified"]').val("1");
      form
        .find('[name="deliveryNormalized"]')
        .val(suggestion.normalized_address || suggestion.label || "");
      hideAddressSuggestions();
    }

    function clearAddressVerificationFields() {
      form
        .find(
          '[name="deliveryLat"], [name="deliveryLng"], [name="deliveryNormalized"]',
        )
        .val("");
      form.find('[name="deliveryVerified"]').val("0");
    }

    function getAddressSuggestionDropdown() {
      return $("#svdpDeliveryAddressSuggestions");
    }

    function hideAddressSuggestions() {
      getAddressSuggestionDropdown().prop("hidden", true).empty();
      state.addressSuggestions = [];
    }

    function updateAllPillArrows() {
      $("[data-pill-scroll]").each(function () {
        const shell = $(this);
        const row = shell.find(".svdp-filter-pills").first()[0];
        if (!row) {
          return;
        }
        const overflow = row.scrollWidth > row.clientWidth + 1;
        const atLeft = row.scrollLeft <= 1;
        const atRight = row.scrollLeft + row.clientWidth >= row.scrollWidth - 1;
        shell
          .find('[data-pill-arrow="left"]')
          .prop("hidden", !overflow || atLeft);
        shell
          .find('[data-pill-arrow="right"]')
          .prop("hidden", !overflow || atRight);
      });
    }

    function showInlineError(key, message, field) {
      const target = $('[data-error-for="' + key + '"]');
      target.text(message).prop("hidden", false);
      if (field && field.length) {
        field.trigger("focus");
      } else {
        target.attr("tabindex", "-1").trigger("focus");
      }
      return false;
    }

    function clearAllInlineErrors() {
      $(".svdp-inline-error").text("").prop("hidden", true);
      $("#svdpFormMessage").hide().removeClass("success error");
    }

    function showMessage(message, type) {
      $("#svdpFormMessage")
        .text(message)
        .removeClass("success error")
        .addClass(type)
        .show();
    }

    function isSelected(type) {
      return state.selectedTypes.indexOf(type) !== -1;
    }

    function typeShortLabel(type) {
      if (type === "household_goods") {
        return "Household Goods";
      }
      return type.charAt(0).toUpperCase() + type.slice(1);
    }

    function formatTypeList(labels) {
      if (labels.length <= 1) {
        return labels[0] || "";
      }
      if (labels.length === 2) {
        return labels[0] + " and " + labels[1];
      }
      return (
        labels.slice(0, -1).join(", ") + ", and " + labels[labels.length - 1]
      );
    }

    function formatMoney(value) {
      return "$" + Number(value || 0).toFixed(2);
    }

    function parseJsonAttribute(raw, fallback) {
      if (!raw) {
        return fallback;
      }
      try {
        return JSON.parse(raw);
      } catch (error) {
        return fallback;
      }
    }

    function normalizeSearch(value) {
      return String(value || "")
        .toLowerCase()
        .trim();
    }

    function matchesSearch(haystack, search) {
      if (!search) {
        return true;
      }
      const normalized = normalizeSearch(haystack);
      return search.split(/\s+/).every(function (token) {
        return normalized.indexOf(token) !== -1;
      });
    }

    function escapeHtml(value) {
      return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    function debounce(callback, delay) {
      let timer = null;
      return function () {
        window.clearTimeout(timer);
        timer = window.setTimeout(callback, delay);
      };
    }
  });
})(jQuery);

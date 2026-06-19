(function ($) {
  "use strict";

  // Global variables for card-based rendering
  let allVouchers = []; // All vouchers from API
  let filteredVouchers = []; // After search/filter applied
  let currentFilter = "all"; // Current filter selection
  let currentSort = "date-desc"; // Current sort selection
  let searchTerm = ""; // Current search term
  let displayCount = 5; // Number of cards to display
  let autoRefreshEnabled = true; // Control auto-refresh
  let autoRefreshInterval = null; // Store interval ID
  let pendingOverrideData = null; // For override modal
  let currentCoatVoucher = null; // For coat modal
  let currentRedemptionVoucher = null; // For redemption modal
  let itemValues = svdpVouchers.itemValues || { adult: 5.0, child: 3.0 }; // Item values from settings

  $(document).ready(function () {
    // Check if we're on the cashier station page
    if ($("#svdpVouchersContainer").length === 0) {
      return; // Not on cashier station page, exit
    }

    // Small delay to ensure DOM is fully ready
    setTimeout(function () {
      setupEventListeners();
      initializeEmergencyForm();
      initializeModal();
      initializeCoatModal();
      initializeRedemptionModal();
      loadItemValues();
      loadOverrideReasons(); // Load dropdown options
      loadVouchers();
      setupHeartbeat();

      // Auto-refresh every 30 seconds
      autoRefreshInterval = setInterval(function () {
        if (autoRefreshEnabled) {
          loadVouchers(true); // Silent refresh
        }
      }, 30000);
    }, 100);
  });

  /**
   * Load vouchers from REST API
   */
  function loadVouchers(silent = false) {
    if (!silent) {
      $("#svdpLoadingState").show();
      $("#svdpVouchersContainer").empty();
    }

    $.ajax({
      url: svdpVouchers.restUrl + "svdp/v1/vouchers",
      method: "GET",
      headers: {
        "X-WP-Nonce": svdpVouchers.nonce,
      },
      success: function (response) {
        allVouchers = response;
        filterAndRenderVouchers();
        $("#svdpLoadingState").hide();
      },
      error: function (xhr) {
        // Handle 403 (nonce expired) specially
        if (xhr.status === 403) {
          handleAuthError(
            function () {
              // Retry after nonce refresh
              loadVouchers(silent);
            },
            function () {
              // Nonce refresh failed - show error
              handleLoadError(xhr);
            },
          );
        } else {
          handleLoadError(xhr);
        }
      },
    });
  }

  /**
   * Handle errors when loading vouchers
   */
  function handleLoadError(xhr) {
    let errorMsg = "";

    if (xhr.status === 401 || xhr.status === 403 || xhr.status === 0) {
      // Authentication expired - STOP auto-refresh
      autoRefreshEnabled = false;
      errorMsg = '<div class="svdp-error-message">';
      errorMsg += "<h2>⚠️ Session Expired</h2>";
      errorMsg +=
        "<p>Your session has expired. Please refresh the page to continue.</p>";
      errorMsg +=
        '<button onclick="location.reload()" class="svdp-btn svdp-btn-primary">Refresh Page</button>';
      errorMsg += "</div>";
    } else {
      // Temporary error - KEEP auto-refresh running
      errorMsg = '<div class="svdp-error-message warning">';
      errorMsg += "<h3>⚠️ Temporary Error</h3>";
      errorMsg +=
        "<p>Could not load vouchers (Status: " +
        xhr.status +
        "). Will retry in 30 seconds...</p>";
      errorMsg +=
        '<button onclick="loadVouchers()" class="svdp-btn svdp-btn-secondary">Retry Now</button>';
      errorMsg += "</div>";
      // Don't set autoRefreshEnabled = false
    }

    $("#svdpLoadingState").hide();
    $("#svdpVouchersContainer").html(errorMsg);
    $("#svdpLoadMore").hide();
  }

  /**
   * Filter and render vouchers
   */
  function filterAndRenderVouchers() {
    // Filter vouchers
    filteredVouchers = allVouchers.filter(function (v) {
      // Search filter
      if (searchTerm) {
        const searchable = (
          v.first_name +
          " " +
          v.last_name +
          " " +
          v.dob +
          " " +
          v.conference_name
        ).toLowerCase();
        if (!searchable.includes(searchTerm)) return false;
      }

      // Status filter
      if (currentFilter === "active" && v.cashier_status !== "ready")
        return false;
      if (currentFilter === "redeemed" && v.cashier_status !== "redeemed")
        return false;
      if (currentFilter === "expired" && v.cashier_status !== "expired")
        return false;
      if (currentFilter === "coat-available" && !v.coat_eligible) return false;

      return true;
    });

    // Sort vouchers
    filteredVouchers.sort(function (a, b) {
      if (currentSort === "date-desc") {
        return (
          new Date(b.voucher_created_date) - new Date(a.voucher_created_date)
        );
      } else if (currentSort === "date-asc") {
        return (
          new Date(a.voucher_created_date) - new Date(b.voucher_created_date)
        );
      } else if (currentSort === "name-asc") {
        return (a.first_name + a.last_name).localeCompare(
          b.first_name + b.last_name,
        );
      } else if (currentSort === "name-desc") {
        return (b.first_name + b.last_name).localeCompare(
          a.first_name + a.last_name,
        );
      }
    });

    // Reset display count
    displayCount = 5;

    // Render
    renderVouchers();
    updateStats();
  }

  /**
   * Render vouchers as cards
   */
  function renderVouchers() {
    const container = $("#svdpVouchersContainer");
    container.empty();

    if (filteredVouchers.length === 0) {
      container.html(
        '<div class="svdp-empty-state">' +
          '<div class="svdp-empty-icon">📋</div>' +
          '<div class="svdp-empty-text">No vouchers found</div>' +
          "</div>",
      );
      $("#svdpLoadMore").hide();
      return;
    }

    // Render cards up to displayCount
    const vouchersToShow = filteredVouchers.slice(0, displayCount);
    vouchersToShow.forEach(function (voucher) {
      container.append(renderVoucherCard(voucher));
    });

    // Show/hide Load More button
    if (filteredVouchers.length > displayCount) {
      $("#svdpLoadMore").show();
    } else {
      $("#svdpLoadMore").hide();
    }

    // Attach event listeners to cards
    attachCardEventListeners();
  }

  /**
   * Format date from YYYY-MM-DD to MM/DD/YYYY
   */
  function formatDateToUS(dateStr) {
    if (!dateStr) return "";
    // Convert YYYY-MM-DD to MM/DD/YYYY
    const parts = dateStr.split("-");
    if (parts.length === 3) {
      return parts[1] + "/" + parts[2] + "/" + parts[0];
    }
    return dateStr; // Return as-is if not in expected format
  }

  /**
   * Render a single voucher card
   */
  function renderVoucherCard(voucher) {
    const statusClass = (voucher.cashier_status || voucher.status || "")
      .toLowerCase()
      .replace(/\s+/g, "-");
    const statusLabel = voucher.cashier_status_label || voucher.status;
    const statusDateLabel = voucher.cashier_status_date_label || "";
    const statusIcon = voucher.cashier_status_icon || "INFO";
    const statusBadgeClass = "svdp-badge-" + statusClass;
    const total = parseInt(voucher.adults) + parseInt(voucher.children);

    let card =
      '<div class="svdp-voucher-card svdp-card-' +
      statusClass +
      '" data-id="' +
      voucher.id +
      '">';

    // Card Header
    card += '<div class="svdp-card-header">';
    card +=
      '<div class="svdp-card-name">' +
      voucher.first_name +
      " " +
      voucher.last_name +
      "</div>";
    card += "<div>";
    card +=
      '<span class="svdp-card-status-icon" style="margin-right: 6px;">' +
      statusIcon +
      "</span>";
    card +=
      '<strong class="svdp-card-status-label">' + statusLabel + "</strong>";
    if (statusDateLabel) {
      card +=
        '<div class="svdp-card-status-date">' + statusDateLabel + "</div>";
    }
    // Voucher type badge
    if (voucher.voucher_type) {
      const voucherTypeCaps =
        voucher.voucher_type.charAt(0).toUpperCase() +
        voucher.voucher_type.slice(1);
      card +=
        '<span class="svdp-type-badge" style="background: #2271b1; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; margin-right: 5px;">' +
        voucherTypeCaps +
        "</span>";
    }
    card +=
      '<span class="svdp-status-badge ' +
      statusBadgeClass +
      '">' +
      statusLabel +
      "</span>";
    card += "</div>";
    card += "</div>";

    // Card Details
    card += '<div class="svdp-card-details">';
    card += '<div class="svdp-detail-item">';
    card += '<span class="svdp-detail-label">DOB</span>';
    card +=
      '<span class="svdp-detail-value">' +
      formatDateToUS(voucher.dob) +
      "</span>";
    card += "</div>";
    card += '<div class="svdp-detail-item">';
    card += '<span class="svdp-detail-label">Household</span>';
    card += '<span class="svdp-detail-value">' + total + " people</span>";
    card += "</div>";
    // Calculate items breakdown
    const itemsPerPerson = voucher.created_by === "Cashier" ? 3 : 7;
    const adultItems = parseInt(voucher.adults) * itemsPerPerson;
    const childItems = parseInt(voucher.children) * itemsPerPerson;
    const totalItems = adultItems + childItems;

    card += '<div class="svdp-detail-item">';
    card += '<span class="svdp-detail-label">Items Allowed</span>';
    card +=
      '<span class="svdp-detail-value">' +
      totalItems +
      " (" +
      adultItems +
      " adult, " +
      childItems +
      " child)</span>";
    card += "</div>";
    card += '<div class="svdp-detail-item">';
    card += '<span class="svdp-detail-label">Conference</span>';
    card +=
      '<span class="svdp-detail-value">' + voucher.conference_name + "</span>";
    card += "</div>";
    card += '<div class="svdp-detail-item">';
    card += '<span class="svdp-detail-label">Created</span>';
    card +=
      '<span class="svdp-detail-value">' +
      voucher.voucher_created_date +
      "</span>";
    card += "</div>";

    if (
      (voucher.cashier_status || "").toLowerCase() === "redeemed" &&
      voucher.redeemed_date
    ) {
      card += '<div class="svdp-detail-item">';
      card += '<span class="svdp-detail-label">Redeemed</span>';
      card +=
        '<span class="svdp-detail-value">' + voucher.redeemed_date + "</span>";
      card += "</div>";
      // Show items redeemed if available
      if (voucher.items_adult_redeemed || voucher.items_children_redeemed) {
        const totalRedeemed =
          (voucher.items_adult_redeemed || 0) +
          (voucher.items_children_redeemed || 0);
        card += '<div class="svdp-detail-item">';
        card += '<span class="svdp-detail-label">Items Redeemed</span>';
        card +=
          '<span class="svdp-detail-value">' +
          totalRedeemed +
          " (" +
          (voucher.items_adult_redeemed || 0) +
          " adult, " +
          (voucher.items_children_redeemed || 0) +
          " child)</span>";
        card += "</div>";
      }
      if (voucher.redemption_total_value) {
        card += '<div class="svdp-detail-item">';
        card += '<span class="svdp-detail-label">Redemption Value</span>';
        card +=
          '<span class="svdp-detail-value">$' +
          parseFloat(voucher.redemption_total_value).toFixed(2) +
          "</span>";
        card += "</div>";
      }
    }

    card += "</div>"; // End card details

    // Coat Info
    card += renderCoatInfo(voucher);

    // Card Actions
    card += '<div class="svdp-card-actions">';

    if ((voucher.cashier_status || "").toLowerCase() === "ready") {
      card +=
        '<button class="svdp-btn svdp-btn-success svdp-redeem-btn" data-id="' +
        voucher.id +
        '">';
      card += "Mark Redeemed";
      card += "</button>";
    }

    if (voucher.coat_eligible && voucher.coat_status !== "Issued") {
      card += '<button class="svdp-btn svdp-btn-coat svdp-issue-coat" ';
      card += 'data-id="' + voucher.id + '" ';
      card += 'data-adults="' + voucher.adults + '" ';
      card += 'data-children="' + voucher.children + '" ';
      card += 'data-firstname="' + voucher.first_name + '" ';
      card += 'data-lastname="' + voucher.last_name + '">';
      card += "🧥 Issue Coat";
      card += "</button>";
    }

    card += "</div>"; // End card actions
    card += "</div>"; // End card

    return card;
  }

  /**
   * Render coat eligibility info
   */
  function renderCoatInfo(voucher) {
    let html = "";

    if (voucher.coat_status === "Issued" && voucher.coat_issued_date) {
      const adults = voucher.coat_adults_issued || 0;
      const children = voucher.coat_children_issued || 0;
      html += '<div class="svdp-coat-info issued">';
      html +=
        "🧥 Coats Issued: " +
        adults +
        " adults, " +
        children +
        " children (" +
        voucher.coat_issued_date +
        ")";
      html += "</div>";
    } else if (!voucher.coat_eligible) {
      html += '<div class="svdp-coat-info not-eligible">';
      html += "🧥 Coat not eligible until " + voucher.coat_eligible_after;
      html += "</div>";
    } else {
      html += '<div class="svdp-coat-info eligible">';
      html += "🧥 Coat Available";
      html += "</div>";
    }

    return html;
  }

  /**
   * Attach event listeners to card action buttons
   */
  function attachCardEventListeners() {
    // Redeem button - open redemption modal
    $(".svdp-redeem-btn")
      .off("click")
      .on("click", function () {
        const voucherId = $(this).data("id");
        const voucher = allVouchers.find((v) => v.id == voucherId);
        if (voucher) {
          showRedemptionModal(voucher);
        }
      });

    // Issue coat button
    $(".svdp-issue-coat")
      .off("click")
      .on("click", function () {
        const voucherId = $(this).data("id");
        const adults = parseInt($(this).data("adults")) || 0;
        const children = parseInt($(this).data("children")) || 0;
        const firstName = $(this).data("firstname");
        const lastName = $(this).data("lastname");

        openCoatModal(voucherId, adults, children, firstName, lastName);
      });
  }

  /**
   * Load more vouchers (increase display count)
   */
  function loadMoreVouchers() {
    displayCount += 5;
    renderVouchers();
  }

  /**
   * Update stats display
   */
  function updateStats() {
    const today = new Date().toISOString().split("T")[0];

    const activeCount = allVouchers.filter(
      (v) => v.cashier_status === "ready",
    ).length;
    const redeemedToday = allVouchers.filter(
      (v) => v.cashier_status === "redeemed" && v.redeemed_date === today,
    ).length;
    const coatsAvailable = allVouchers.filter(
      (v) => v.coat_eligible && v.coat_status !== "Issued",
    ).length;

    $("#statActive").text(activeCount);
    $("#statRedeemed").text(redeemedToday);
    $("#statCoats").text(coatsAvailable);
    $("#statShowing").text(filteredVouchers.length);
  }

  /**
   * Setup all event listeners
   */
  function setupEventListeners() {
    // Search input
    $("#svdpSearchInput").on("input", function () {
      searchTerm = $(this).val().toLowerCase();
      filterAndRenderVouchers();
    });

    // Filter buttons
    $(".svdp-filter-btn").on("click", function () {
      $(".svdp-filter-btn").removeClass("active");
      $(this).addClass("active");
      currentFilter = $(this).data("filter");
      filterAndRenderVouchers();
    });

    // Sort dropdown
    $("#svdpSortDropdown").on("change", function () {
      currentSort = $(this).val();
      filterAndRenderVouchers();
    });

    // Load More button
    $("#svdpLoadMore").on("click", function () {
      loadMoreVouchers();
    });
  }

  /**
   * Update voucher status
   */
  function updateVoucherStatus(voucherId, newStatus) {
    $.ajax({
      url: svdpVouchers.restUrl + "svdp/v1/vouchers/" + voucherId + "/status",
      method: "PATCH",
      headers: {
        "X-WP-Nonce": svdpVouchers.nonce,
      },
      data: JSON.stringify({ status: newStatus }),
      contentType: "application/json",
      success: function () {
        loadVouchers(true); // Silent reload
      },
      error: function (xhr) {
        if (xhr.status === 403) {
          handleAuthError(
            function () {
              updateVoucherStatus(voucherId, newStatus); // Retry
            },
            function () {
              alert(
                "Error updating status: Session expired. Please refresh the page.",
              );
            },
          );
        } else {
          alert(
            "Error updating status: " +
              (xhr.responseJSON?.message || "Unknown error"),
          );
        }
      },
    });
  }

  /**
   * Initialize emergency form
   */
  function initializeEmergencyForm() {
    const form = $("#svdpEmergencyForm");
    const messageDiv = $("#svdpEmergencyMessage");

    form.on("submit", function (e) {
      e.preventDefault();

      const submitBtn = form.find('button[type="submit"]');
      submitBtn.prop("disabled", true).text("Processing...");
      messageDiv.hide().removeClass("success error");

      const formData = {
        firstName: $('input[name="firstName"]', form).val().trim(),
        lastName: $('input[name="lastName"]', form).val().trim(),
        dob: $('input[name="dob"]', form).val(),
        adults: parseInt($('input[name="adults"]', form).val()),
        children: parseInt($('input[name="children"]', form).val()),
        conference: "emergency",
      };

      // Check for duplicate
      $.ajax({
        url: svdpVouchers.restUrl + "svdp/v1/vouchers/check-duplicate",
        method: "POST",
        headers: {
          "X-WP-Nonce": svdpVouchers.nonce,
        },
        data: JSON.stringify({
          firstName: formData.firstName,
          lastName: formData.lastName,
          dob: formData.dob,
          createdBy: "Cashier",
        }),
        contentType: "application/json",
        success: function (response) {
          if (response.found && response.matchType === "exact") {
            // EXACT match - show override modal
            showOverrideModal(response, formData);
            submitBtn.prop("disabled", false).text("Create Emergency Voucher");
          } else if (response.found && response.matchType === "similar") {
            // SIMILAR match - show warning
            showSimilarWarning(response, formData, submitBtn, messageDiv, form);
          } else {
            // No match - create voucher
            createEmergencyVoucher(formData, submitBtn, messageDiv, form);
          }
        },
        error: function (xhr) {
          const error =
            xhr.responseJSON?.message || "Error checking for duplicates";
          messageDiv.html(error).addClass("error").fadeIn();
          submitBtn.prop("disabled", false).text("Create Emergency Voucher");
        },
      });
    });
  }

  /**
   * Show similar name warning
   */
  function showSimilarWarning(
    similarData,
    formData,
    submitBtn,
    messageDiv,
    form,
  ) {
    let warning =
      '<div style="background: #fff3cd; border: 2px solid #ffc107; padding: 15px; border-radius: 4px;">';
    warning += "<strong>⚠️ Similar Name(s) Found</strong><br><br>";
    warning +=
      "We found " +
      similarData.matches.length +
      " voucher(s) with similar name(s) and the same date of birth:<br><br>";

    similarData.matches.forEach(function (match) {
      warning +=
        '<div style="margin: 10px 0; padding: 10px; background: white; border-left: 3px solid #ffc107;">';
      warning +=
        "<strong>" + match.firstName + " " + match.lastName + "</strong><br>";
      warning += "DOB: " + match.dob + "<br>";
      warning += "Conference: " + match.conference + "<br>";
      warning += "Created: " + match.voucherCreatedDate;
      if (match.vincentianName) {
        warning += "<br>By: " + match.vincentianName;
      }
      warning += "</div>";
    });

    warning +=
      "<br><strong>Is this the same person with a name variation?</strong><br>";
    warning += '<div style="margin-top: 15px;">';
    warning +=
      '<button id="svdpProceedAnyway" class="svdp-btn svdp-btn-warning" style="margin-right: 10px;">No, Create New Voucher</button>';
    warning +=
      '<button id="svdpCancelSimilar" class="svdp-btn svdp-btn-secondary">Yes, Cancel</button>';
    warning += "</div>";
    warning += "</div>";

    messageDiv.html(warning).removeClass("success error").fadeIn();
    submitBtn.prop("disabled", false).text("Create Emergency Voucher");

    // Handle proceed
    $("#svdpProceedAnyway").on("click", function () {
      messageDiv.fadeOut();
      createEmergencyVoucher(formData, submitBtn, messageDiv, form);
    });

    // Handle cancel
    $("#svdpCancelSimilar").on("click", function () {
      messageDiv.fadeOut();
      form[0].reset();
    });
  }

  /**
   * Load override reasons from REST API
   */
  function loadOverrideReasons() {
    $.ajax({
      url: svdpVouchers.restUrl + "svdp/v1/override-reasons",
      method: "GET",
      success: function (reasons) {
        const select = $("#svdpOverrideReason");
        select.empty().append('<option value="">Select a reason...</option>');

        reasons.forEach(function (reason) {
          select.append(
            $("<option>", {
              value: reason.id,
              text: reason.reason_text,
            }),
          );
        });
      },
      error: function () {
        console.error("Failed to load override reasons");
      },
    });
  }

  /**
   * Show override modal for exact duplicate
   */
  function showOverrideModal(duplicateData, formData) {
    const modal = $("#svdpOverrideModal");
    const message = $("#svdpOverrideMessage");

    let msg =
      "<strong>" +
      duplicateData.firstName +
      " " +
      duplicateData.lastName +
      "</strong> already has a voucher:<br><br>";
    msg += "<strong>Conference:</strong> " + duplicateData.conference + "<br>";
    msg +=
      "<strong>Created:</strong> " + duplicateData.voucherCreatedDate + "<br>";
    msg +=
      "<strong>Next Eligible:</strong> " +
      duplicateData.nextEligibleDate +
      "<br><br>";
    msg +=
      "<strong>Manager approval required to override and create an emergency voucher.</strong>";

    message.html(msg);
    $("#svdpManagerCode").val("");
    $("#svdpOverrideReason").val("");

    // Store both formData and duplicateData
    pendingOverrideData = {
      formData: formData,
      duplicateData: duplicateData,
    };

    modal.css("display", "flex");
  }

  /**
   * Initialize override modal
   */
  function initializeModal() {
    $("#svdpCancelOverride").on("click", function () {
      // Save denied voucher when they cancel
      if (pendingOverrideData) {
        saveDeniedVoucher(
          pendingOverrideData.formData,
          pendingOverrideData.duplicateData,
        );
      }
      $("#svdpOverrideModal").hide();
      $("#svdpManagerCode").val("");
      $("#svdpOverrideReason").val("");
      pendingOverrideData = null;
    });

    $("#svdpConfirmOverride").on("click", function () {
      const managerCode = $("#svdpManagerCode").val().trim();
      const reasonId = $("#svdpOverrideReason").val();

      if (!managerCode || managerCode.length !== 6) {
        alert("Please enter a valid 6-digit manager code");
        return;
      }

      if (!reasonId) {
        alert("Please select a reason for the override");
        return;
      }

      if (!pendingOverrideData) {
        alert("Error: No pending override data");
        return;
      }

      // Validate manager code via REST API
      $.ajax({
        url: svdpVouchers.restUrl + "svdp/v1/managers/validate",
        method: "POST",
        headers: {
          "X-WP-Nonce": svdpVouchers.nonce,
        },
        data: JSON.stringify({ code: managerCode }),
        contentType: "application/json",
        success: function (response) {
          if (response.valid) {
            // Code is valid - attach manager_id and reason_id
            pendingOverrideData.formData.manager_id = response.id;
            pendingOverrideData.formData.reason_id = parseInt(reasonId);

            const form = $("#svdpEmergencyForm");
            const submitBtn = form.find('button[type="submit"]');
            const messageDiv = $("#svdpEmergencyMessage");

            $("#svdpOverrideModal").hide();
            $("#svdpManagerCode").val(""); // Clear for security
            $("#svdpOverrideReason").val("");

            createEmergencyVoucher(
              pendingOverrideData.formData,
              submitBtn,
              messageDiv,
              form,
            );
            pendingOverrideData = null;
          } else {
            alert("Invalid manager code. Please try again.");
            $("#svdpManagerCode").val("").focus();
          }
        },
        error: function () {
          alert("Error validating manager code. Please try again.");
        },
      });
    });
  }

  /**
   * Initialize coat modal
   */
  function initializeCoatModal() {
    // Update coat total when numbers change
    $("#svdpCoatAdults, #svdpCoatChildren").on("input", function () {
      updateCoatTotal();
    });

    // Cancel coat modal
    $("#svdpCancelCoat").on("click", function () {
      closeCoatModal();
    });

    // Submit coat form
    $("#svdpCoatForm").on("submit", function (e) {
      e.preventDefault();
      submitCoatIssuance();
    });

    // Close modal on background click
    $("#svdpCoatModal").on("click", function (e) {
      if (e.target === this) {
        closeCoatModal();
      }
    });
  }

  /**
   * Open coat modal
   */
  function openCoatModal(
    voucherId,
    maxAdults,
    maxChildren,
    firstName,
    lastName,
  ) {
    $("#svdpCoatVoucherId").val(voucherId);
    $("#svdpCoatVoucherInfo").html(
      "<strong>" +
        firstName +
        " " +
        lastName +
        "</strong><br>" +
        "Household: " +
        maxAdults +
        " adults, " +
        maxChildren +
        " children",
    );

    $("#svdpCoatMaxAdults").text(maxAdults);
    $("#svdpCoatMaxChildren").text(maxChildren);
    $("#svdpCoatAdults").attr("max", maxAdults).val(maxAdults);
    $("#svdpCoatChildren").attr("max", maxChildren).val(maxChildren);

    updateCoatTotal();
    $("#svdpCoatModal").fadeIn(200);
  }

  /**
   * Close coat modal
   */
  function closeCoatModal() {
    $("#svdpCoatModal").fadeOut(200);
    $("#svdpCoatForm")[0].reset();
  }

  /**
   * Update coat total count
   */
  function updateCoatTotal() {
    const adults = parseInt($("#svdpCoatAdults").val()) || 0;
    const children = parseInt($("#svdpCoatChildren").val()) || 0;
    const total = adults + children;
    $("#svdpCoatTotalCount").text(total);
    $("#svdpConfirmCoat").prop("disabled", total === 0);
  }

  /**
   * Submit coat issuance
   */
  function submitCoatIssuance() {
    const voucherId = $("#svdpCoatVoucherId").val();
    const adults = parseInt($("#svdpCoatAdults").val()) || 0;
    const children = parseInt($("#svdpCoatChildren").val()) || 0;
    const messageDiv = $("#svdpEmergencyMessage");

    const submitBtn = $("#svdpConfirmCoat");
    submitBtn.prop("disabled", true).text("Issuing...");

    $.ajax({
      url: svdpVouchers.restUrl + "svdp/v1/vouchers/" + voucherId + "/coat",
      method: "PATCH",
      contentType: "application/json",
      headers: {
        "X-WP-Nonce": svdpVouchers.nonce,
      },
      data: JSON.stringify({
        adults: adults,
        children: children,
      }),
      success: function (response) {
        closeCoatModal();
        showMessage(
          "success",
          "✅ Successfully issued " +
            response.total +
            " coat(s): " +
            response.adults +
            " adult, " +
            response.children +
            " children",
          messageDiv,
        );
        loadVouchers(true); // Silent reload

        setTimeout(function () {
          messageDiv.fadeOut();
        }, 5000);
      },
      error: function (xhr) {
        if (xhr.status === 403) {
          handleAuthError(
            function () {
              submitCoatIssuance(); // Retry
            },
            function () {
              showMessage(
                "error",
                "Session expired. Please refresh the page.",
                messageDiv,
              );
            },
          );
        } else {
          showMessage(
            "error",
            "Failed to issue coats: " +
              (xhr.responseJSON?.message || "Unknown error"),
            messageDiv,
          );
        }
      },
      complete: function () {
        submitBtn.prop("disabled", false).text("Issue Coats");
      },
    });
  }

  /**
   * Load item values from settings
   */
  function loadItemValues() {
    // Item values are now loaded from backend
    // Default values are set in global variables
    // In future, could make AJAX call to get current settings
  }

  /**
   * Initialize redemption modal
   */
  function initializeRedemptionModal() {
    // Cancel button
    $("#svdpCancelRedemption").on("click", function () {
      closeRedemptionModal();
    });

    // Form submission
    $("#svdpRedemptionForm").on("submit", function (e) {
      e.preventDefault();
      submitRedemption();
    });

    // Real-time item calculation
    $("#svdpItemsAdult, #svdpItemsChildren").on("input", function () {
      updateRedemptionSummary();
    });

    // Click outside modal to close
    $("#svdpRedemptionModal").on("click", function (e) {
      if ($(e.target).is("#svdpRedemptionModal")) {
        closeRedemptionModal();
      }
    });
  }

  /**
   * Show redemption modal
   */
  function showRedemptionModal(voucher) {
    currentRedemptionVoucher = voucher;

    // Calculate max items
    const householdSize = parseInt(voucher.adults) + parseInt(voucher.children);
    const maxAdultItems = parseInt(voucher.adults) * 7; // Assuming 7 items per person
    const maxChildItems = parseInt(voucher.children) * 7;
    const maxTotalItems = voucher.voucher_items_count || householdSize * 7;

    // Populate info section
    let info = "<strong>Voucher Details:</strong><br>";
    info += "Name: " + voucher.first_name + " " + voucher.last_name + "<br>";
    info +=
      "Household: " +
      voucher.adults +
      " adults, " +
      voucher.children +
      " children (" +
      householdSize +
      " total)<br>";
    info +=
      "Voucher Type: " +
      (voucher.voucher_type
        ? voucher.voucher_type.charAt(0).toUpperCase() +
          voucher.voucher_type.slice(1)
        : "Clothing") +
      "<br>";
    info += "Allowed Items: " + maxTotalItems;
    $("#svdpRedemptionVoucherInfo").html(info);

    // Set voucher ID
    $("#svdpRedemptionVoucherId").val(voucher.id);

    // Set max values
    $("#svdpMaxAdultItems").text(maxAdultItems);
    $("#svdpMaxChildItems").text(maxChildItems);
    $("#svdpMaxTotalItems").text(maxTotalItems);

    // Reset form
    $("#svdpItemsAdult").attr("max", maxAdultItems).val(0);
    $("#svdpItemsChildren").attr("max", maxChildItems).val(0);

    // Reset error
    $("#svdpRedemptionError").hide();

    updateRedemptionSummary();
    $("#svdpRedemptionModal").fadeIn(200);
  }

  /**
   * Close redemption modal
   */
  function closeRedemptionModal() {
    $("#svdpRedemptionModal").fadeOut(200);
    $("#svdpRedemptionForm")[0].reset();
    currentRedemptionVoucher = null;
  }

  /**
   * Update redemption summary
   */
  function updateRedemptionSummary() {
    const adultItems = parseInt($("#svdpItemsAdult").val()) || 0;
    const childItems = parseInt($("#svdpItemsChildren").val()) || 0;
    const totalItems = adultItems + childItems;

    const maxAdultItems = parseInt($("#svdpMaxAdultItems").text()) || 0;
    const maxChildItems = parseInt($("#svdpMaxChildItems").text()) || 0;
    const maxTotalItems = parseInt($("#svdpMaxTotalItems").text()) || 0;

    // Calculate estimated value
    const estimatedValue =
      adultItems * itemValues.adult + childItems * itemValues.child;

    // Update display
    $("#svdpTotalItems").text(totalItems);
    $("#svdpEstimatedValue").text(estimatedValue.toFixed(2));

    // Validation
    const errorDiv = $("#svdpRedemptionError");
    let errors = [];

    if (adultItems > maxAdultItems) {
      errors.push("Adult items exceed maximum (" + maxAdultItems + ")");
    }
    if (childItems > maxChildItems) {
      errors.push("Child items exceed maximum (" + maxChildItems + ")");
    }
    if (totalItems > maxTotalItems) {
      errors.push("Total items exceed maximum (" + maxTotalItems + ")");
    }
    if (totalItems === 0) {
      errors.push("Must provide at least 1 item");
    }

    if (errors.length > 0) {
      errorDiv.html("⚠️ " + errors.join("<br>⚠️ ")).show();
      $("#svdpConfirmRedemption").prop("disabled", true);
    } else {
      errorDiv.hide();
      $("#svdpConfirmRedemption").prop("disabled", false);
    }
  }

  /**
   * Submit redemption
   */
  function submitRedemption() {
    const voucherId = $("#svdpRedemptionVoucherId").val();
    const adultItems = parseInt($("#svdpItemsAdult").val()) || 0;
    const childItems = parseInt($("#svdpItemsChildren").val()) || 0;
    const messageDiv = $("#svdpEmergencyMessage");

    const submitBtn = $("#svdpConfirmRedemption");
    submitBtn.prop("disabled", true).text("Redeeming...");

    $.ajax({
      url: svdpVouchers.restUrl + "svdp/v1/vouchers/" + voucherId + "/status",
      method: "PATCH",
      contentType: "application/json",
      headers: {
        "X-WP-Nonce": svdpVouchers.nonce,
      },
      data: JSON.stringify({
        status: "Redeemed",
        items_adult: adultItems,
        items_children: childItems,
      }),
      success: function (response) {
        closeRedemptionModal();
        showMessage(
          "success",
          "✅ Voucher redeemed successfully! " +
            "Items: " +
            (adultItems + childItems) +
            ", Value: $" +
            response.redemption_value,
          messageDiv,
        );
        loadVouchers(true); // Silent reload

        setTimeout(function () {
          messageDiv.fadeOut();
        }, 5000);
      },
      error: function (xhr) {
        if (xhr.status === 403) {
          handleAuthError(
            function () {
              submitRedemption(); // Retry
            },
            function () {
              showMessage(
                "error",
                "Session expired. Please refresh the page.",
                messageDiv,
              );
            },
          );
        } else {
          showMessage(
            "error",
            "Failed to redeem voucher: " +
              (xhr.responseJSON?.message || "Unknown error"),
            messageDiv,
          );
        }
      },
      complete: function () {
        submitBtn.prop("disabled", false).text("Mark as Redeemed");
      },
    });
  }

  /**
   * Show message in message div
   */
  function showMessage(type, message, messageDiv) {
    messageDiv
      .html(message)
      .removeClass("success error")
      .addClass(type)
      .fadeIn();
  }

  /**
   * Create emergency voucher
   */
  function createEmergencyVoucher(formData, submitBtn, messageDiv, form) {
    $.ajax({
      url: svdpVouchers.restUrl + "svdp/v1/vouchers/create",
      method: "POST",
      headers: {
        "X-WP-Nonce": svdpVouchers.nonce,
      },
      data: JSON.stringify(formData),
      contentType: "application/json",
      success: function (response) {
        messageDiv
          .html("✅ Emergency voucher created successfully!")
          .removeClass("error")
          .addClass("success")
          .fadeIn();

        form[0].reset();
        loadVouchers(true); // Silent reload

        setTimeout(function () {
          messageDiv.fadeOut();
        }, 5000);

        submitBtn.prop("disabled", false).text("Create Emergency Voucher");
      },
      error: function (xhr) {
        if (xhr.status === 403) {
          handleAuthError(
            function () {
              createEmergencyVoucher(formData, submitBtn, messageDiv, form); // Retry
            },
            function () {
              const error = "Session expired. Please refresh the page.";
              messageDiv
                .html(error)
                .removeClass("success")
                .addClass("error")
                .fadeIn();
              submitBtn
                .prop("disabled", false)
                .text("Create Emergency Voucher");
            },
          );
        } else {
          const error = xhr.responseJSON?.message || "Error creating voucher";
          messageDiv
            .html(error)
            .removeClass("success")
            .addClass("error")
            .fadeIn();
          submitBtn.prop("disabled", false).text("Create Emergency Voucher");
        }
      },
    });
  }

  /**
   * Save denied voucher for tracking
   */
  function saveDeniedVoucher(formData, duplicateData) {
    const denialReason =
      "Duplicate found: " +
      duplicateData.firstName +
      " " +
      duplicateData.lastName +
      " received voucher from " +
      duplicateData.conference +
      " on " +
      duplicateData.voucherCreatedDate +
      ". Next eligible: " +
      duplicateData.nextEligibleDate;

    $.ajax({
      url: svdpVouchers.restUrl + "svdp/v1/vouchers/create-denied",
      method: "POST",
      headers: {
        "X-WP-Nonce": svdpVouchers.nonce,
      },
      data: JSON.stringify({
        firstName: formData.firstName,
        lastName: formData.lastName,
        dob: formData.dob,
        adults: formData.adults,
        children: formData.children,
        conference: "emergency",
        denialReason: denialReason,
        createdBy: "Cashier",
      }),
      contentType: "application/json",
      // Silent - no success/error handling needed
    });
  }

  /**
   * Handle authentication errors - primarily for cookie expiration
   *
   * With cookie-based auth (no nonce required), this mainly handles
   * session refresh via heartbeat to extend the auth cookie.
   */
  function handleAuthError(onSuccess, onError) {
    console.log("⚠ Authentication error, triggering session refresh...");

    // Check if WordPress heartbeat is available
    if (typeof wp === "undefined" || typeof wp.heartbeat === "undefined") {
      console.error("Heartbeat not available - cannot refresh session");
      if (onError) onError();
      return;
    }

    let refreshed = false;
    let timedOut = false;

    // Set up one-time listener for next heartbeat tick
    let tickHandler = function (event, data) {
      if (timedOut) return;

      if (data.svdp_heartbeat_status === "active") {
        refreshed = true;
        // Update nonce for backwards compatibility (server ignores it now)
        if (data.svdp_nonce) {
          svdpVouchers.nonce = data.svdp_nonce;
        }
        console.log("✓ Session refreshed via heartbeat");
        $(document).off("heartbeat-tick", tickHandler);
        if (onSuccess) onSuccess();
      } else if (data.svdp_heartbeat_status === "logged_out") {
        console.error("User logged out on server");
        $(document).off("heartbeat-tick", tickHandler);
        if (onError) onError();
      }
    };

    $(document).on("heartbeat-tick", tickHandler);

    // Trigger immediate heartbeat to refresh session
    wp.heartbeat.connectNow();

    // Timeout fallback in case heartbeat fails
    setTimeout(function () {
      timedOut = true;
      $(document).off("heartbeat-tick", tickHandler);

      if (!refreshed) {
        console.error("Heartbeat refresh timeout - session not refreshed");
        if (onError) onError();
      }
    }, 10000);
  }

  /**
   * Setup WordPress Heartbeat for session keep-alive
   *
   * With cookie-based authentication, heartbeat extends the auth cookie
   * to keep the session alive for long-running cashier shifts.
   */
  function setupHeartbeat() {
    // Only run if Heartbeat API is available
    if (typeof wp === "undefined" || typeof wp.heartbeat === "undefined") {
      console.warn(
        "WordPress Heartbeat API not available - session keep-alive disabled",
      );
      return;
    }

    // Send cashier station flag on every heartbeat
    $(document).on("heartbeat-send", function (event, data) {
      data.svdp_cashier_active = true;
    });

    // Receive heartbeat response
    $(document).on("heartbeat-tick", function (event, data) {
      if (data.svdp_heartbeat_status === "logged_out") {
        // User was logged out server-side
        handleLoadError({ status: 401 });
        autoRefreshEnabled = false;
      } else if (data.svdp_heartbeat_status === "active") {
        // Session is active - update nonce for backwards compatibility
        if (data.svdp_nonce) {
          svdpVouchers.nonce = data.svdp_nonce;
        }
        console.log("✓ Session kept alive via heartbeat");
      }
    });

    // Handle heartbeat errors (optional logging)
    $(document).on("heartbeat-error", function (event, jqXHR, textStatus) {
      console.warn("Heartbeat error:", textStatus);
      // Don't stop auto-refresh - heartbeat is supplementary
    });

    console.log("✓ Heartbeat session keep-alive enabled (15s interval)");
  }
})(jQuery);

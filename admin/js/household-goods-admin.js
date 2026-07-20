(function ($) {
  "use strict";

  $(document).ready(function () {
    initializeHouseholdGoodsAdmin();
  });

  function initializeHouseholdGoodsAdmin() {
    if (
      $("#svdp-household-goods-group-form").length === 0 &&
      $("#svdp-household-goods-limits-form").length === 0
    ) {
      return;
    }

    $("#svdp-add-hg-group").on("click", function () {
      submitGroupForm(
        $("#svdp-household-goods-group-form"),
        "svdp_add_household_goods_browse_group",
      );
    });

    $(document).on("click", ".svdp-edit-hg-group", function () {
      const button = $(this);
      $("#svdp-edit-hg-group-id").val(button.data("id"));
      $("#svdp-edit-hg-group-name").val(button.data("name"));
      $("#svdp-edit-hg-group-sort-order").val(button.attr("data-sort-order"));
      $("#svdp-edit-hg-group-modal").show();
    });

    $("#svdp-save-hg-group-edit").on("click", function () {
      submitGroupForm(
        $("#svdp-edit-household-goods-group-form"),
        "svdp_update_household_goods_browse_group",
        {
          id: $("#svdp-edit-hg-group-id").val(),
        },
      );
    });

    $(document).on("click", ".svdp-toggle-hg-group-active", function () {
      const id = $(this).data("id");
      const isActive = Number($(this).data("active")) === 1;
      submitStatusToggle(
        "svdp_set_household_goods_browse_group_active",
        id,
        isActive,
        "browse group",
      );
    });

    $("#svdp-add-hg-category").on("click", function () {
      submitCategoryForm(
        $("#svdp-household-goods-category-form"),
        "svdp_add_household_goods_category",
      );
    });

    $(document).on("click", ".svdp-edit-hg-category", function () {
      const button = $(this);
      $("#svdp-edit-hg-category-id").val(button.data("id"));
      $("#svdp-edit-hg-category-name").val(button.data("name"));
      $("#svdp-edit-hg-category-group").val(
        button.attr("data-browse-group-id"),
      );
      const editForm = $("#svdp-edit-household-goods-category-form");
      editForm.find('[name="pricing_type"]').val(button.attr("data-pricing-type"));
      editForm.find('[name="price_min"]').val(button.attr("data-price-min"));
      editForm.find('[name="price_max"]').val(button.attr("data-price-max"));
      editForm.find('[name="price_fixed"]').val(button.attr("data-price-fixed"));
      editForm.find('[name="show_price_as_max"]').prop("checked", Number(button.attr("data-show-price-as-max")) === 1);
      editForm.find('[name="discount_type"]').val(button.attr("data-discount-type"));
      editForm.find('[name="discount_value"]').val(button.attr("data-discount-value"));
      $("#svdp-edit-hg-category-quantity-max").val(
        button.attr("data-quantity-max"),
      );
      $("#svdp-edit-hg-category-guidance").val(button.attr("data-guidance"));
      $("#svdp-edit-hg-category-sort-order").val(
        button.attr("data-sort-order"),
      );
      $("#svdp-edit-hg-category-modal").show();
    });

    $("#svdp-save-hg-category-edit").on("click", function () {
      submitCategoryForm(
        $("#svdp-edit-household-goods-category-form"),
        "svdp_update_household_goods_category",
        {
          id: $("#svdp-edit-hg-category-id").val(),
        },
      );
    });

    $(document).on("click", ".svdp-toggle-hg-category-active", function () {
      const id = $(this).data("id");
      const isActive = Number($(this).data("active")) === 1;
      submitStatusToggle(
        "svdp_set_household_goods_category_active",
        id,
        isActive,
        "Household Goods category",
      );
    });

    $("#svdp-save-hg-limits").on("click", function () {
      $.ajax({
        url: svdpAdmin.ajaxUrl,
        method: "POST",
        data: {
          action: "svdp_update_household_goods_limits",
          nonce: svdpAdmin.nonce,
          selected_category_limit: $("#svdp-hg-selected-category-limit").val(),
          voucher_quantity_max: $("#svdp-hg-voucher-quantity-max").val(),
        },
        success: reloadOrAlert,
        error: function () {
          window.alert("Failed to save Household Goods limits.");
        },
      });
    });
  }

  function submitGroupForm(form, action, extraData) {
    const payload = $.extend(
      {
        action: action,
        nonce: svdpAdmin.nonce,
        name: form.find('[name="name"]').val().trim(),
        sort_order: form.find('[name="sort_order"]').val(),
      },
      extraData || {},
    );

    $.ajax({
      url: svdpAdmin.ajaxUrl,
      method: "POST",
      data: payload,
      success: reloadOrAlert,
      error: function () {
        window.alert("Failed to save the browse group.");
      },
    });
  }

  function submitCategoryForm(form, action, extraData) {
    const payload = $.extend(
      {
        action: action,
        nonce: svdpAdmin.nonce,
        name: form.find('[name="name"]').val().trim(),
        browse_group_id: form.find('[name="browse_group_id"]').val(),
        pricing_type: form.find('[name="pricing_type"]').val(),
        price_min: form.find('[name="price_min"]').val(),
        price_max: form.find('[name="price_max"]').val(),
        price_fixed: form.find('[name="price_fixed"]').val(),
        show_price_as_max: form.find('[name="show_price_as_max"]').is(':checked') ? 1 : 0,
        discount_type: form.find('[name="discount_type"]').val(),
        discount_value: form.find('[name="discount_value"]').val(),
        quantity_max: form.find('[name="quantity_max"]').val(),
        cashier_guidance: form.find('[name="cashier_guidance"]').val(),
        sort_order: form.find('[name="sort_order"]').val(),
      },
      extraData || {},
    );

    $.ajax({
      url: svdpAdmin.ajaxUrl,
      method: "POST",
      data: payload,
      success: reloadOrAlert,
      error: function () {
        window.alert("Failed to save the Household Goods category.");
      },
    });
  }

  function submitStatusToggle(action, id, isActive, label) {
    const targetActive = isActive ? 0 : 1;
    const actionLabel = isActive ? "archive" : "restore";

    if (
      !window.confirm(
        "Are you sure you want to " + actionLabel + " this " + label + "?",
      )
    ) {
      return;
    }

    $.ajax({
      url: svdpAdmin.ajaxUrl,
      method: "POST",
      data: {
        action: action,
        nonce: svdpAdmin.nonce,
        id: id,
        active: targetActive,
      },
      success: reloadOrAlert,
      error: function () {
        window.alert("Failed to update status.");
      },
    });
  }

  function reloadOrAlert(response) {
    if (response.success) {
      window.location.reload();
      return;
    }

    window.alert("Error: " + response.data);
  }
})(jQuery);

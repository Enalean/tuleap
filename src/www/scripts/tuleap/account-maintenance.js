/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/* global tuleap:readonly codendi:readonly */

!(function($) {
    var themes_list,
        popover_is_displayed = false;

    $(document).ready(function() {
        const ssh_keys_delete_button = $("#button-delete-keys"),
            svn_tokens_delete_button = $("#button-delete-svn-tokens"),
            access_keys_delete_button = $("#button-revoke-access-tokens"),
            ssk_keys_checkboxes = $('input[type="checkbox"][name="ssh_key_selected[]"]'),
            svn_tokens_checkboxes = $('input[type="checkbox"][name="svn-tokens-selected[]"]'),
            access_keys_checkboxes = $('input[type="checkbox"][name="access-keys-selected[]"]');

        loadAvatarReset();
        loadAvatarPreview();
        updateHeightValue();

        changeDeleteButtonStatusDependingCheckboxesStatus(
            ssh_keys_delete_button,
            ssk_keys_checkboxes
        );
        changeDeleteButtonStatusDependingCheckboxesStatus(
            svn_tokens_delete_button,
            svn_tokens_checkboxes
        );

        ssk_keys_checkboxes.change(function() {
            changeDeleteButtonStatusDependingCheckboxesStatus(
                ssh_keys_delete_button,
                ssk_keys_checkboxes
            );
        });

        svn_tokens_checkboxes.change(function() {
            changeDeleteButtonStatusDependingCheckboxesStatus(
                svn_tokens_delete_button,
                svn_tokens_checkboxes
            );
        });

        access_keys_checkboxes.change(function() {
            changeDeleteButtonStatusDependingCheckboxesStatus(
                access_keys_delete_button,
                access_keys_checkboxes
            );
        });

        $("[data-ssh_key_value]").one("click", displayFullSSHKey);
        $(window).resize(updateHeightValue);

        initThemeVariantSelection();
        initApiAccessKeyExpirationDatePicker();
    });

    function initApiAccessKeyExpirationDatePicker() {
        var field_times = document.querySelectorAll(".access-key-expiration-date-input");
        if (field_times.length != 0) {
            [].forEach.call(field_times, function(date_picker) {
                const dateToday = new Date();
                $(date_picker).datetimepicker({
                    language: codendi.locale,
                    pickTime: false,
                    pickSeconds: false,
                    pickDate: true,
                    startDate: dateToday
                });
            });
        }
    }

    function getResizedImageUrl(url) {
        var tmp_img = document.createElement("img");
        tmp_img.src = url;

        var canvas = document.createElement("canvas"),
            max_size = 100,
            width = tmp_img.width,
            height = tmp_img.height,
            source_x = 0,
            source_y = 0,
            source_width = width,
            source_height = height;

        if (width > max_size || height > max_size) {
            var size = Math.min(width, height);
            source_x = Math.round((width - size) / 2);
            source_y = Math.round((height - size) / 2);
            source_width = size;
            source_height = size;
        }
        canvas.width = max_size;
        canvas.height = max_size;
        var ctx = canvas.getContext("2d");
        ctx.drawImage(
            tmp_img,
            source_x,
            source_y,
            source_width,
            source_height,
            0,
            0,
            max_size,
            max_size
        );

        return canvas.toDataURL("image/png");
    }

    function setAvatarPreviewUrl(url) {
        var preview = document.querySelector(".change-avatar-modal-content > .avatar > img");
        if (!preview) {
            preview = document.createElement("img");
            document.querySelector(".change-avatar-modal-content > .avatar").appendChild(preview);
        }

        preview.src = url;
    }

    function loadAvatarReset() {
        var btn = document.getElementById("use-default-avatar-btn");

        if (!btn) {
            return;
        }

        btn.addEventListener("click", function() {
            document
                .querySelector(".change-avatar-modal-content")
                .classList.remove("change-avatar-modal-content-preview");
            document.querySelector(".change-avatar-modal-content > .avatar > img").remove();

            var use_default_avatar = document.getElementById("use-default-avatar");
            use_default_avatar.form.reset();
            use_default_avatar.value = 1;
        });
    }

    function useImageInPreviewIfItIsValid(url) {
        var img = document.createElement("img");

        img.onload = function() {
            var resized_image_url = getResizedImageUrl(url);
            setAvatarPreviewUrl(resized_image_url);
            document
                .querySelector(".change-avatar-modal-content")
                .classList.add("change-avatar-modal-content-preview");
            document.getElementById("use-default-avatar").value = 0;
        };
        img.src = url;
    }

    function loadAvatarPreview() {
        var input_file = document.getElementById("change-avatar-modal-actions-select-file");

        if (!input_file) {
            return;
        }

        input_file.addEventListener("change", function() {
            var url = URL.createObjectURL(this.files[0]);
            useImageInPreviewIfItIsValid(url);
        });
    }

    function displayFullSSHKey() {
        var $element = $(this);
        $element.html($element.attr("data-ssh_key_value"));
        $element.css("cursor", "auto");

        updateHeightValue();
    }

    function updateHeightValue() {
        $("#account-maintenance, #account-preferences").height("auto");

        var new_height = Math.max(
            $("#account-maintenance").height(),
            $("#account-preferences").height()
        );

        $("#account-preferences").height(new_height);
    }

    function changeDeleteButtonStatusDependingCheckboxesStatus(button, checkboxes) {
        var at_least_one_checkbox_is_checked = false;

        checkboxes.each(function() {
            if ($(this)[0].checked) {
                at_least_one_checkbox_is_checked = true;
                return;
            }
        });

        if (at_least_one_checkbox_is_checked) {
            button.removeAttr("disabled");
        } else {
            button.attr("disabled", true);
        }
    }

    function initThemeVariantSelection() {
        if ($('.select-user-preferences[name="user_theme"]').length > 0) {
            bindThemeSelect();
            fetchThemeVariants();

            if (!tuleap.browserCompatibility.isIE7()) {
                $(".navbar-inner").attr(
                    "data-content",
                    codendi.locales.account.theme_variant_preview
                );
                $(".navbar-inner").popover({
                    placement: "bottom",
                    trigger: "manual"
                });
            }
        }
    }

    function bindThemeSelect() {
        var theme_selector = $('.select-user-preferences[name="user_theme"]');

        theme_selector.change(function() {
            fetchThemeVariants();
            $(".navbar-inner").popover("hide");
            popover_is_displayed = false;
        });
    }

    function fetchThemeVariants() {
        var theme_selector = $('.select-user-preferences[name="user_theme"]');

        $.ajax({
            url: "/account/get_available_theme_variants.php",
            data: {
                theme: theme_selector.val()
            },
            cache: false,
            dataType: "json",
            success: listThemeVariantsIfExist,
            error: function() {
                listThemeVariantsIfExist([]);
            }
        });
    }

    function listThemeVariantsIfExist(themes) {
        themes_list = themes.values;
        var i,
            selected_theme_variant = themes.selected,
            themes_length = themes_list.length,
            theme_variant_group = $("#theme_variant_group"),
            theme_variant_list = $("#theme_variant_list"),
            theme_picker,
            theme_picker_container;

        theme_variant_list.empty();
        theme_variant_group.css("display", "none");

        if (themes_length > 0) {
            if (!tuleap.browserCompatibility.isIE7()) {
                addCSSFilestoDOM(themes.css_files);
            }

            theme_variant_group.css("display", "block");
            for (i = 0; i < themes_length; ++i) {
                theme_picker_container = $("<span></span>")
                    .addClass("theme_picker_container")
                    .val(themes_list[i])
                    .click(selectThemeVariant);

                if (themes_list[i] === selected_theme_variant) {
                    theme_picker_container.addClass("checked");
                    $("#current_theme_variant").val(themes_list[i]);
                    applyThemeVariantToBody(themes_list[i]);
                }

                theme_picker = $("<span></span>")
                    .addClass("theme_variant")
                    .addClass(themes_list[i]);

                theme_variant_list.append(theme_picker_container.append(theme_picker));
                if ((i + 1) % 6 === 0) {
                    theme_variant_list.append("<br />");
                }
            }
        }

        updateHeightValue();
    }

    function addCSSFilestoDOM(css_files) {
        if ($("body[class*=FlamingParrot_]").length === 0) {
            return;
        }

        css_files.forEach(function(file) {
            if ($('link[rel*=style][href="' + file + '"]').length === 0) {
                $("head").append('<link rel="stylesheet" type="text/css" href="' + file + '"/>');
            }
        });
    }

    function selectThemeVariant() {
        var current_theme_variant = $("#current_theme_variant"),
            theme_variant_list = $("#theme_variant_list");

        theme_variant_list.children().each(function(i, element) {
            $(element).removeClass("checked");
        });
        $(this).addClass("checked");
        current_theme_variant.val(this.value);

        applyThemeVariantToBody(this.value);
        if (!popover_is_displayed) {
            $(".navbar-inner").popover("show");
            popover_is_displayed = true;
        }
    }

    function applyThemeVariantToBody(theme_variant) {
        if (tuleap.browserCompatibility.isIE7()) {
            return;
        }

        for (var i = 0, themes_length = themes_list.length; i < themes_length; ++i) {
            $(document.body).removeClass(themes_list[i]);
        }
        $(document.body).addClass(theme_variant);
    }
})(window.jQuery);

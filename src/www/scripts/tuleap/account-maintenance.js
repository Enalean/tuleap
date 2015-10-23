/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

!(function ($) {

    var themes_list,
        popover_is_displayed = false;

    $(document).ready(function(){
        var $delete_button = $('#button-delete-keys');
        var $checkboxs     = $('input[type="checkbox"][name="ssh_key_selected[]"]');

        updateHeightValue();
        modifyDeleteKeysButtonStatus($delete_button);

        $checkboxs.change(function() {
            modifyDeleteKeysButtonStatus($delete_button);
        });

        $('[data-ssh_key_value]').one('click', displayFullSSHKey);
        $(window).resize(updateHeightValue);

        initThemeVariantSelection();
    });

    function displayFullSSHKey() {
        var $element = $(this);
        $element.html($element.attr('data-ssh_key_value'));
        $element.css('cursor', 'auto');

        updateHeightValue();
    }

    function updateHeightValue() {
        $('#account-maintenance, #account-preferences').height('auto');

        var new_height = Math.max(
            $('#account-maintenance').height(),
            $('#account-preferences').height()
        );

        $('#account-preferences').height(new_height);
    };

    function modifyDeleteKeysButtonStatus($delete_button) {
        var nb_checked = $('input[type="checkbox"][name="ssh_key_selected[]"]:checked').length;

        if (nb_checked === 0) {
            $delete_button.attr('disabled', true);
            return;
        }

        $delete_button.removeAttr('disabled');
    };

    function initThemeVariantSelection() {
        if ($('.select-user-preferences[name="user_theme"]').length > 0) {
            bindThemeSelect();
            fetchThemeVariants();

            if (! tuleap.browserCompatibility.isIE7()) {
                $('.navbar-inner').attr('data-content',codendi.locales.account.theme_variant_preview);
                $('.navbar-inner').popover({
                    placement: 'bottom',
                    trigger: 'manual'
                });
            }
        }
    }

    function bindThemeSelect() {
        var theme_selector = $('.select-user-preferences[name="user_theme"]');

        theme_selector.change(function() {
            fetchThemeVariants();
            $('.navbar-inner').popover('hide');
            popover_is_displayed = false;
        });
    }

    function fetchThemeVariants() {
        var theme_selector = $('.select-user-preferences[name="user_theme"]');

        $.ajax({
            url: '/account/get_available_theme_variants.php',
            data: {
                theme: theme_selector.val()
            },
            cache: false,
            dataType: 'json',
            success: listThemeVariantsIfExist,
            error: function() {
                listThemeVariantsIfExist([]);
            }
        });
    }

    function listThemeVariantsIfExist(themes) {
        themes_list = themes.values;
        var i,
            selected_theme_variant       = themes.selected,
            themes_length        = themes_list.length,
            theme_variant_group = $('#theme_variant_group'),
            theme_variant_list  = $('#theme_variant_list'),
            theme_picker,
            theme_picker_container;

        theme_variant_list.empty();
        theme_variant_group.css('display', 'none');

        if (themes_length > 0) {
            if (! tuleap.browserCompatibility.isIE7()) {
                addCSSFilestoDOM(themes.css_files);
            }

            theme_variant_group.css('display', 'block');
            for (i = 0; i < themes_length; ++i) {
                theme_picker_container = $('<span></span>')
                    .addClass('theme_picker_container')
                    .val(themes_list[i])
                    .click(selectThemeVariant);

                if (themes_list[i] === selected_theme_variant) {
                    theme_picker_container.addClass('checked');
                    $('#current_theme_variant').val(themes_list[i]);
                    applyThemeVariantToBody(themes_list[i]);
                }

                theme_picker = $('<span></span>')
                    .addClass('theme_variant')
                    .addClass(themes_list[i]);

                theme_variant_list.append(
                    theme_picker_container.append(
                        theme_picker
                    )
                );
                if ((i + 1) % 6 === 0) {
                    theme_variant_list.append('<br />');
                }
            }
        }

        updateHeightValue();
    }

    function addCSSFilestoDOM(css_files) {
        if ($('body[class*=FlamingParrot_]').length === 0) {
            return;
        }

        css_files.forEach(function(file) {
            if ($('link[rel*=style][href="'+file+'"]').length === 0) {
                $("head").append('<link rel="stylesheet" type="text/css" href="'+file+'"/>');
            }
        });
    }

    function selectThemeVariant() {
        var current_theme_variant = $('#current_theme_variant'),
            theme_variant_list    = $('#theme_variant_list');

        theme_variant_list.children().each(function(i, element) {
            $(element).removeClass('checked');
        });
        $(this).addClass('checked');
        current_theme_variant.val(this.value);

        applyThemeVariantToBody(this.value);
        if (!popover_is_displayed) {
            $('.navbar-inner').popover('show');
            popover_is_displayed = true;
        }
    }

    function applyThemeVariantToBody(theme_variant) {
        if (tuleap.browserCompatibility.isIE7()) {
            return;
        }

        for (var i = 0, themes_length = themes_list.length ; i < themes_length; ++i) {
            $(document.body).removeClass(themes_list[i]);
        }
        $(document.body).addClass(theme_variant);
    }

})(window.jQuery);

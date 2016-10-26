/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

    function formatOptionIcon(option) {
        return $('<i class="fa fa-' + option.id + '"></i>');
    }

    function initIconSelector() {
        tlp.select2(document.querySelector('#icon'), {
            minimumResultsForSearch: Infinity,
            templateResult         : formatOptionIcon,
            templateSelection      : formatOptionIcon
        });
    }

    function formatOptionColor(option) {
        return $('<span class="' + option.id + '"></span>');
    }

    function initColorSelector() {
        tlp.select2(document.querySelector('#color'), {
            minimumResultsForSearch: Infinity,
            templateResult         : formatOptionColor,
            templateSelection      : formatOptionColor,
        });
    }

    function syncPreviewButton() {
        $('.provider-name').keyup(function() {
            $(this).parents('.tlp-modal-body').find('#provider-add-modal-provider-button-preview > button > span').html($(this).val());
        });

        $('.provider-icon-selector').change(function() {
            var icon = $(this).parents('.tlp-modal-body').find('#provider-add-modal-provider-button-preview > button > i');
            icon.removeClass();
            icon.addClass('tlp-button-icon fa fa-' + $(this).val());
        });

        $('.provider-color-selector').change(function() {
            var button = $(this).parents('.tlp-modal-body').find('#provider-add-modal-provider-button-preview > button');
            button.removeClass();
            button.addClass('tlp-button-primary tlp-button-large');

            if ($(this).val()) {
                button.addClass($(this).val());
            }
        });
    }

    function initCreationModal() {
        var modal_providers_config_element = document.getElementById('siteadmin-config-providers-modal-create');
        var modal_providers_config         = tlp.modal(modal_providers_config_element);

        document.querySelector('.add-provider-button').addEventListener('click', function() {
            modal_providers_config.toggle();
        });
    }

    $(document).ready(function() {
        initIconSelector();
        initColorSelector();

        syncPreviewButton();
        initCreationModal();
    });
})(window.jQuery);

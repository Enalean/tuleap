/**
 * Copyright (c) Enalean - 2015. All rights reserved
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

!(function($) {

    var ESC_KEYCODE = 27;

    $(function() {
        bindFilterEvent();
        bindCheckboxesEvent();
        bindDeleteEvent();
        projectAutocompleter();
    });

    function bindFilterEvent() {
        $('#allowed-projects-list-actions').on('keyup', '#filter-projects', function(event) {
            if (event.keyCode == ESC_KEYCODE) {
                $(this).val('');
            }

            $('#allowed-projects-list table').find('td:not(:caseInsensitiveContains(' +  $(this).val() + '))').parent().hide();
            $('#allowed-projects-list table').find('td:caseInsensitiveContains(' +  $(this).val() + ')').parent().show();
        });
    }

    function bindCheckboxesEvent() {
        var checkboxes = $('#allowed-projects-list input[type="checkbox"]:not(#check-all)'),
            select_all = $('#check-all');

        (function toggleAll() {
            select_all.change(function() {
                if($(this).is(':checked')) {
                    checkboxes.each(function() {
                        $(this).prop('checked', 'checked');
                    });

                } else {
                    checkboxes.each(function() {
                        $(this).prop('checked', '');
                    });
                }

                toggleRevokeSelectedButton();
            });
        })();

        (function projectCheckboxesEvent() {
            checkboxes.change(function() {
                select_all.prop('checked', '');
                toggleRevokeSelectedButton();
            });
        })();

        function toggleRevokeSelectedButton() {
            if ($('#allowed-projects-list input[type="checkbox"]:not(#check-all):checked').length > 0) {
                $('#revoke-project').prop('disabled', '');
            } else {
                $('#revoke-project').prop('disabled', 'disabled');
            }
        }
    }

    function bindDeleteEvent() {
        $('#revoke-project').on('click', function(event) {
            event.preventDefault();
            $('#revoke-modal').modal('show');
        });

        $('#revoke-confirm').click(function() {
            $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'revoke-project')
                .attr('value', '1')
                .appendTo('#projects-allowed-form');

            $('#projects-allowed-form').submit();
        });

    }

    function projectAutocompleter() {
        var autocompleter = new ProjectAutoCompleter('project-to-allow', codendi.imgroot, false);
        autocompleter.registerOnLoad();
    }

})(window.jQuery);
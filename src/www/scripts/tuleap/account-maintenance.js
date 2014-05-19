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

    $(document).ready(function(){
        var delete_button = $('#button-delete-keys');
        var checkboxs     = $('input[type="checkbox"][name="ssh_key_selected[]"]');

        initializeHeightValue();
        modifyDeleteKeysButtonStatus(delete_button);

        checkboxs.change(function() {
            modifyDeleteKeysButtonStatus(delete_button);
        });
    });

    function initializeHeightValue() {
        var right_height = $('#account-handler').height();

        $('#account-maintenance').height(right_height);
        $('#account-preferences').height(right_height);
    };

    function modifyDeleteKeysButtonStatus(delete_button) {
        var nb_checked = $('input[type="checkbox"][name="ssh_key_selected[]"]:checked').length;

        if (nb_checked === 0) {
            delete_button.attr('disabled', true);
            return;
        }

        delete_button.removeAttr('disabled');
    };

})(window.jQuery);
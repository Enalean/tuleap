/**
 * Copyright (c) Enalean, 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

(function($) {
    $(function() {
        var help_modal_trigger = document.querySelector(
            '#dropdown-help > li:first-child > a[href="/help/"]'
        );
        var contact_support_modal;

        help_modal_trigger.addEventListener("click", function(event) {
            event.preventDefault();

            if (!contact_support_modal) {
                $.get(
                    "/plugins/mytuleap_contact_support/index.php?action=get-modal-conten&is-burning-parrot-compatible=0"
                ).then(function(data) {
                    var modal_container = document.createElement("div");
                    modal_container.innerHTML = data;

                    contact_support_modal = modal_container.querySelector(".contact-support-modal");
                    document.body.appendChild(contact_support_modal);

                    $(contact_support_modal)
                        .on("shown", tuleap.contact_support_modal_shown)
                        .modal("show");
                });
            } else {
                $(contact_support_modal).modal("show");
            }
        });
    });
})(jQuery);

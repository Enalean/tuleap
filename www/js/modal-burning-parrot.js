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
    var help_modal_trigger = document.querySelector(
        '#nav-dropdown-content-help-dropdown > .nav-dropdown-content-items-lists > a[href="/help/"]'
    );
    var contact_support_modal;

    help_modal_trigger.addEventListener("click", function(event) {
        event.preventDefault();

        if (!contact_support_modal) {
            $.get(
                "/plugins/mytuleap_contact_support/index.php?action=get-modal-content&is-burning-parrot-compatible=1"
            ).then(function(data) {
                var modal_container = document.createElement("div");
                modal_container.innerHTML = data;
                document.body.appendChild(modal_container.querySelector(".tlp-modal"));

                contact_support_modal = tlp.modal(
                    document.body.querySelector(".contact-support-modal")
                );
                contact_support_modal.addEventListener(
                    "tlp-modal-shown",
                    tuleap.contact_support_modal_shown
                );
                contact_support_modal.show();
            });
        } else {
            contact_support_modal.show();
        }
    });
})(jQuery);

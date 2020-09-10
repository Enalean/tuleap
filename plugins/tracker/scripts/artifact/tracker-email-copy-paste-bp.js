/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/* global jQuery:readonly */

import { createModal } from "../../../../src/themes/tlp/src/js";

(function ($, window, document) {
    /**
     * Return true if the copy is supported by the current browser
     *
     * This must be called in a user-intitited call stack else it returns false for security reasons
     */
    function isCopyToClipboardSupported() {
        var supported = document.queryCommandSupported("copy");
        if (supported) {
            // Firefox before 41 always return true for queryCommandSupported('copy'), so double check
            try {
                document.execCommand("copy");
            } catch (e) {
                supported = false;
            }
        }

        return supported;
    }

    function initModal(target, trigger) {
        const modal = createModal(target);
        trigger.addEventListener("click", (event) => {
            event.preventDefault();
            modal.toggle();
        });

        const input = target.querySelector(".tracker-email-copy-to-clipboard-input");
        const button = target.querySelector(".tracker-email-copy-to-clipboard-button");
        if (input && button) {
            if (isCopyToClipboardSupported()) {
                button.addEventListener("click", () => {
                    input.select();
                    document.execCommand("copy");
                    const info = target.querySelector(".tracker-email-copy-to-clipboard-info");
                    if (info) {
                        info.classList.add("shown");
                        setTimeout(() => info.classList.remove("shown"), 2000);
                    }
                });
            } else {
                button.remove();
            }
        }
    }

    $(function () {
        const reply_link = document.querySelector(".email-tracker-reply");
        const reply_target = document.getElementById("reply-by-mail-modal-info");
        if (reply_link && reply_target) {
            initModal(reply_target, reply_link);
        }

        const create_link = document.querySelector("a[href='#create-by-mail-modal-info']");
        const create_target = document.getElementById("create-by-mail-modal-info");
        if (create_link && create_target) {
            initModal(create_target, create_link);
        }
    });
})(jQuery, window, document);

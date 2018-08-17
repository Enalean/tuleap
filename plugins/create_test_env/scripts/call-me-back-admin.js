/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

document.addEventListener("DOMContentLoaded", () => {
    const selector = document.getElementById("call-me-back-message-select-language");

    initCKEditor();
    selector.addEventListener("change", switchLanguage);

    function initCKEditor() {
        const messages = document.querySelectorAll('textarea[id^="call-me-back-message-"]');

        for (const message of messages) {
            const textarea_id = message.getAttribute("id");

            CKEDITOR.replace(textarea_id, {
                toolbar: tuleap.ckeditor.toolbar
            });

            CKEDITOR.on("instanceReady", function() {
                switchLanguage();
            });
        }
    }

    function switchLanguage() {
        const language_id = selector.value,
            cke_messages = document.querySelectorAll('div[id^="cke_call-me-back-message-"]');

        for (const cke_message of cke_messages) {
            if (cke_message.getAttribute("id") === "cke_call-me-back-message-" + language_id) {
                cke_message.classList.remove("hidden");
                cke_message.focus();
            } else {
                cke_message.classList.add("hidden");
            }
        }
    }
});

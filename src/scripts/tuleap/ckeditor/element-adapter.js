/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { getGettextProvider } from "../gettext/gettext-factory.js";

export function isUploadEnabled(element) {
    return document.body.querySelector("[data-upload-is-enabled]") && element.dataset.uploadUrl;
}

export function informUsersThatTheyCanPasteImagesInEditor(element) {
    if (typeof element.dataset.helpId === "undefined") {
        return;
    }
    const help_block = document.getElementById(element.dataset.helpId);
    if (!help_block) {
        return;
    }

    if (help_block.textContent) {
        return;
    }

    const p = document.createElement("p");
    p.innerText = getGettextProvider().gettext(
        "You can drag 'n drop or paste image directly in the editor."
    );
    help_block.appendChild(p);
}

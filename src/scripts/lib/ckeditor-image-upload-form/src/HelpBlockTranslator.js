/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

export class HelpBlockTranslator {
    constructor(doc, textarea, gettext_provider) {
        this.doc = doc;
        this.textarea = textarea;
        this.gettext_provider = gettext_provider;
    }

    informUsersThatTheyCanPasteImagesInEditor() {
        if (typeof this.textarea.dataset.helpId === "undefined") {
            return;
        }
        const help_block = this.doc.getElementById(this.textarea.dataset.helpId);
        if (!help_block) {
            return;
        }

        if (help_block.textContent) {
            return;
        }

        const p = this.doc.createElement("p");
        p.textContent = this.gettext_provider.gettext(
            "You can drag 'n drop or paste image directly in the editor."
        );
        help_block.appendChild(p);
    }
}

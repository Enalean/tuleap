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

import { HelpBlock } from "./HelpBlock";

export class HelpBlockFactory {
    constructor(doc, gettext_provider, textarea) {
        this.doc = doc;
        this.gettext_provider = gettext_provider;
        this.textarea = textarea;
    }

    createHelpBlock(textarea) {
        if (typeof textarea.dataset.helpId === "undefined") {
            return null;
        }
        const help_block_element = this.doc.getElementById(textarea.dataset.helpId);
        if (!help_block_element) {
            return null;
        }

        if (help_block_element.textContent) {
            return null;
        }

        const p = this.doc.createElement("p");
        p.textContent = this.gettext_provider.gettext(
            "You can drag 'n drop or paste image directly in the editor.",
        );
        help_block_element.appendChild(p);

        return new HelpBlock(help_block_element);
    }
}

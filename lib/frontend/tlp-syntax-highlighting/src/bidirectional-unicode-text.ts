/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { POTENTIALLY_DANGEROUS_BIDIRECTIONAL_CHARACTERS } from "@tuleap/potentially-dangerous-bidirectional-characters";

export function markPotentiallyDangerousBidirectionalUnicodeText(html_content: string): string {
    const regex_potentially_dangerous_bidirectional_characters = new RegExp(
        "[" + POTENTIALLY_DANGEROUS_BIDIRECTIONAL_CHARACTERS.join("") + "]",
        "g",
    );

    return html_content.replaceAll(
        regex_potentially_dangerous_bidirectional_characters,
        replaceMatchedChar,
    );
}

function replaceMatchedChar(match: string): string {
    const span = document.createElement("span");
    span.classList.add("syntax-highlight-invisible-char");
    span.dir = "ltr";
    span.title = "\\u" + match.charCodeAt(0).toString(16);
    span.textContent = match;
    return span.outerHTML;
}

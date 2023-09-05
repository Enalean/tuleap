/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import type { UpdateFunctionWithMethods } from "hybrids";
import { html } from "hybrids";
import { Classifier } from "./highlight/Classifier";
import { HighlightedText } from "./highlight/HighlightedText";
export function highlightFilterElements(
    content: string | number | undefined,
    search: string | number | undefined,
): UpdateFunctionWithMethods<unknown> {
    const text_content = content?.toString();
    if (
        text_content === "" ||
        text_content === undefined ||
        search === "" ||
        search === undefined
    ) {
        return html`${text_content}`;
    }

    const classifier = Classifier(search.toString());
    const templates = classifier.classify(text_content).map((highlighted_text) => {
        if (!HighlightedText.isHighlight(highlighted_text)) {
            return html`${highlighted_text.content}`;
        }
        return html`<span class="highlight">${highlighted_text.content}</span>`;
    });
    return html`${templates}`;
}

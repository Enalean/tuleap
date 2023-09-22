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

import type { GettextProvider } from "@tuleap/gettext";
import DOMPurify from "dompurify";
import type { TemplateResult } from "lit-html";
import { html } from "lit-html";
import { unsafeHTML } from "lit-html/directives/unsafe-html.js";
import { until } from "lit-html/directives/until.js";

const buildErrorMessage = (error: Error, gettext_provider: GettextProvider): TemplateResult => html`
    <div class="alert alert-error">
        ${gettext_provider.gettext("There was an error in the Markdown preview:")}
        <br />
        ${error.message}
    </div>
`;

export function createPreviewArea(
    promise_of_html: Promise<string> | null,
    gettext_provider: GettextProvider,
): TemplateResult {
    if (promise_of_html === null) {
        return html``;
    }
    return html`
        <div>
            ${until(
                promise_of_html.then(
                    (html_string) =>
                        unsafeHTML(
                            DOMPurify.sanitize(html_string, {
                                ADD_TAGS: ["tlp-mermaid-diagram", "tlp-syntax-highlighting"],
                            }),
                        ),
                    (error) => buildErrorMessage(error, gettext_provider),
                ),
            )}
        </div>
    `;
}

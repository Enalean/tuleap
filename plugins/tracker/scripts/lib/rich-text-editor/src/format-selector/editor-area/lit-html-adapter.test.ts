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
import { html } from "lit-html";
import { renderHTMLOrTextEditor, renderMarkdownEditor } from "./lit-html-adapter";
import { stripLitExpressionComments } from "../../test-helper";
import { initGettextSync } from "@tuleap/gettext";

describe(`lit-html-adapter`, () => {
    let gettext_provider: GettextProvider, doc: Document, mount_point: HTMLDivElement;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        mount_point = doc.createElement("div");
        gettext_provider = initGettextSync("rich-text-editor", {}, "en_US");
    });

    describe(`renderMarkdownEditor()`, () => {
        it(`will wrap the controls in a div with .rte_format classname before the textarea
            and will show the additional buttons / elements for Markdown`, () => {
            const selectbox = html`<select></select>`;
            const preview_button = html`<button>Preview</button>`;
            const help_button = html`<button>Help</button>`;
            const textarea = html`<textarea></textarea>`;
            const preview_area = html`<div>Preview Area</div>`;
            const hidden_format_input = html`<input type="hidden" />`;
            renderMarkdownEditor(
                {
                    mount_point,
                    selectbox,
                    preview_button,
                    help_button,
                    textarea,
                    preview_area,
                    hidden_format_input,
                },
                gettext_provider,
            );
            expect(stripLitExpressionComments(mount_point.innerHTML)).toMatchInlineSnapshot(`
                "
                            <div class="rte_format">
                                Format:<select></select><input type="hidden"><button>Preview</button><button>Help</button>
                            </div>
                            <textarea></textarea><div>Preview Area</div>
                        "
            `);
        });
    });

    describe(`renderHTMLOrTextEditor()`, () => {
        it(`will wrap the controls in a div with .rte_format classname, before the textarea`, () => {
            const selectbox = html`<select></select>`;
            const textarea = html`<textarea></textarea>`;

            renderHTMLOrTextEditor(
                {
                    mount_point,
                    selectbox,
                    textarea,
                },
                gettext_provider,
            );
            expect(stripLitExpressionComments(mount_point.innerHTML)).toMatchInlineSnapshot(`
                "
                            <div class="rte_format">
                                Format:<select></select>
                            </div>
                            <textarea></textarea>
                        "
            `);
        });
    });
});

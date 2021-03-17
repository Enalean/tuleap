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
import { render, html } from "lit-html";
import type { SelectboxPresenter } from "./lit-html-adapter";
import {
    createSelect,
    createSyntaxHelpButton,
    renderRichTextEditorArea,
    wrapTextArea,
} from "./lit-html-adapter";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "../../../../../constants/fields-constants";

const emptyFunction = (): void => {
    //Do nothing
};

describe(`lit-html-adapter`, () => {
    let gettext_provider: GettextProvider, doc: Document, mount_point: HTMLDivElement;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        mount_point = doc.createElement("div");
        gettext_provider = {
            gettext: (msgid: string): string => msgid,
        };
    });

    describe(`createSelect()`, () => {
        let presenter: SelectboxPresenter;
        beforeEach(() => {
            presenter = {
                id: "whimper",
                name: "depletive",
                selected_value: TEXT_FORMAT_COMMONMARK,
                options: [TEXT_FORMAT_COMMONMARK, TEXT_FORMAT_HTML, TEXT_FORMAT_TEXT],
                formatChangedCallback: emptyFunction,
            };
        });

        it(`given a presenter, it will create a selectbox with the given format options`, () => {
            const template = createSelect(presenter, gettext_provider);
            render(template, mount_point);
            expect(mount_point.innerHTML).toMatchInlineSnapshot(`
                <!---->
                <select class="input-small" data-test="format-select" id="whimper" name="depletive">
                  <!---->
                  <option value="commonmark" selected="">
                    <!---->Markdown
                    <!---->
                  </option>
                  <!---->
                  <option value="html">
                    <!---->HTML
                    <!---->
                  </option>
                  <!---->
                  <option value="text">
                    <!---->Text
                    <!---->
                  </option>
                  <!---->
                </select>
                <!---->
            `);
        });

        it(`on "input" event from the select, it will call the formatChangedCallback with the new value`, () => {
            const callback = jest.spyOn(presenter, "formatChangedCallback");
            const template = createSelect(presenter, gettext_provider);
            render(template, mount_point);

            const select = mount_point.querySelector("[data-test=format-select]");
            if (!(select instanceof HTMLSelectElement)) {
                throw new Error("Could not find the select that was just rendered");
            }
            select.value = TEXT_FORMAT_HTML;
            select.dispatchEvent(new InputEvent("input"));
            expect(callback).toHaveBeenCalledWith(TEXT_FORMAT_HTML);
        });
    });

    describe(`createSyntaxHelpButton()`, () => {
        it(`will create a custom element with the button and the popover content`, () => {
            const template = createSyntaxHelpButton(gettext_provider);
            render(template, mount_point);
            expect(mount_point.innerHTML).toMatchSnapshot();
        });
    });

    describe(`wrapTextArea()`, () => {
        it(`will just wrap the existing textarea in a TemplateResult so that it can be displaced`, () => {
            const textarea = doc.createElement("textarea");
            const template = wrapTextArea(textarea);
            render(template, mount_point);
            expect(mount_point.firstElementChild).toBe(textarea);
        });
    });

    describe(`renderRichTextEditorArea()`, () => {
        it(`will wrap the controls in a div with .rte_format classname, before the textarea`, () => {
            const selectbox = html`
                <select></select>
            `;
            const help_button = html`
                <button>Help</button>
            `;
            const textarea = html`
                <textarea></textarea>
            `;
            renderRichTextEditorArea(
                {
                    mount_point,
                    selectbox,
                    helper_button: help_button,
                    textarea,
                },
                gettext_provider
            );
            expect(mount_point.innerHTML).toMatchInlineSnapshot(`
                <!---->
                <div class="rte_format">
                  Format:
                  <!---->
                  <select></select>
                  <!---->
                  <button>Help</button>

                </div>

                <textarea></textarea>

                <!---->
            `);
        });
    });
});

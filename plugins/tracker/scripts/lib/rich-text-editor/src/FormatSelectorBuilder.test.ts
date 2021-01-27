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

import { FormatSelectorBuilder, FormatSelectorPresenter } from "./FormatSelectorBuilder";
import { DocumentInterface } from "./DocumentInterface";
import { FlamingParrotDocumentAdapter } from "./FlamingParrotDocumentAdapter";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "../../../constants/fields-constants";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe(`FormatSelectorBuilder`, () => {
    let doc: Document, builder: FormatSelectorBuilder;
    beforeEach(() => {
        doc = createDocument();
        const gettext_provider = {
            gettext: (english: string): string => english,
        };
        const document_interface: DocumentInterface = new FlamingParrotDocumentAdapter(doc);
        builder = new FormatSelectorBuilder(document_interface, gettext_provider);
    });

    describe(`createFormatSelectbox()`, () => {
        it.each([[TEXT_FORMAT_TEXT], [TEXT_FORMAT_HTML], [TEXT_FORMAT_COMMONMARK]])(
            `given a presenter with default format %s, the corresponding option will be selected`,
            (format) => {
                const presenter = {
                    id: "some_id",
                    name: "some_name",
                    default_format: format,
                    formatChangedCallback: jest.fn(),
                };
                const wrapper = builder.createFormatSelectbox(presenter);

                const options = wrapper.firstElementChild?.children;
                if (!options) {
                    throw new Error("Expected to find options in the selectbox");
                }
                let found = false;
                for (const option of options) {
                    if (option instanceof HTMLOptionElement && option.value === format) {
                        found = option.selected;
                    }
                }
                if (!found) {
                    throw new Error("Expected one of the options to be selected");
                }
            }
        );

        describe(`given a presenter`, () => {
            let presenter: FormatSelectorPresenter;
            beforeEach(() => {
                presenter = {
                    id: "some_id",
                    name: "some_name",
                    default_format: TEXT_FORMAT_TEXT,
                    formatChangedCallback: jest.fn(),
                };
            });

            it(`creates a selectbox element with "html", "text" and "markdown" options`, () => {
                const wrapper = builder.createFormatSelectbox(presenter);

                expect(wrapper.outerHTML).toMatchInlineSnapshot(`
                    <div class="rte_format">Format:<select id="rte_format_selectboxsome_id" name="some_name" class="input-small">
                        <option value="text">Text</option>
                        <option value="html">HTML</option>
                        <option value="commonmark">Markdown</option>
                      </select></div>
                `);
            });

            it(`registers a callback that reacts when the selectbox changes value`, () => {
                const wrapper = builder.createFormatSelectbox(presenter);

                const selectbox = getSelectboxFromWrapper(wrapper);
                selectbox.value = "html";
                selectbox.dispatchEvent(new InputEvent("input"));
                expect(presenter.formatChangedCallback).toHaveBeenCalledWith("html");
            });

            it(`given a presenter without a name, it defaults the selectbox name to
                a prefix + the presenter id`, () => {
                presenter.name = undefined;
                const wrapper = builder.createFormatSelectbox(presenter);

                const selectbox = getSelectboxFromWrapper(wrapper);
                expect(selectbox.name).toEqual("comment_formatsome_id");
            });
        });
    });
});

function getSelectboxFromWrapper(wrapper: HTMLDivElement): HTMLSelectElement {
    const selectbox = wrapper.firstElementChild;
    if (!(selectbox instanceof HTMLSelectElement)) {
        throw new Error("Expected to find the selectbox in its wrapper");
    }
    return selectbox;
}

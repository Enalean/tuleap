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

import type { SyntaxButtonPresenter } from "./FlamingParrotDocumentAdapter";
import {
    FlamingParrotDocumentAdapter,
    HTML_FORMAT_CLASSNAME,
} from "./FlamingParrotDocumentAdapter";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "../../../constants/fields-constants";

const createDocument = (): Document => document.implementation.createHTMLDocument();

const emptyFunction = (): void => {
    //Do nothing
};

interface FakeBootstrap {
    popover(): void;
}

jest.mock("jquery", () => {
    return {
        default: (): FakeBootstrap => {
            return { popover: emptyFunction };
        },
    };
});

describe(`FlamingParrotDocumentAdapter`, () => {
    let doc: Document, adapter: FlamingParrotDocumentAdapter;
    beforeEach(() => {
        doc = createDocument();
        adapter = new FlamingParrotDocumentAdapter(doc);
    });

    describe(`getDefaultFormat()`, () => {
        it(`when the body has a special CSS class, it returns "html"`, () => {
            doc.body.classList.add(HTML_FORMAT_CLASSNAME);
            expect(adapter.getDefaultFormat()).toEqual(TEXT_FORMAT_HTML);
        });

        it(`when the body does not have the class, it returns "commonmark"`, () => {
            expect(adapter.getDefaultFormat()).toEqual(TEXT_FORMAT_COMMONMARK);
        });
    });

    describe(`createOption()`, () => {
        it(`given a presenter, it creates an HTML option element`, () => {
            const option = adapter.createOption({
                value: "neoplasticism",
                is_selected: true,
                text: "acatallactic",
            });
            expect(option.outerHTML).toEqual(`<option value="neoplasticism">acatallactic</option>`);
            expect(option.selected).toBe(true);
        });
    });

    describe(`createSelectBox()`, () => {
        it(`given a presenter, it creates an HTML select element and registers an input callback on it`, () => {
            const first_option = adapter.createOption({
                value: "html",
                text: "html",
                is_selected: true,
            });
            const second_option = adapter.createOption({
                value: "text",
                text: "text",
                is_selected: false,
            });
            const onInputCallback = jest.fn();
            const presenter = {
                id: "subsaturated",
                name: "epeirid",
                options: [first_option, second_option],
                onInputCallback,
            };
            const select = adapter.createSelectBox(presenter);
            expect(select.outerHTML).toEqual(
                `<select id="subsaturated" name="epeirid" class="input-small"><option value="html">html</option><option value="text">text</option></select>`
            );

            select.value = "text";
            select.dispatchEvent(new InputEvent("input"));
            expect(onInputCallback).toHaveBeenCalledWith("text");
        });
    });

    describe(`createFormatWrapper()`, () => {
        it(`given a presenter, it creates an HTML div element wrapping a selectbox`, () => {
            const selectbox = doc.createElement("select");
            const button_helper = doc.createElement("button");
            const presenter = {
                label: "orgue",
                selectbox,
                button_helper,
            };

            const wrapper = adapter.createFormatWrapper(presenter);
            expect(wrapper.outerHTML).toEqual(
                `<div class="rte_format">orgue<select></select><button></button></div>`
            );
        });
    });

    describe(`insertFormatWrapper()`, () => {
        it(`inserts the given wrapper before the given textarea`, () => {
            const textarea = doc.createElement("textarea");
            const wrapper = doc.createElement("div");
            doc.body.append(textarea);
            adapter.insertFormatWrapper(textarea, wrapper);
            expect(wrapper.nextElementSibling).toBe(textarea);
        });
    });
    describe("createCommonMarkSyntaxHelperButton()", () => {
        it(`creates and display the 'Help' button if the format is 'commonmark'`, () => {
            const format = TEXT_FORMAT_COMMONMARK;
            const button_presenter: SyntaxButtonPresenter = {
                label: "Help",
                popover_content: `<div>Call Casting</div>`,
            };

            const button = adapter.createCommonMarkSyntaxHelperButton(button_presenter, format);

            expect(button.outerHTML).toEqual(
                `<button type="button" class="btn btn-small commonmark-button-help commonmark-button-help-show"><i class="fas fa-question-circle help-button-icon"></i>Help</button>`
            );
        });
        it.each([[TEXT_FORMAT_TEXT], [TEXT_FORMAT_HTML]])(
            `creates BUT DOES NOT display the 'Help' button if the format is '%s'`,
            (format) => {
                const button_presenter: SyntaxButtonPresenter = {
                    label: "Help",
                    popover_content: `<div>On the dark side, if you keep cold, it's a cold case</div>`,
                };

                const button = adapter.createCommonMarkSyntaxHelperButton(button_presenter, format);

                expect(button.outerHTML).toEqual(
                    `<button type="button" class="btn btn-small commonmark-button-help"><i class="fas fa-question-circle help-button-icon"></i>Help</button>`
                );
            }
        );
    });
});

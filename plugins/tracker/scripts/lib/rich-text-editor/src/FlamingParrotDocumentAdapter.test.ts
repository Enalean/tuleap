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

import {
    FlamingParrotDocumentAdapter,
    HTML_FORMAT_CLASSNAME,
} from "./FlamingParrotDocumentAdapter";
import { TEXT_FORMAT_HTML, TEXT_FORMAT_TEXT } from "../../../constants/fields-constants";
import { DocumentInterface } from "./DocumentInterface";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe(`FlamingParrotDocumentAdapter`, () => {
    let doc: Document, adapter: DocumentInterface;
    beforeEach(() => {
        doc = createDocument();
        adapter = new FlamingParrotDocumentAdapter(doc);
    });

    describe(`getDefaultFormat()`, () => {
        it(`when the body has a special CSS class, it returns "html"`, () => {
            doc.body.classList.add(HTML_FORMAT_CLASSNAME);
            expect(adapter.getDefaultFormat()).toEqual(TEXT_FORMAT_HTML);
        });

        it(`when the body does not have the class, it returns "text`, () => {
            expect(adapter.getDefaultFormat()).toEqual(TEXT_FORMAT_TEXT);
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

    describe(`createSelectBoxWrapper()`, () => {
        it(`given a presenter, it creates an HTML div element wrapping a selectbox`, () => {
            const selectbox = doc.createElement("select");
            const presenter = {
                label: "orgue",
                child: selectbox,
            };

            const wrapper = adapter.createSelectBoxWrapper(presenter);
            expect(wrapper.outerHTML).toEqual(
                `<div class="rte_format">orgue<select></select></div>`
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
});

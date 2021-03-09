/*
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
 *
 */

import { ExistingFormatSelector } from "./ExistingFormatSelector";
import { TEXT_FORMAT_TEXT } from "../../../../constants/fields-constants";
const createDocument = (): Document => document.implementation.createHTMLDocument();

describe(`ExistingFormatSelector`, () => {
    describe(`insertFormatSelectbox`, () => {
        let doc: Document, textarea: HTMLTextAreaElement;
        beforeEach(() => {
            doc = createDocument();
            textarea = doc.createElement("textarea");
            doc.body.append(textarea);
        });
        it(`throws an error if the presenter id is not a select element`, () => {
            const bad_format_presenter = doc.createElement("input");
            bad_format_presenter.id = "wololo";
            doc.body.append(bad_format_presenter);
            const presenter = {
                id: "wololo",
                name: "prophet",
                selected_value: TEXT_FORMAT_TEXT,
                formatChangedCallback: jest.fn(),
            };
            const text_editor = new ExistingFormatSelector(doc);

            expect(() => text_editor.insertFormatSelectbox(textarea, presenter)).toThrow();
        });
        it(`throws an error if the selected option value is not valid`, () => {
            const format_element = doc.createElement("select");
            format_element.id = "oulala";
            format_element.insertAdjacentHTML("beforeend", `<option value="fail"></option>`);

            doc.body.append(format_element);

            const formatChangedCallback = jest.fn();
            const presenter = {
                id: "oulala",
                name: "aie",
                selected_value: TEXT_FORMAT_TEXT,
                formatChangedCallback,
            };

            const text_editor = new ExistingFormatSelector(doc);
            text_editor.insertFormatSelectbox(textarea, presenter);
            format_element.value = "fail";
            format_element.dispatchEvent(new InputEvent("input"));
            expect(formatChangedCallback).not.toHaveBeenCalled();
        });

        it.each([["text"], ["html"], ["commonmark"]])(
            `change the format of the selectbox with the '%s' format`,
            (format) => {
                const format_element = doc.createElement("select");
                format_element.id = "ok_id";
                format_element.value = "text";
                format_element.insertAdjacentHTML(
                    "beforeend",
                    `<option value="text"></option><option value="html"></option><option value="commonmark"></option>`
                );

                doc.body.append(format_element);
                const formatChangedCallback = jest.fn();
                const presenter = {
                    id: "ok_id",
                    name: "yay",
                    selected_value: TEXT_FORMAT_TEXT,
                    formatChangedCallback,
                };

                const text_editor = new ExistingFormatSelector(doc);
                text_editor.insertFormatSelectbox(textarea, presenter);
                format_element.value = format;
                format_element.dispatchEvent(new InputEvent("input"));
                expect(formatChangedCallback).toHaveBeenCalledWith(format);
            }
        );
    });
});

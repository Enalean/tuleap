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

import { createSelect } from "./FormatSelect";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";
import type { GettextProvider } from "@tuleap/gettext";
import { render } from "lit/html.js";
import { stripLitExpressionComments } from "../../../test-helper";
import { initGettextSync } from "@tuleap/gettext";

const emptyFunction = (): void => {
    //Do nothing
};

describe(`FormatSelect`, () => {
    let gettext_provider: GettextProvider, mount_point: HTMLDivElement;
    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        mount_point = doc.createElement("div");
        gettext_provider = initGettextSync("rich-text-editor", {}, "en_US");
    });

    function getSelectBox(): HTMLSelectElement {
        const select = mount_point.querySelector("[data-test=format-select]");
        if (!(select instanceof HTMLSelectElement)) {
            throw new Error("Could not find the select that was just rendered");
        }
        return select;
    }

    it(`given a presenter, it will create a selectbox with the given format options`, () => {
        const presenter = {
            id: "whimper",
            name: "depletive",
            is_disabled: false,
            selected_value: TEXT_FORMAT_COMMONMARK,
            options: [TEXT_FORMAT_COMMONMARK, TEXT_FORMAT_HTML, TEXT_FORMAT_TEXT],
            formatChangedCallback: emptyFunction,
        };
        const template = createSelect(presenter, gettext_provider);
        render(template, mount_point);

        expect(stripLitExpressionComments(mount_point.innerHTML)).toMatchInlineSnapshot(`
            "
                    <select class="small" data-test="format-select" id="whimper" name="depletive">
                        
                <option value="commonmark" selected="">Markdown</option>

                <option value="html">HTML</option>

                <option value="text">Text</option>

                    </select>
                "
        `);
    });

    it(`with is_disabled is true, it will disable the selectbox`, () => {
        const presenter = {
            id: "irrelevant",
            name: "irrelevant",
            is_disabled: true,
            selected_value: TEXT_FORMAT_COMMONMARK,
            options: [TEXT_FORMAT_COMMONMARK, TEXT_FORMAT_HTML, TEXT_FORMAT_TEXT],
            formatChangedCallback: emptyFunction,
        };
        const template = createSelect(presenter, gettext_provider);
        render(template, mount_point);

        const select = getSelectBox();
        expect(select.disabled).toBe(true);
    });

    describe(`select new value`, () => {
        it.each([
            [TEXT_FORMAT_COMMONMARK, TEXT_FORMAT_HTML],
            [TEXT_FORMAT_HTML, TEXT_FORMAT_COMMONMARK],
        ])(
            `given %s was selected, when I select %s, on "input" event,
            it will call the formatChangedCallback with the new value`,
            (initial_format, new_format) => {
                const presenter = {
                    id: "irrelevant",
                    name: "irrelevant",
                    is_disabled: false,
                    selected_value: initial_format,
                    options: [TEXT_FORMAT_COMMONMARK, TEXT_FORMAT_HTML, TEXT_FORMAT_TEXT],
                    formatChangedCallback: emptyFunction,
                };
                const callback = jest.spyOn(presenter, "formatChangedCallback");
                const template = createSelect(presenter, gettext_provider);
                render(template, mount_point);

                const select = getSelectBox();
                select.value = new_format;
                select.dispatchEvent(new InputEvent("input"));
                expect(callback).toHaveBeenCalledWith(new_format);
            },
        );
    });
});

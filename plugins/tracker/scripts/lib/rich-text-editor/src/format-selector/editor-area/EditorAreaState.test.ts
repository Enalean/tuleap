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
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "../../../../../constants/fields-constants";
import { EditorAreaState } from "./EditorAreaState";
import type { FormatSelectorPresenter } from "../FormatSelectorInterface";

describe(`EditorAreaState`, () => {
    let mount_point: HTMLDivElement,
        textarea: HTMLTextAreaElement,
        presenter: FormatSelectorPresenter;
    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        mount_point = doc.createElement("div");
        textarea = doc.createElement("textarea");
        presenter = {
            id: "selectbox_id",
            name: "selectbox_name",
            selected_value: TEXT_FORMAT_COMMONMARK,
            formatChangedCallback: jest.fn(),
        };
    });

    describe(`isCurrentFormatMarkdown()`, () => {
        it.each([
            [TEXT_FORMAT_TEXT, false],
            [TEXT_FORMAT_HTML, false],
            [TEXT_FORMAT_COMMONMARK, true],
        ])(
            `when the current selected value is %s, it will return %s`,
            (selected_value, expected_value) => {
                presenter.selected_value = selected_value;
                const state = new EditorAreaState(mount_point, textarea, presenter);
                expect(state.isCurrentFormatMarkdown()).toBe(expected_value);
            }
        );
    });

    describe(`onFormatChange()`, () => {
        it(`will change the selected value to the new format
            and will call the presenter's callback`, () => {
            const state = new EditorAreaState(mount_point, textarea, presenter);
            state.onFormatChange(TEXT_FORMAT_HTML);
            expect(state.selected_value).toEqual(TEXT_FORMAT_HTML);
            expect(presenter.formatChangedCallback).toHaveBeenCalledWith(TEXT_FORMAT_HTML);
        });
    });
});

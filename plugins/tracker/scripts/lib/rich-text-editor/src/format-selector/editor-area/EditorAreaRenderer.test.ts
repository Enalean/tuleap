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

import * as adapter from "./lit-html-adapter";
import { EditorAreaRenderer } from "./EditorAreaRenderer";
import type { GettextProvider } from "@tuleap/gettext";
import { EditorAreaState } from "./EditorAreaState";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
} from "../../../../../constants/fields-constants";

const emptyFunction = (): void => {
    //Do nothing
};

function createState(selectbox_id: string, selectbox_name?: string): EditorAreaState {
    const doc = document.implementation.createHTMLDocument();
    const mount_point = doc.createElement("div");
    const textarea = doc.createElement("textarea");
    return new EditorAreaState(mount_point, textarea, {
        id: selectbox_id,
        name: selectbox_name,
        selected_value: TEXT_FORMAT_COMMONMARK,
        formatChangedCallback: emptyFunction,
    });
}

describe(`EditorAreaRenderer`, () => {
    let renderer: EditorAreaRenderer, gettext_provider: GettextProvider, state: EditorAreaState;
    beforeEach(() => {
        gettext_provider = {
            gettext: (msgid: string): string => msgid,
        };
        state = createState("reduplicatory", "archdruid");
        renderer = new EditorAreaRenderer(gettext_provider);
    });

    describe(`render()`, () => {
        beforeEach(() => {
            //Do not actually render
            jest.spyOn(adapter, "renderRichTextEditorArea").mockImplementation(emptyFunction);
        });

        it(`when the state is not in Markdown,
            it will render the area without a Help button`, () => {
            state.selected_value = TEXT_FORMAT_HTML;
            const render = jest.spyOn(adapter, "renderRichTextEditorArea");

            renderer.render(state);
            const helper_button = render.mock.calls[0][0].helper_button;
            expect(helper_button).not.toBeDefined();
        });

        it(`when the state is in Markdown format,
            it will create a Help button`, () => {
            const render = jest.spyOn(adapter, "renderRichTextEditorArea");

            renderer.render(state);
            const helper_button = render.mock.calls[0][0].helper_button;
            expect(helper_button).toBeDefined();
        });

        it(`will prefix the selectbox id before creating it
            (so that old Prototype code can select it)`, () => {
            const state = createState("new", "new");
            const createSelect = jest.spyOn(adapter, "createSelect");
            renderer.render(state);

            const selectbox_id = createSelect.mock.calls[0][0].id;
            expect(selectbox_id).toEqual("rte_format_selectboxnew");
        });

        it(`when the state does not have a selectbox_name,
            it will default it to the name prefix + the selectbox_id
            (so that old Prototype code can select it)`, () => {
            const state = createState("new", undefined);
            const createSelect = jest.spyOn(adapter, "createSelect");
            renderer.render(state);

            const selectbox_name = createSelect.mock.calls[0][0].name;
            expect(selectbox_name).toEqual("comment_formatnew");
        });

        it(`when the format changes in the selectbox,
            it will call the state's onFormatChange() and it will re-render the state`, () => {
            const createSelect = jest.spyOn(adapter, "createSelect");
            const stateCallback = jest.spyOn(state, "onFormatChange");
            const render = jest.spyOn(renderer, "render");

            renderer.render(state);

            const callback = createSelect.mock.calls[0][0].formatChangedCallback;
            callback(TEXT_FORMAT_HTML);
            expect(stateCallback).toHaveBeenCalledWith(TEXT_FORMAT_HTML);
            expect(render).toHaveBeenCalledTimes(2);
        });
    });
});

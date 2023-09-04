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
import * as FormatSelect from "./components/FormatSelect";
import * as PreviewEditButton from "./components/PreviewEditButton";
import * as FormatHiddenInput from "./components/FormatHiddenInput";
import { EditorAreaRenderer } from "./EditorAreaRenderer";
import type { GettextProvider } from "@tuleap/gettext";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import { TEXT_FORMAT_COMMONMARK, TEXT_FORMAT_HTML } from "@tuleap/plugin-tracker-constants";
import type { EditorAreaStateInterface } from "./EditorAreaStateInterface";
import { initGettextSync } from "@tuleap/gettext";

const emptyFunction = (): void => {
    //Do nothing
};

function createState(
    selectbox_id: string,
    selectbox_name: string | undefined,
): EditorAreaStateInterface {
    const doc = document.implementation.createHTMLDocument();
    const mount_point = doc.createElement("div");
    const textarea = doc.createElement("textarea");

    return new (class implements EditorAreaStateInterface {
        constructor(
            public mount_point: HTMLDivElement,
            public textarea: HTMLTextAreaElement,
            public selectbox_id: string,
            public selectbox_name: string | undefined,
            public current_format: TextFieldFormat = TEXT_FORMAT_COMMONMARK,
            public rendered_html = null,
            private is_in_preview_mode = false,
        ) {}
        changeFormat(new_format: TextFieldFormat): void {
            this.current_format = new_format;
        }
        isCurrentFormatCommonMark(): boolean {
            return this.current_format === TEXT_FORMAT_COMMONMARK;
        }
        isInEditMode(): boolean {
            return !this.is_in_preview_mode;
        }
        switchToEditMode(): void {
            this.is_in_preview_mode = false;
        }
        switchToPreviewMode(): void {
            this.is_in_preview_mode = true;
        }
    })(mount_point, textarea, selectbox_id, selectbox_name);
}

describe(`EditorAreaRenderer`, () => {
    let renderer: EditorAreaRenderer,
        gettext_provider: GettextProvider,
        state: EditorAreaStateInterface;
    beforeEach(() => {
        gettext_provider = initGettextSync("rich-text-editor", {}, "en_US");
        state = createState("reduplicatory", "archdruid");
        renderer = new EditorAreaRenderer(gettext_provider);
    });

    describe(`render()`, () => {
        beforeEach(() => {
            //Do not actually render
            jest.spyOn(adapter, "renderHTMLOrTextEditor").mockImplementation(emptyFunction);
        });

        it(`when the state is not in Markdown,
            it will render the area without the Help and Preview/Edit buttons`, () => {
            state.changeFormat(TEXT_FORMAT_HTML);
            const render = jest.spyOn(adapter, "renderHTMLOrTextEditor");
            renderer.render(state);

            expect(render).toHaveBeenCalled();
        });

        describe(`when the state is in Markdown format`, () => {
            beforeEach(() => {
                state.changeFormat(TEXT_FORMAT_COMMONMARK);
                //Do not actually render
                jest.spyOn(adapter, "renderMarkdownEditor").mockImplementation(emptyFunction);
            });

            it(`will create a Help button and a Preview/Edit button`, () => {
                const render = jest.spyOn(adapter, "renderMarkdownEditor");
                renderer.render(state);

                const { help_button, preview_button } = render.mock.calls[0][0];
                expect(help_button).toBeDefined();
                expect(preview_button).toBeDefined();
            });

            describe(`and the state is in Preview mode`, () => {
                beforeEach(() => {
                    state.switchToPreviewMode();
                });

                it(`will create a hidden input with the same name and value as the format selectbox
                    so that users can submit the Artifact view form while previewing Markdown`, () => {
                    const createHidden = jest.spyOn(FormatHiddenInput, "createFormatHiddenInput");

                    renderer.render(state);

                    expect(createHidden).toHaveBeenCalled();
                });

                it(`when I click on the Edit button, it will switch to Edit and re-render`, () => {
                    const createEditButton = jest.spyOn(
                        PreviewEditButton,
                        "createPreviewEditButton",
                    );
                    const render = jest.spyOn(renderer, "render");

                    renderer.render(state);
                    createEditButton.mock.calls[0][0].onClickCallback();

                    expect(state.isInEditMode()).toBe(true);
                    expect(render).toHaveBeenCalledTimes(2);
                });
            });

            describe(`and the state is in Edit mode`, () => {
                beforeEach(() => {
                    state.switchToEditMode();
                });

                it(`when I click on the Preview button, it will switch to Preview and re-render`, () => {
                    const createPreviewButton = jest.spyOn(
                        PreviewEditButton,
                        "createPreviewEditButton",
                    );
                    const render = jest.spyOn(renderer, "render");

                    renderer.render(state);
                    createPreviewButton.mock.calls[0][0].onClickCallback();

                    expect(state.isInEditMode()).toBe(false);
                    expect(render).toHaveBeenCalledTimes(2);
                });
            });
        });

        describe(`Selectbox presenter`, () => {
            it(`will prefix the selectbox id before creating it
                (so that old Prototype code can select it)`, () => {
                const state = createState("new", "new");
                const createSelect = jest.spyOn(FormatSelect, "createSelect");
                renderer.render(state);

                const selectbox_id = createSelect.mock.calls[0][0].id;
                expect(selectbox_id).toBe("rte_format_selectboxnew");
            });

            it(`when the state does not have a selectbox_name,
                it will default it to the name prefix + the selectbox_id
                (so that old Prototype code can select it)`, () => {
                const state = createState("new", undefined);
                const createSelect = jest.spyOn(FormatSelect, "createSelect");
                renderer.render(state);

                const selectbox_name = createSelect.mock.calls[0][0].name;
                expect(selectbox_name).toBe("comment_formatnew");
            });

            it(`when the format changes in the selectbox,
                it will call the state's onFormatChange() and it will re-render the state`, () => {
                const createSelect = jest.spyOn(FormatSelect, "createSelect");
                const stateCallback = jest.spyOn(state, "changeFormat");
                const render = jest.spyOn(renderer, "render");

                renderer.render(state);
                const callback = createSelect.mock.calls[0][0].formatChangedCallback;
                callback(TEXT_FORMAT_HTML);

                expect(stateCallback).toHaveBeenCalledWith(TEXT_FORMAT_HTML);
                expect(render).toHaveBeenCalledTimes(2);
            });
        });
    });
});

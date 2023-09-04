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

import { TextEditor } from "./TextEditor";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";
import type {
    HTMLToMarkdownConverterInterface,
    InternalTextEditorOptions,
    MarkdownToHTMLRendererInterface,
} from "./types";
import CKEDITOR from "ckeditor4";

const createDocument = (): Document => document.implementation.createHTMLDocument();
const emptyFunction = (): void => {
    //Do nothing
};
const emptyOptionsProvider = (): Record<string, never> => ({});

describe(`TextEditor`, () => {
    let textarea: HTMLTextAreaElement,
        markdown_converter: HTMLToMarkdownConverterInterface,
        markdown_renderer: MarkdownToHTMLRendererInterface;
    beforeEach(() => {
        const doc = createDocument();
        textarea = doc.createElement("textarea");
        textarea.id = "some_id";
        doc.body.append(textarea);
        markdown_converter = { convert: (html): string => html };
        markdown_renderer = { render: (markdown): string => markdown };
    });

    describe(`init()`, () => {
        it('when the format is "html", then ckeditor is created and the text is not rendered as Markdown', () => {
            const ckeditor_instance = getMockedCKEditorInstance();
            jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
            jest.spyOn(markdown_converter, "convert");
            const editor = new TextEditor(
                textarea,
                getEmptyOptions(),
                markdown_converter,
                markdown_renderer,
            );
            textarea.value = `<p>HTML</p>\t<ul>\t<li><strong>List</strong>\t<ul>\t\t<li>element</li>\t</ul>\t</li></ul>`;
            editor.init(TEXT_FORMAT_HTML);
            expect(markdown_converter.convert).not.toHaveBeenCalled();
            expect(textarea.value).toBe(
                "<p>HTML</p>\t<ul>\t<li><strong>List</strong>\t<ul>\t\t<li>element</li>\t</ul>\t</li></ul>",
            );
        });

        it("when the format is commonmark, then ckeditor is not created and callback is called", () => {
            const options = {
                ...getEmptyOptions(),
                onFormatChange: jest.fn(),
            };
            const editor = new TextEditor(textarea, options, markdown_converter, markdown_renderer);
            editor.init(TEXT_FORMAT_COMMONMARK);
            expect(options.onFormatChange).toHaveBeenCalledWith(TEXT_FORMAT_COMMONMARK);
        });
    });

    describe(`onFormatChange()`, () => {
        describe(`when the format changes to "html"`, () => {
            it(`creates a CKEditor with the additional options given`, () => {
                const replace = jest
                    .spyOn(CKEDITOR, "replace")
                    .mockReturnValue(getMockedCKEditorInstance());
                const additional_options = {
                    extraPlugins: "uploadimage",
                    uploadUrl: "/example/url",
                };
                const options = {
                    ...getEmptyOptions(),
                    getAdditionalOptions: (): CKEDITOR.config => additional_options,
                };
                const editor = new TextEditor(
                    textarea,
                    options,
                    markdown_converter,
                    markdown_renderer,
                );
                editor.onFormatChange(TEXT_FORMAT_HTML);

                const expected_options = { language: "fr_FR", ...additional_options };
                expect(replace).toHaveBeenCalledWith(
                    textarea,
                    expect.objectContaining(expected_options),
                );
            });

            it(`calls the onEditorDataReady callback when CKEditor data is ready`, () => {
                const ckeditor_instance = getMockedCKEditorInstance();
                const eventHandler = jest.spyOn(ckeditor_instance, "on");
                jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
                const options = {
                    ...getEmptyOptions(),
                    onEditorDataReady: jest.fn(),
                };
                const editor = new TextEditor(
                    textarea,
                    options,
                    markdown_converter,
                    markdown_renderer,
                );
                editor.onFormatChange(TEXT_FORMAT_HTML);
                const onDataReadyCallback = eventHandler.mock.calls[0][1];
                onDataReadyCallback({} as CKEDITOR.eventInfo);

                expect(options.onEditorDataReady).toHaveBeenCalled();
            });

            it(`calls the given onEditorInit and onFormatChange callbacks`, () => {
                const ckeditor_instance = getMockedCKEditorInstance();
                jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
                const options = {
                    ...getEmptyOptions(),
                    onEditorInit: jest.fn(),
                    onFormatChange: jest.fn(),
                };
                const editor = new TextEditor(
                    textarea,
                    options,
                    markdown_converter,
                    markdown_renderer,
                );
                editor.onFormatChange(TEXT_FORMAT_HTML);

                expect(options.onEditorInit).toHaveBeenCalledWith(ckeditor_instance, textarea);
                expect(options.onFormatChange).toHaveBeenCalledWith(TEXT_FORMAT_HTML);
            });

            it(`from another format, it converts the textarea's value as Markdown to HTML
                and sets CKEditor data with it`, () => {
                const ckeditor_instance = getMockedCKEditorInstance();
                jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
                jest.spyOn(markdown_renderer, "render").mockReturnValue(`<p>Some HTML content</p>`);

                const editor = new TextEditor(
                    textarea,
                    getEmptyOptions(),
                    markdown_converter,
                    markdown_renderer,
                );
                textarea.value = "**markdown** content";
                editor.onFormatChange(TEXT_FORMAT_COMMONMARK);
                editor.onFormatChange(TEXT_FORMAT_HTML);

                expect(markdown_renderer.render).toHaveBeenCalledWith("**markdown** content");
                expect(ckeditor_instance.getData()).toBe(`<p>Some HTML content</p>`);
            });
        });

        describe(`when the format changes from "html" to another format`, () => {
            it(`converts the CKEditor content to Markdown and sets the textarea value with it`, () => {
                const ckeditor_instance = getMockedCKEditorInstance();
                jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
                jest.spyOn(markdown_converter, "convert").mockReturnValue("**markdown** content");

                const editor = new TextEditor(
                    textarea,
                    getEmptyOptions(),
                    markdown_converter,
                    markdown_renderer,
                );
                textarea.value = `<p><strong>HTML</strong> content</p>`;
                editor.onFormatChange(TEXT_FORMAT_HTML);
                editor.onFormatChange(TEXT_FORMAT_COMMONMARK);

                expect(markdown_converter.convert).toHaveBeenCalledWith(
                    `<p><strong>HTML</strong> content</p>`,
                );
                expect(textarea.value).toBe("**markdown** content");
            });
        });

        it.each([[TEXT_FORMAT_TEXT], [TEXT_FORMAT_COMMONMARK]])(
            `when the format changes to %s, it calls the given onFormatChange callback`,
            (format) => {
                const options = {
                    ...getEmptyOptions(),
                    onFormatChange: jest.fn(),
                };
                const editor = new TextEditor(
                    textarea,
                    options,
                    markdown_converter,
                    markdown_renderer,
                );
                editor.onFormatChange(format);
                expect(options.onFormatChange).toHaveBeenCalledWith(format);
            },
        );
    });

    describe("getContent()", () => {
        it.each([
            ["some data to return from CKEDITOR", TEXT_FORMAT_HTML, "Return from TEXTAREA"],
            ["Return from TEXTAREA", TEXT_FORMAT_COMMONMARK, "some data to return from CKEDITOR"],
        ])(
            `returns the '%s' content when the format is %s`,
            (expected_content, format, other_content) => {
                const ckeditor_instance = getMockedCKEditorInstance();
                jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);

                const editor = new TextEditor(
                    textarea,
                    getEmptyOptions(),
                    markdown_converter,
                    markdown_renderer,
                );
                editor.onFormatChange(format);
                if (format === TEXT_FORMAT_HTML) {
                    ckeditor_instance.setData(expected_content);
                    textarea.value = other_content;
                } else {
                    ckeditor_instance.setData(other_content);
                    textarea.value = expected_content;
                }

                expect(editor.getContent()).toEqual(expected_content);
            },
        );
    });

    describe(`destroy()`, () => {
        let ckeditor_instance: CKEDITOR.editor,
            destroyCKEditor: jest.SpyInstance,
            editor: TextEditor;
        beforeEach(() => {
            ckeditor_instance = getMockedCKEditorInstance();
            jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);

            destroyCKEditor = jest.spyOn(ckeditor_instance, "destroy");

            editor = new TextEditor(
                textarea,
                getEmptyOptions(),
                markdown_converter,
                markdown_renderer,
            );
        });

        it(`when the format is html, it will destroy the CKEditor`, () => {
            editor.onFormatChange(TEXT_FORMAT_HTML);

            editor.destroy();
            expect(destroyCKEditor).toHaveBeenCalled();
        });

        it(`when the format is not html, it does nothing`, () => {
            editor.onFormatChange(TEXT_FORMAT_COMMONMARK);

            editor.destroy();
            expect(destroyCKEditor).not.toHaveBeenCalled();
        });
    });
});

function getEmptyOptions(): InternalTextEditorOptions {
    return {
        locale: "fr_FR",
        getAdditionalOptions: emptyOptionsProvider,
        onEditorInit: emptyFunction,
        onFormatChange: emptyFunction,
        onEditorDataReady: emptyFunction,
    };
}

function getMockedCKEditorInstance(): CKEDITOR.editor {
    let _data = "";
    const fake_ckeditor_document = createDocument();
    return {
        on: emptyFunction,
        document: {
            getBody(): CKEDITOR.dom.element {
                return {
                    $: fake_ckeditor_document.createElement("body"),
                } as unknown as CKEDITOR.dom.element;
            },
        },
        setData(data: string): void {
            _data = data;
        },
        getData(): string {
            return _data;
        },
        destroy: emptyFunction,
    } as unknown as CKEDITOR.editor;
}

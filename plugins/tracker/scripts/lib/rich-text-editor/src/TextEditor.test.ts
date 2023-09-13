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
import { InternalTextEditorOptionsBuilder } from "../tests/builders/InternalTextEditorOptionsBuilder";

type CKEditorEventHandler = (event: CKEDITOR.eventInfo) => void;

const createDocument = (): Document => document.implementation.createHTMLDocument();
const noop = (): void => {
    //Do nothing
};

const COMMONMARK_CONTENT = "**markdown** content";
const HTML_CONTENT = `<p>Some HTML content</p>`;
const TEXT_CONTENT = "Plain text content";

describe(`TextEditor`, () => {
    let textarea: HTMLTextAreaElement,
        markdown_converter: HTMLToMarkdownConverterInterface,
        markdown_renderer: MarkdownToHTMLRendererInterface,
        options: InternalTextEditorOptions;
    beforeEach(() => {
        const doc = createDocument();
        textarea = doc.createElement("textarea");
        textarea.id = "some_id";
        doc.body.append(textarea);
        markdown_converter = { convert: (html): string => html };
        markdown_renderer = { render: (markdown): string => markdown };

        options = InternalTextEditorOptionsBuilder.options().build();
    });

    const getTextEditor = (): TextEditor =>
        new TextEditor(textarea, options, markdown_converter, markdown_renderer);

    describe(`init()`, () => {
        it(`when the format is "html",
            then ckeditor is created and the text is not rendered as Markdown
            and onFormatChange is called`, () => {
            const ckeditor_instance = getMockedCKEditorInstance();
            jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
            const convert = jest.spyOn(markdown_converter, "convert");
            const onFormatChange = jest.spyOn(options, "onFormatChange");
            const html_content = `<p>HTML</p>\t<ul>\t<li><strong>List</strong>\t<ul>\t\t<li>element</li>\t</ul>\t</li></ul>`;

            const editor = getTextEditor();
            textarea.value = html_content;
            editor.init(TEXT_FORMAT_HTML);

            expect(convert).not.toHaveBeenCalled();
            expect(textarea.value).toBe(html_content);
            expect(onFormatChange).toHaveBeenCalledWith(TEXT_FORMAT_HTML, html_content);
        });

        it("when the format is commonmark, then ckeditor is not created and onFormatChange is called", () => {
            const onFormatChange = jest.spyOn(options, "onFormatChange");
            const editor = getTextEditor();
            textarea.value = COMMONMARK_CONTENT;
            editor.init(TEXT_FORMAT_COMMONMARK);
            expect(onFormatChange).toHaveBeenCalledWith(TEXT_FORMAT_COMMONMARK, COMMONMARK_CONTENT);
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
                const locale = "fr_FR";
                options = InternalTextEditorOptionsBuilder.options()
                    .withLocale(locale)
                    .withAdditionalOptionsProvider(() => additional_options)
                    .build();
                getTextEditor().onFormatChange(TEXT_FORMAT_HTML);

                const expected_options = { language: locale, ...additional_options };
                expect(replace).toHaveBeenCalledWith(
                    textarea,
                    expect.objectContaining(expected_options),
                );
            });

            it(`calls the onEditorDataReady callback when CKEditor data is ready`, () => {
                const ckeditor_instance = getMockedCKEditorInstance();
                const eventHandler = jest.spyOn(ckeditor_instance, "on");
                jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
                const onEditorDataReady = jest.spyOn(options, "onEditorDataReady");

                getTextEditor().onFormatChange(TEXT_FORMAT_HTML);
                const onDataReadyCallback = eventHandler.mock.calls[0][1];
                onDataReadyCallback({} as CKEDITOR.eventInfo);

                expect(onEditorDataReady).toHaveBeenCalled();
            });

            it(`calls the given onEditorInit callback`, () => {
                const ckeditor_instance = getMockedCKEditorInstance();
                jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
                const onEditorInit = jest.spyOn(options, "onEditorInit");

                getTextEditor().onFormatChange(TEXT_FORMAT_HTML);

                expect(onEditorInit).toHaveBeenCalledWith(ckeditor_instance, textarea);
            });

            it(`from another format, it converts the textarea's value as Markdown to HTML
                and sets CKEditor data with it
                and calls the onFormatChange callback with the HTML format and the converted content`, () => {
                const ckeditor_instance = getMockedCKEditorInstance();
                jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
                const render = jest
                    .spyOn(markdown_renderer, "render")
                    .mockReturnValue(HTML_CONTENT);
                const onFormatChange = jest.spyOn(options, "onFormatChange");

                const editor = getTextEditor();
                textarea.value = COMMONMARK_CONTENT;
                editor.onFormatChange(TEXT_FORMAT_COMMONMARK);
                editor.onFormatChange(TEXT_FORMAT_HTML);

                expect(render).toHaveBeenCalledWith(COMMONMARK_CONTENT);
                expect(ckeditor_instance.getData()).toBe(HTML_CONTENT);
                expect(onFormatChange).toHaveBeenCalledWith(TEXT_FORMAT_HTML, HTML_CONTENT);
            });
        });

        describe(`when the format changes from "html" to another format`, () => {
            it(`converts the CKEditor content to Markdown
                and sets the textarea value with it
                and calls the onFormatChange callback with the new format and the converted content`, () => {
                const ckeditor_instance = getMockedCKEditorInstance();
                jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
                const convert = jest
                    .spyOn(markdown_converter, "convert")
                    .mockReturnValue(COMMONMARK_CONTENT);
                const onFormatChange = jest.spyOn(options, "onFormatChange");

                const editor = getTextEditor();
                textarea.value = HTML_CONTENT;
                editor.onFormatChange(TEXT_FORMAT_HTML);
                editor.onFormatChange(TEXT_FORMAT_COMMONMARK);

                expect(convert).toHaveBeenCalledWith(HTML_CONTENT);
                expect(textarea.value).toBe(COMMONMARK_CONTENT);
                expect(onFormatChange).toHaveBeenCalledWith(
                    TEXT_FORMAT_COMMONMARK,
                    COMMONMARK_CONTENT,
                );
            });
        });

        it(`when the format changes to "text", it calls the onFormatChange callback with the new format and the textarea's value`, () => {
            const onFormatChange = jest.spyOn(options, "onFormatChange");
            const editor = getTextEditor();
            textarea.value = TEXT_CONTENT;
            editor.onFormatChange(TEXT_FORMAT_TEXT);
            expect(onFormatChange).toHaveBeenCalledWith(TEXT_FORMAT_TEXT, TEXT_CONTENT);
        });
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

                const editor = getTextEditor();
                editor.onFormatChange(format);
                if (format === TEXT_FORMAT_HTML) {
                    ckeditor_instance.setData(expected_content);
                    textarea.value = other_content;
                } else {
                    ckeditor_instance.setData(other_content);
                    textarea.value = expected_content;
                }

                expect(editor.getContent()).toBe(expected_content);
            },
        );
    });

    describe(`destroy()`, () => {
        let ckeditor_instance: CKEDITOR.editor;
        beforeEach(() => {
            ckeditor_instance = getMockedCKEditorInstance();
            jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
        });

        it(`when the format is html, it will destroy the CKEditor`, () => {
            const destroyCKEditor = jest.spyOn(ckeditor_instance, "destroy");
            const editor = getTextEditor();
            editor.onFormatChange(TEXT_FORMAT_HTML);

            editor.destroy();
            expect(destroyCKEditor).toHaveBeenCalled();
        });

        it(`when the format is not html, it does nothing`, () => {
            const destroyCKEditor = jest.spyOn(ckeditor_instance, "destroy");
            const editor = getTextEditor();
            editor.onFormatChange(TEXT_FORMAT_COMMONMARK);

            editor.destroy();
            expect(destroyCKEditor).not.toHaveBeenCalled();
        });
    });
});

function getMockedCKEditorInstance(): CKEDITOR.editor {
    let _data = "";
    const fake_ckeditor_document = createDocument();
    return {
        on(event_name: string, handler: CKEditorEventHandler): void {
            if (event_name === "" && handler !== null) {
                // Do nothing
            }
        },
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
        destroy: noop,
    } as CKEDITOR.editor;
}

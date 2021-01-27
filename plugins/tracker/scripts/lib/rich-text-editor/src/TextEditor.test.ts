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
} from "../../../constants/fields-constants";
import { HTMLToMarkdownConverterInterface, MarkdownToHTMLRendererInterface } from "./types";
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

    describe(`onFormatChange()`, () => {
        describe(`when the format changes to "html"`, () => {
            it(`creates a CKEditor with the additional options given`, () => {
                const replace = jest.spyOn(CKEDITOR, "replace");
                const additional_options = {
                    extraPlugins: "uploadimage",
                    uploadUrl: "/example/url",
                };
                const options = {
                    locale: "fr_FR",
                    getAdditionalOptions: (): CKEDITOR.config => additional_options,
                    onEditorInit: emptyFunction,
                    onFormatChange: emptyFunction,
                };
                const editor = new TextEditor(
                    textarea,
                    options,
                    markdown_converter,
                    markdown_renderer
                );
                editor.onFormatChange(TEXT_FORMAT_HTML);

                const expected_options = { language: "fr_FR", ...additional_options };
                expect(replace).toHaveBeenCalledWith(
                    textarea,
                    expect.objectContaining(expected_options)
                );
            });

            it(`calls the given onEditorInit and onFormatChange callbacks`, () => {
                const ckeditor_instance = ({
                    setData: emptyFunction,
                } as unknown) as CKEDITOR.editor;
                jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
                const options = {
                    locale: "fr_FR",
                    getAdditionalOptions: emptyOptionsProvider,
                    onEditorInit: jest.fn(),
                    onFormatChange: jest.fn(),
                };
                const editor = new TextEditor(
                    textarea,
                    options,
                    markdown_converter,
                    markdown_renderer
                );
                editor.onFormatChange(TEXT_FORMAT_HTML);

                expect(options.onEditorInit).toHaveBeenCalledWith(ckeditor_instance, textarea);
                expect(options.onFormatChange).toHaveBeenCalledWith(TEXT_FORMAT_HTML);
            });

            it(`from another format, it converts the textarea's value as Markdown to HTML
                and sets CKEditor data with it`, () => {
                let _data = "";
                const ckeditor_instance = ({
                    data: "",
                    setData(data: string): void {
                        _data = data;
                    },
                    getData(): string {
                        return _data;
                    },
                    destroy: emptyFunction,
                } as unknown) as CKEDITOR.editor;
                jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
                const options = {
                    locale: "fr_FR",
                    getAdditionalOptions: emptyOptionsProvider,
                    onEditorInit: emptyFunction,
                    onFormatChange: emptyFunction,
                };
                jest.spyOn(markdown_renderer, "render").mockReturnValue(`<p>Some HTML content</p>`);

                const editor = new TextEditor(
                    textarea,
                    options,
                    markdown_converter,
                    markdown_renderer
                );
                textarea.value = "**markdown** content";
                editor.onFormatChange(TEXT_FORMAT_COMMONMARK);
                editor.onFormatChange(TEXT_FORMAT_HTML);

                expect(markdown_renderer.render).toHaveBeenCalledWith("**markdown** content");
                expect(_data).toEqual(`<p>Some HTML content</p>`);
            });
        });

        describe(`when the format changes from "html" to another format`, () => {
            it(`converts the CKEditor content to Markdown and sets the textarea value with it`, () => {
                let _data = "";
                const ckeditor_instance = ({
                    setData(data: string): void {
                        _data = data;
                    },
                    getData(): string {
                        return _data;
                    },
                    destroy: emptyFunction,
                } as unknown) as CKEDITOR.editor;
                jest.spyOn(CKEDITOR, "replace").mockReturnValue(ckeditor_instance);
                const options = {
                    locale: "fr_FR",
                    getAdditionalOptions: emptyOptionsProvider,
                    onEditorInit: emptyFunction,
                    onFormatChange: emptyFunction,
                };
                jest.spyOn(markdown_converter, "convert").mockReturnValue("**markdown** content");

                const editor = new TextEditor(
                    textarea,
                    options,
                    markdown_converter,
                    markdown_renderer
                );
                textarea.value = `<p><strong>HTML</strong> content</p>`;
                editor.onFormatChange(TEXT_FORMAT_HTML);
                editor.onFormatChange(TEXT_FORMAT_COMMONMARK);

                expect(markdown_converter.convert).toHaveBeenCalledWith(
                    `<p><strong>HTML</strong> content</p>`
                );
                expect(textarea.value).toEqual("**markdown** content");
            });
        });

        it.each([[TEXT_FORMAT_TEXT], [TEXT_FORMAT_COMMONMARK]])(
            `when the format changes to %s, it calls the given onFormatChange callback`,
            (format) => {
                const options = {
                    locale: "fr_FR",
                    getAdditionalOptions: emptyOptionsProvider,
                    onEditorInit: emptyFunction,
                    onFormatChange: jest.fn(),
                };
                const editor = new TextEditor(
                    textarea,
                    options,
                    markdown_converter,
                    markdown_renderer
                );
                editor.onFormatChange(format);
                expect(options.onFormatChange).toHaveBeenCalledWith(format);
            }
        );
    });
});

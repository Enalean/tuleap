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

import CKEDITOR from "ckeditor4";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import { TEXT_FORMAT_HTML } from "@tuleap/plugin-tracker-constants";
import type {
    HTMLToMarkdownConverterInterface,
    InternalTextEditorOptions,
    MarkdownToHTMLRendererInterface,
} from "./types";
import type { TextEditorInterface } from "./TextEditorInterface";

const DEFAULT_LOCALE = "en_US";
const CKEDITOR_DEFAULT_OPTIONS = {
    toolbar: [
        ["Bold", "Italic"],
        ["NumberedList", "BulletedList", "-", "Blockquote", "Styles", "Format"],
        ["Link", "Unlink", "Anchor", "Image"],
        ["Source"],
    ],
    stylesSet: [
        { name: "Bold", element: "strong", overrides: { b: true } },
        { name: "Italic", element: "em", overrides: { i: true } },
        { name: "Code", element: "code" },
        { name: "Subscript", element: "sub" },
        { name: "Superscript", element: "sup" },
    ],
    resize_enabled: true,
    language: DEFAULT_LOCALE,
    disableNativeSpellChecker: false,
    linkShowTargetTab: false,
};

export class TextEditor implements TextEditorInterface {
    private ckeditor: CKEDITOR.editor | null = null;

    constructor(
        private readonly textarea: HTMLTextAreaElement,
        private readonly options: InternalTextEditorOptions,
        private readonly markdown_converter: HTMLToMarkdownConverterInterface,
        private readonly markdown_renderer: MarkdownToHTMLRendererInterface,
    ) {}

    public init(new_format: TextFieldFormat): void {
        if (new_format === TEXT_FORMAT_HTML) {
            if (!this.ckeditor) {
                this.ckeditor = this.initCKEditor();
            }
            this.ckeditor.setData(this.textarea.value);
        }
        this.options.onFormatChange(new_format, this.getContent());
    }

    public onFormatChange(new_format: TextFieldFormat): void {
        if (new_format === TEXT_FORMAT_HTML) {
            if (!this.ckeditor) {
                this.ckeditor = this.initCKEditor();
            }
            this.ckeditor.setData(this.markdown_renderer.render(this.textarea.value));
            this.options.onFormatChange(new_format, this.ckeditor.getData());
            return;
        }

        if (this.ckeditor) {
            const text = this.markdown_converter.convert(this.ckeditor.getData());
            this.ckeditor.destroy();
            this.ckeditor = null;
            this.textarea.value = text;
        }

        this.options.onFormatChange(new_format, this.textarea.value);
    }

    public getContent(): string {
        if (this.ckeditor) {
            return this.ckeditor.getData();
        }
        return this.textarea.value;
    }

    public destroy(): void {
        this.ckeditor?.destroy();
    }

    private initCKEditor(): CKEDITOR.editor {
        if (CKEDITOR.instances && CKEDITOR.instances[this.textarea.id]) {
            CKEDITOR.instances[this.textarea.id].destroy(true);
        }

        const dimensions = this.textarea.getBoundingClientRect();
        const replace_options = {
            ...CKEDITOR_DEFAULT_OPTIONS,
            language: this.options.locale,
            height: dimensions.height,
            width: dimensions.width,
            ...this.options.getAdditionalOptions(this.textarea),
        };
        const editor = CKEDITOR.replace(this.textarea, replace_options);
        this.ckeditor = editor;
        editor.on("dataReady", () => this.options.onEditorDataReady(editor));
        this.options.onEditorInit(editor, this.textarea);

        return editor;
    }
}

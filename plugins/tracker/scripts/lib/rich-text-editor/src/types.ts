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

import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";

type EmptyObject = Record<string, never>;

type AdditionalOptionsProvider = (textarea: HTMLTextAreaElement) => CKEDITOR.config | EmptyObject;
type EditorInitCallback = (ckeditor: CKEDITOR.editor, textarea: HTMLTextAreaElement) => void;
type FormatChangedCallback = (new_format: TextFieldFormat, new_content: string) => void;
type DataReadyCallback = (ckeditor: CKEDITOR.editor) => void;

export interface InternalTextEditorOptions {
    locale: string;
    getAdditionalOptions: AdditionalOptionsProvider;
    onEditorInit: EditorInitCallback;
    onFormatChange: FormatChangedCallback;
    onEditorDataReady: DataReadyCallback;
}

export interface RichTextEditorOptions {
    format_selectbox_id: string;
    format_selectbox_name?: string;
    format_selectbox_value?: TextFieldFormat;
    getAdditionalOptions?: AdditionalOptionsProvider;
    onEditorInit?: EditorInitCallback;
    onFormatChange?: FormatChangedCallback;
    onEditorDataReady?: DataReadyCallback;
}

export interface HTMLToMarkdownConverterInterface {
    convert(html: string): string;
}

export interface MarkdownToHTMLRendererInterface {
    render(markdown: string): string;
}

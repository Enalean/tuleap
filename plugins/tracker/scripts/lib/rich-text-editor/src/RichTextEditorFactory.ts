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

import fr_FR from "../po/fr_FR.po";
import pt_BR from "../po/pt_BR.po";
import { initGettextSync } from "@tuleap/gettext";
import TurndownService from "turndown";
import { parse } from "marked";
import { FlamingParrotDocumentAdapter } from "./format-selector/FlamingParrotDocumentAdapter";
import type {
    HTMLToMarkdownConverterInterface,
    MarkdownToHTMLRendererInterface,
    RichTextEditorOptions,
} from "./types";
import { TextEditor } from "./TextEditor";
import type { FormatSelectorInterface } from "./format-selector/FormatSelectorInterface";
import type { TextEditorInterface } from "./TextEditorInterface";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import { defaultOptionsIfNotProvided } from "./options-defaulter";
import { ExistingFormatSelector } from "./format-selector/ExistingFormatSelector";
import { FlamingParrotEditorAreaBuilder } from "./format-selector/editor-area/FlamingParrotEditorAreaBuilder";
import { EditorAreaRenderer } from "./format-selector/editor-area/EditorAreaRenderer";

export class RichTextEditorFactory {
    private readonly markdown_converter: HTMLToMarkdownConverterInterface;
    private readonly markdown_renderer: MarkdownToHTMLRendererInterface;

    private constructor(
        private readonly format_selector: FormatSelectorInterface,
        private readonly default_format: TextFieldFormat,
        private readonly locale: string,
    ) {
        const turndown_service = new TurndownService({ emDelimiter: "*" });
        this.markdown_converter = {
            convert: (html: string): string => turndown_service.turndown(html),
        };
        this.markdown_renderer = {
            render: (markdown: string): string => parse(markdown),
        };
    }

    public createRichTextEditor(
        textarea: HTMLTextAreaElement,
        options: RichTextEditorOptions,
    ): TextEditorInterface {
        const defaulted_options = defaultOptionsIfNotProvided(this.locale, options);
        const editor = new TextEditor(
            textarea,
            defaulted_options,
            this.markdown_converter,
            this.markdown_renderer,
        );
        const selected_value =
            options.format_selectbox_value !== undefined
                ? options.format_selectbox_value
                : this.default_format;
        this.format_selector.insertFormatSelectbox(textarea, {
            id: options.format_selectbox_id,
            name: options.format_selectbox_name,
            selected_value,
            editor,
        });
        editor.init(selected_value);
        return editor;
    }

    public static forFlamingParrotWithFormatSelector(
        doc: Document,
        locale: string,
    ): RichTextEditorFactory {
        const gettext_provider = initGettextSync("rich-text-editor", { fr_FR, pt_BR }, locale);
        const document_adapter = new FlamingParrotDocumentAdapter(doc);
        const builder = new FlamingParrotEditorAreaBuilder(
            document_adapter,
            new EditorAreaRenderer(gettext_provider),
        );
        const default_format = document_adapter.getDefaultFormat();
        return new RichTextEditorFactory(builder, default_format, locale);
    }

    public static forFlamingParrotWithExistingFormatSelector(
        doc: Document,
        locale: string,
    ): RichTextEditorFactory {
        const document_adapter = new FlamingParrotDocumentAdapter(doc);
        const default_format = document_adapter.getDefaultFormat();
        const format_selector = new ExistingFormatSelector(doc);
        return new RichTextEditorFactory(format_selector, default_format, locale);
    }

    public static forBurningParrotWithExistingFormatSelector(
        doc: Document,
        locale: string,
        default_format: TextFieldFormat,
    ): RichTextEditorFactory {
        const format_selector = new ExistingFormatSelector(doc);
        return new RichTextEditorFactory(format_selector, default_format, locale);
    }
}

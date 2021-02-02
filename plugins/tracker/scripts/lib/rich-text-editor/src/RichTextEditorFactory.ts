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

import french_translations from "../po/fr_FR.po";
import { initGettextSync } from "@tuleap/gettext";
import TurndownService from "turndown";
import marked from "marked";
import { FlamingParrotDocumentAdapter } from "./FlamingParrotDocumentAdapter";
import {
    HTMLToMarkdownConverterInterface,
    MarkdownToHTMLRendererInterface,
    RichTextEditorOptions,
} from "./types";
import { TextEditor } from "./TextEditor";
import { DisplayInterface } from "./DisplayInterface";
import { FormatSelectorBuilder } from "./FormatSelectorBuilder";
import { TextFieldFormat } from "../../../constants/fields-constants";
import { defaultOptionsIfNotProvided } from "./options-defaulter";

export class RichTextEditorFactory {
    private readonly display_interface: DisplayInterface;
    private readonly default_format: TextFieldFormat;
    private readonly markdown_converter: HTMLToMarkdownConverterInterface;
    private readonly markdown_renderer: MarkdownToHTMLRendererInterface;

    constructor(doc: Document, private readonly locale: string) {
        const document_adapter = new FlamingParrotDocumentAdapter(doc);
        this.default_format = document_adapter.getDefaultFormat();

        const gettext_provider = initGettextSync(
            "rich-text-editor",
            french_translations,
            this.locale
        );

        this.display_interface = new FormatSelectorBuilder(document_adapter, gettext_provider);
        const turndown_service = new TurndownService();
        this.markdown_converter = {
            convert: (html: string): string => turndown_service.turndown(html),
        };
        this.markdown_renderer = {
            render: (markdown: string): string => marked(markdown),
        };
    }

    public createRichTextEditor(
        textarea: HTMLTextAreaElement,
        options: RichTextEditorOptions
    ): void {
        const defaulted_options = defaultOptionsIfNotProvided(this.locale, options);
        const editor = new TextEditor(
            textarea,
            defaulted_options,
            this.markdown_converter,
            this.markdown_renderer
        );
        this.display_interface.insertFormatSelectbox(textarea, {
            id: options.format_selectbox_id,
            name: options.format_selectbox_name,
            default_format: this.default_format,
            formatChangedCallback: (new_format) => {
                editor.onFormatChange(new_format);
            },
        });
        editor.onFormatChange(this.default_format);
    }
}

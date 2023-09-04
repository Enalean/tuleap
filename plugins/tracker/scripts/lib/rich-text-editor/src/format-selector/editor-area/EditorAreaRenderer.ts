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

import type { GettextProvider } from "@tuleap/gettext";
import type { TemplateResult } from "lit/html.js";
import type { EditorAreaStateInterface } from "./EditorAreaStateInterface";
import "./FlamingParrotPopoverButtonElement";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";
import { renderHTMLOrTextEditor, renderMarkdownEditor } from "./lit-html-adapter";
import { createSyntaxHelpButton } from "./components/SyntaxHelpButton";
import { createPreviewEditButton } from "./components/PreviewEditButton";
import { createPreviewArea } from "./components/PreviewArea";
import { createSelect } from "./components/FormatSelect";
import { createFormatHiddenInput } from "./components/FormatHiddenInput";
import { wrapTextArea } from "./components/TextArea";

const SELECTBOX_ID_PREFIX = "rte_format_selectbox";
const SELECTBOX_NAME_PREFIX = "comment_format";

const getFormatSelectboxName = (state: EditorAreaStateInterface): string =>
    state.selectbox_name ? state.selectbox_name : SELECTBOX_NAME_PREFIX + state.selectbox_id;

export class EditorAreaRenderer {
    constructor(private readonly gettext_provider: GettextProvider) {}

    public render(state: EditorAreaStateInterface): void {
        if (state.isCurrentFormatCommonMark()) {
            this.renderMarkdown(state);
            return;
        }
        const selectbox = this.createSelectbox(state);
        const textarea = wrapTextArea({
            promise_of_preview: Promise.resolve(state.rendered_html),
            is_hidden: !state.isInEditMode(),
            textarea: state.textarea,
        });

        renderHTMLOrTextEditor(
            {
                mount_point: state.mount_point,
                selectbox,
                textarea,
            },
            this.gettext_provider,
        );
    }

    private renderMarkdown(state: EditorAreaStateInterface): void {
        const help_button = createSyntaxHelpButton(
            { is_disabled: !state.isInEditMode() },
            this.gettext_provider,
        );
        const preview_button = createPreviewEditButton(
            {
                is_in_edit_mode: state.isInEditMode(),
                promise_of_preview: Promise.resolve(state.rendered_html),
                onClickCallback: () => this.onEditPreviewClick(state),
            },
            this.gettext_provider,
        );
        const preview_area = createPreviewArea(state.rendered_html, this.gettext_provider);
        const selectbox = this.createSelectbox(state);

        const textarea = wrapTextArea({
            promise_of_preview: Promise.resolve(state.rendered_html),
            is_hidden: !state.isInEditMode(),
            textarea: state.textarea,
        });

        let hidden_format_input;
        if (!state.isInEditMode()) {
            hidden_format_input = createFormatHiddenInput({
                name: getFormatSelectboxName(state),
                value: state.current_format,
            });
        }

        renderMarkdownEditor(
            {
                mount_point: state.mount_point,
                selectbox,
                textarea,
                help_button,
                preview_button,
                preview_area,
                hidden_format_input,
            },
            this.gettext_provider,
        );
    }

    private onEditPreviewClick(state: EditorAreaStateInterface): void {
        if (state.isInEditMode()) {
            state.switchToPreviewMode();
            this.render(state);
            return;
        }
        state.switchToEditMode();
        this.render(state);
    }

    private createSelectbox(state: EditorAreaStateInterface): TemplateResult {
        return createSelect(
            {
                id: SELECTBOX_ID_PREFIX + state.selectbox_id,
                name: getFormatSelectboxName(state),
                is_disabled: !state.isInEditMode(),
                options: [TEXT_FORMAT_TEXT, TEXT_FORMAT_HTML, TEXT_FORMAT_COMMONMARK],
                selected_value: state.current_format,
                formatChangedCallback: (new_value) => {
                    state.changeFormat(new_value);
                    this.render(state);
                },
            },
            this.gettext_provider,
        );
    }
}

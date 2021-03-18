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
import type { TemplateResult } from "lit-html";
import {
    createSelect,
    createSyntaxHelpButton,
    renderRichTextEditorArea,
    wrapTextArea,
} from "./lit-html-adapter";
import type { EditorAreaState } from "./EditorAreaState";
import "./FlamingParrotPopoverButtonElement";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "../../../../../constants/fields-constants";

const SELECTBOX_ID_PREFIX = "rte_format_selectbox";
const SELECTBOX_NAME_PREFIX = "comment_format";

export class EditorAreaRenderer {
    constructor(private readonly gettext_provider: GettextProvider) {}

    public render(state: EditorAreaState): void {
        let helper_button;
        if (state.isCurrentFormatMarkdown()) {
            helper_button = createSyntaxHelpButton(this.gettext_provider);
        }
        const textarea = wrapTextArea(state.textarea);
        const selectbox = this.createSelectbox(state);

        renderRichTextEditorArea(
            {
                mount_point: state.mount_point,
                selectbox,
                helper_button,
                textarea,
            },
            this.gettext_provider
        );
    }

    private createSelectbox(state: EditorAreaState): TemplateResult {
        const selectbox_name = state.selectbox_name
            ? state.selectbox_name
            : SELECTBOX_NAME_PREFIX + state.selectbox_id;
        return createSelect(
            {
                id: SELECTBOX_ID_PREFIX + state.selectbox_id,
                name: selectbox_name,
                options: [TEXT_FORMAT_TEXT, TEXT_FORMAT_HTML, TEXT_FORMAT_COMMONMARK],
                selected_value: state.selected_value,
                formatChangedCallback: (new_value) => {
                    state.onFormatChange(new_value);
                    this.render(state);
                },
            },
            this.gettext_provider
        );
    }
}

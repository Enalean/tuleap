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

import { GettextProvider } from "@tuleap/gettext";
import {
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_TEXT,
    TextFieldFormat,
} from "../../../constants/fields-constants";
import { DisplayInterface, FormatSelectorPresenter } from "./DisplayInterface";
import { FlamingParrotDocumentAdapter } from "./FlamingParrotDocumentAdapter";

const isValidFormat = (value: string): value is TextFieldFormat =>
    TEXT_FORMAT_TEXT === value || TEXT_FORMAT_HTML === value || TEXT_FORMAT_COMMONMARK === value;

const SELECTBOX_ID_PREFIX = "rte_format_selectbox";
const SELECTBOX_NAME_PREFIX = "comment_format";

export class FormatSelectorBuilder implements DisplayInterface {
    constructor(
        private readonly doc: FlamingParrotDocumentAdapter,
        private readonly gettext_provider: GettextProvider
    ) {}

    public insertFormatSelectbox(
        textarea: HTMLTextAreaElement,
        presenter: FormatSelectorPresenter
    ): void {
        const text_option = this.doc.createOption({
            text: this.gettext_provider.gettext("Text"),
            value: TEXT_FORMAT_TEXT,
            is_selected: presenter.selected_value === TEXT_FORMAT_TEXT,
        });
        const html_option = this.doc.createOption({
            text: this.gettext_provider.gettext("HTML"),
            value: TEXT_FORMAT_HTML,
            is_selected: presenter.selected_value === TEXT_FORMAT_HTML,
        });
        const markdown_option = this.doc.createOption({
            text: this.gettext_provider.gettext("Markdown"),
            value: TEXT_FORMAT_COMMONMARK,
            is_selected: presenter.selected_value === TEXT_FORMAT_COMMONMARK,
        });

        const selectbox_name = presenter.name
            ? presenter.name
            : SELECTBOX_NAME_PREFIX + presenter.id;
        const selectbox = this.doc.createSelectBox({
            id: SELECTBOX_ID_PREFIX + presenter.id,
            name: selectbox_name,
            onInputCallback: (new_value) => {
                if (isValidFormat(new_value)) {
                    presenter.formatChangedCallback(new_value);
                }
            },
            options: [text_option, html_option, markdown_option],
        });

        const wrapper = this.doc.createSelectBoxWrapper({
            label: this.gettext_provider.gettext("Format:"),
            child: selectbox,
        });

        this.doc.insertFormatWrapper(textarea, wrapper);
    }
}

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
import type { TextFieldFormat } from "../../../constants/fields-constants";
import {
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_TEXT,
    isValidTextFormat,
} from "../../../constants/fields-constants";
import type { DisplayInterface, FormatSelectorPresenter } from "./DisplayInterface";
import type { FlamingParrotDocumentAdapter } from "./FlamingParrotDocumentAdapter";
import { getCommonMarkSyntaxPopoverHelperContent } from "./helper/commonmark-syntax-helper";
import { SyntaxHelperButtonToggler } from "./SyntaxHelperButtonToggler";

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

        const button_helper = this.doc.createCommonMarkSyntaxHelperButton(
            {
                label: this.gettext_provider.gettext("Help"),
                popover_content: getCommonMarkSyntaxPopoverHelperContent(this.gettext_provider),
            },
            presenter.selected_value
        );

        const button_helper_toggler = new SyntaxHelperButtonToggler(button_helper);

        const selectbox_name = presenter.name
            ? presenter.name
            : SELECTBOX_NAME_PREFIX + presenter.id;
        const selectbox = this.doc.createSelectBox({
            id: SELECTBOX_ID_PREFIX + presenter.id,
            name: selectbox_name,
            onInputCallback: (new_value) => {
                if (isValidTextFormat(new_value)) {
                    this.handleInputValueChangeCallback(
                        button_helper_toggler,
                        new_value,
                        presenter
                    );
                }
            },
            options: [text_option, html_option, markdown_option],
        });

        const wrapper = this.doc.createFormatWrapper({
            label: this.gettext_provider.gettext("Format:"),
            selectbox,
            button_helper,
        });

        this.doc.insertFormatWrapper(textarea, wrapper);
    }

    private handleInputValueChangeCallback(
        button_toggler: SyntaxHelperButtonToggler,
        new_value: TextFieldFormat,
        presenter: FormatSelectorPresenter
    ): void {
        presenter.formatChangedCallback(new_value);
        if (new_value === TEXT_FORMAT_COMMONMARK) {
            button_toggler.show();
        } else {
            button_toggler.hideAndDismissPopover();
        }
    }
}

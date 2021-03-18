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

import type { TemplateResult } from "lit-html";
import { html, render } from "lit-html";
import type { TextFieldFormat } from "../../../../../constants/fields-constants";
import {
    isValidTextFormat,
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "../../../../../constants/fields-constants";
import type { GettextProvider } from "@tuleap/gettext";
import { getCommonMarkSyntaxPopoverHelperContent } from "./commonmark-syntax-helper";

interface OptionPresenter {
    readonly value: TextFieldFormat;
    readonly is_selected: boolean;
    readonly text: string;
}

const createOption = (presenter: OptionPresenter): TemplateResult => html`
    <option value="${presenter.value}" ?selected=${presenter.is_selected}>${presenter.text}</option>
`;

const createOptions = (
    presenter: SelectboxPresenter,
    gettext_provider: GettextProvider
): TemplateResult[] =>
    presenter.options.map((format) => {
        const is_selected = format === presenter.selected_value;
        if (format === TEXT_FORMAT_TEXT) {
            return createOption({
                value: TEXT_FORMAT_TEXT,
                is_selected,
                text: gettext_provider.gettext("Text"),
            });
        }
        if (format === TEXT_FORMAT_HTML) {
            return createOption({
                value: TEXT_FORMAT_HTML,
                is_selected,
                text: gettext_provider.gettext("HTML"),
            });
        }
        return createOption({
            value: TEXT_FORMAT_COMMONMARK,
            is_selected,
            text: gettext_provider.gettext("Markdown"),
        });
    });

type FormatChangedCallback = (new_value: TextFieldFormat) => void;

export interface SelectboxPresenter {
    readonly id: string;
    readonly name: string;
    readonly options: TextFieldFormat[];
    readonly selected_value: TextFieldFormat;
    readonly formatChangedCallback: FormatChangedCallback;
}

export function createSelect(
    presenter: SelectboxPresenter,
    gettext_provider: GettextProvider
): TemplateResult {
    const inputHandler = (event: InputEvent): void => {
        if (event.target instanceof HTMLSelectElement && isValidTextFormat(event.target.value)) {
            presenter.formatChangedCallback(event.target.value);
        }
    };
    const options = createOptions(presenter, gettext_provider);
    return html`
        <select
            id="${presenter.id}"
            name="${presenter.name}"
            class="input-small"
            @input="${inputHandler}"
            data-test="format-select"
        >
            ${options}
        </select>
    `;
}

export function createSyntaxHelpButton(gettext_provider: GettextProvider): TemplateResult {
    const helper_popover = getCommonMarkSyntaxPopoverHelperContent(gettext_provider);
    return html`
        <fp-popover-button>
            <button type="button" class="btn btn-small rte-button" data-button>
                <i class="fas fa-question-circle" aria-hidden="true"></i>
                ${gettext_provider.gettext("Help")}
            </button>
            <template data-popover-content>${helper_popover}</template>
        </fp-popover-button>
    `;
}

export const wrapTextArea = (textarea: HTMLTextAreaElement): TemplateResult =>
    html`
        ${textarea}
    `;

export interface RichTextEditorAreaPresenter {
    readonly mount_point: HTMLDivElement;
    readonly selectbox: TemplateResult;
    readonly helper_button?: TemplateResult;
    readonly textarea: TemplateResult;
}

export const renderRichTextEditorArea = (
    presenter: RichTextEditorAreaPresenter,
    gettext_provider: GettextProvider
): void =>
    render(
        html`
            <div class="rte_format">
                ${gettext_provider.gettext(
                    "Format:"
                )}${presenter.selectbox}${presenter.helper_button}
            </div>
            ${presenter.textarea}
        `,
        presenter.mount_point
    );

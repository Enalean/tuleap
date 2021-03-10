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

import type { TextFieldFormat } from "../../../constants/fields-constants";
import { TEXT_FORMAT_COMMONMARK, TEXT_FORMAT_HTML } from "../../../constants/fields-constants";

import $ from "jquery";

export const HTML_FORMAT_CLASSNAME = "default_format_html";
const FLAMING_PARROT_SELECTBOX_CLASSNAME = "input-small";
const FLAMING_PARROT_WRAPPER_CLASSNAME = "rte_format";

export interface OptionPresenter {
    is_selected: boolean;
    text: string;
    value: string;
}

export type SelectboxInputCallback = (new_value: string) => void;

export interface SelectboxPresenter {
    id: string;
    name: string;
    onInputCallback: SelectboxInputCallback;
    options: HTMLOptionElement[];
}

export interface FormatWrapperPresenter {
    label: string;
    selectbox: HTMLSelectElement;
    button_helper: HTMLButtonElement;
}

export interface SyntaxButtonPresenter {
    label: string;
    popover_content: string;
}

export class FlamingParrotDocumentAdapter {
    constructor(private readonly doc: Document) {}

    public getDefaultFormat(): TextFieldFormat {
        return this.doc.body.classList.contains(HTML_FORMAT_CLASSNAME)
            ? TEXT_FORMAT_HTML
            : TEXT_FORMAT_COMMONMARK;
    }

    public createOption(presenter: OptionPresenter): HTMLOptionElement {
        const option = this.doc.createElement("option");
        option.value = presenter.value;
        option.selected = presenter.is_selected;
        option.text = presenter.text;
        return option;
    }

    public createSelectBox(presenter: SelectboxPresenter): HTMLSelectElement {
        const select = this.doc.createElement("select");
        select.id = presenter.id;
        select.name = presenter.name;
        select.classList.add(FLAMING_PARROT_SELECTBOX_CLASSNAME);
        select.append(...presenter.options);
        select.addEventListener("input", () => {
            presenter.onInputCallback(select.value);
        });
        return select;
    }

    public createFormatWrapper(presenter: FormatWrapperPresenter): HTMLDivElement {
        const wrapper = this.doc.createElement("div");
        wrapper.textContent = presenter.label;
        wrapper.classList.add(FLAMING_PARROT_WRAPPER_CLASSNAME);
        wrapper.append(presenter.selectbox, presenter.button_helper);
        return wrapper;
    }

    public insertFormatWrapper(textarea: HTMLTextAreaElement, wrapper: HTMLDivElement): void {
        textarea.insertAdjacentElement("beforebegin", wrapper);
    }

    public createCommonMarkSyntaxHelperButton(
        button_presenter: SyntaxButtonPresenter,
        format: TextFieldFormat
    ): HTMLButtonElement {
        const button = this.doc.createElement("button");
        button.type = "button";
        button.classList.add("btn", "btn-small", "commonmark-button-help");

        if (format === TEXT_FORMAT_COMMONMARK) {
            button.classList.add("commonmark-button-help-show");
        }

        const help_icon = this.doc.createElement("i");
        help_icon.classList.add("fas", "fa-question-circle", "help-button-icon");
        button.append(help_icon);
        button.insertAdjacentText("beforeend", button_presenter.label);
        this.bindToButtonHelpThePopover(button, button_presenter.popover_content);
        return button;
    }

    private bindToButtonHelpThePopover(
        help_button: HTMLButtonElement,
        popover_content: string
    ): void {
        $(help_button).popover({
            content: popover_content,
            trigger: "click",
            html: true,
            placement: "right",
        });
    }
}

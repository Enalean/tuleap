/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import { selectOrThrow } from "@tuleap/dom";
import { createModal } from "@tuleap/tlp-modal";

export const EDIT_CONFIG_SELECTOR = "#edit-config-button";
export const EDIT_CONFIGURATION_MODAL_SELECTOR = "#edit-config-modal";
const FORM_SELECTOR = "#edit-config-modal-form";
export const HAS_PREFIX_CHECKBOX_SELECTOR = "#edit-config-modal-has-prefix";
export const PREFIX_INPUT_SELECTOR = "#edit-config-branch-prefix";
export const PREFIX_ICON_SELECTOR = "#edit-config-branch-prefix-icon";
export const PREFIX_BOX_SELECTOR = "#edit-config-branch-prefix-box";

export const HIDDEN_ICON_CLASSNAME = "gitlab-modal-icon-hidden";
export const FORM_ELEMENT_DISABLED_CLASSNAME = "tlp-form-element-disabled";

type EditConfigurationModalType = {
    init(): void;
};

export const EditConfigurationModal = (doc: Document): EditConfigurationModalType => {
    const edit_button = selectOrThrow(doc, EDIT_CONFIG_SELECTOR, HTMLButtonElement);
    const edit_modal = selectOrThrow(doc, EDIT_CONFIGURATION_MODAL_SELECTOR);
    const edit_modal_form = selectOrThrow(edit_modal, FORM_SELECTOR, HTMLFormElement);
    const prefix_checkbox = selectOrThrow(
        edit_modal,
        HAS_PREFIX_CHECKBOX_SELECTOR,
        HTMLInputElement
    );
    const prefix_box = selectOrThrow(edit_modal, PREFIX_BOX_SELECTOR);
    const prefix_icon = selectOrThrow(prefix_box, PREFIX_ICON_SELECTOR);
    const prefix_input = selectOrThrow(prefix_box, PREFIX_INPUT_SELECTOR, HTMLInputElement);

    const modal_instance = createModal(edit_modal);

    const onPrefixCheckboxChange = (): void => {
        const use_prefix = prefix_checkbox.checked;

        prefix_input.required = use_prefix;
        prefix_input.disabled = !use_prefix;
        prefix_box.classList.toggle(FORM_ELEMENT_DISABLED_CLASSNAME, !use_prefix);
        prefix_icon.classList.toggle(HIDDEN_ICON_CLASSNAME, !use_prefix);
    };

    const onClickEdit = (): void => {
        onPrefixCheckboxChange();
        modal_instance.show();
    };

    const onSubmit = (event: Event): void => {
        event.preventDefault();
    };

    return {
        init(): void {
            edit_button.addEventListener("click", onClickEdit);
            prefix_checkbox.addEventListener("input", onPrefixCheckboxChange);
            edit_modal_form.addEventListener("submit", onSubmit);
        },
    };
};

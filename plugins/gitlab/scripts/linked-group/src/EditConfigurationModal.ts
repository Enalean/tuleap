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
import {
    FEEDBACK_HIDDEN_CLASSNAME,
    FORM_ELEMENT_DISABLED_CLASSNAME,
    HIDDEN_ICON_CLASSNAME,
} from "./classnames";
import { patchJSON, uri } from "@tuleap/fetch-result";
import type { GetText } from "@tuleap/gettext";
import { createModal } from "@tuleap/tlp-modal";
import { sprintf } from "sprintf-js";

export const EDIT_CONFIG_SELECTOR = "#edit-config-button";
export const EDIT_CONFIGURATION_MODAL_SELECTOR = "#edit-config-modal";
const FORM_SELECTOR = "#edit-config-modal-form";
export const MODAL_FEEDBACK_SELECTOR = "#edit-config-modal-feedback";
const ALERT_SELECTOR = "#edit-config-modal-alert";
export const HAS_PREFIX_CHECKBOX_SELECTOR = "#edit-config-modal-has-prefix";
export const PREFIX_INPUT_SELECTOR = "#edit-config-branch-prefix";
export const PREFIX_ICON_SELECTOR = "#edit-config-branch-prefix-icon";
export const PREFIX_BOX_SELECTOR = "#edit-config-branch-prefix-box";
export const ALLOW_ARTIFACT_CLOSURE_INPUT_SELECTOR = "#edit-config-modal-allow-artifact-closure";
export const EDIT_CONFIRM_SELECTOR = "#edit-config-confirm";
export const EDIT_CONFIRM_ICON_SELECTOR = "#edit-icon";
export const ALLOW_ARTIFACT_CLOSURE_DISPLAY_SELECTOR = "#group-information-allow-artifact-closure";
export const PREFIX_DISPLAY_SELECTOR = "#group-information-branch-prefix";
export const FORM_ELEMENTS_SELECTOR = "[data-form-element]";
export const INPUTS_SELECTOR = "input";

type UpdatedGroup = {
    readonly create_branch_prefix: string;
    readonly allow_artifact_closure: boolean;
};

type EditConfigurationModalType = {
    init(): void;
};

export const EditConfigurationModal = (
    doc: Document,
    gettext_provider: GetText,
    group_id: number,
): EditConfigurationModalType => {
    const edit_button = selectOrThrow(doc, EDIT_CONFIG_SELECTOR, HTMLButtonElement);
    const edit_modal = selectOrThrow(doc, EDIT_CONFIGURATION_MODAL_SELECTOR);
    const allow_closure_display = selectOrThrow(doc, ALLOW_ARTIFACT_CLOSURE_DISPLAY_SELECTOR);
    const prefix_display = selectOrThrow(doc, PREFIX_DISPLAY_SELECTOR);
    const edit_modal_form = selectOrThrow(edit_modal, FORM_SELECTOR, HTMLFormElement);
    const modal_feedback = selectOrThrow(edit_modal, MODAL_FEEDBACK_SELECTOR);
    const prefix_checkbox = selectOrThrow(
        edit_modal,
        HAS_PREFIX_CHECKBOX_SELECTOR,
        HTMLInputElement,
    );
    const allow_closure_checkbox = selectOrThrow(
        edit_modal,
        ALLOW_ARTIFACT_CLOSURE_INPUT_SELECTOR,
        HTMLInputElement,
    );
    const prefix_box = selectOrThrow(edit_modal, PREFIX_BOX_SELECTOR);
    const form_elements = edit_modal.querySelectorAll(FORM_ELEMENTS_SELECTOR);
    const form_inputs = edit_modal.querySelectorAll(INPUTS_SELECTOR);
    const prefix_icon = selectOrThrow(prefix_box, PREFIX_ICON_SELECTOR);
    const prefix_input = selectOrThrow(prefix_box, PREFIX_INPUT_SELECTOR, HTMLInputElement);
    const confirm_edit_button = selectOrThrow(edit_modal, EDIT_CONFIRM_SELECTOR, HTMLButtonElement);
    const confirm_edit_icon = selectOrThrow(confirm_edit_button, EDIT_CONFIRM_ICON_SELECTOR);

    const modal_instance = createModal(edit_modal);

    const onPrefixCheckboxChange = (): void => {
        const use_prefix = prefix_checkbox.checked;

        prefix_input.required = use_prefix;
        prefix_input.disabled = !use_prefix;
        prefix_box.classList.toggle(FORM_ELEMENT_DISABLED_CLASSNAME, !use_prefix);
        prefix_icon.classList.toggle(HIDDEN_ICON_CLASSNAME, !use_prefix);
    };

    const toggleLoadingState = (is_loading: boolean): void => {
        confirm_edit_icon.classList.toggle(HIDDEN_ICON_CLASSNAME, !is_loading);
        confirm_edit_button.disabled = is_loading;
        form_inputs.forEach((input) => {
            input.disabled = is_loading;
        });
        form_elements.forEach((form_element) => {
            form_element.classList.toggle(FORM_ELEMENT_DISABLED_CLASSNAME, is_loading);
        });
    };

    const onClickEdit = (): void => {
        onPrefixCheckboxChange();
        modal_instance.show();
    };

    const onSubmit = async (event: Event): Promise<void> => {
        event.preventDefault();
        const create_branch_prefix = prefix_checkbox.checked ? prefix_input.value : "";

        modal_feedback.classList.add(FEEDBACK_HIDDEN_CLASSNAME);

        toggleLoadingState(true);
        await patchJSON<UpdatedGroup>(uri`/api/gitlab_groups/${group_id}`, {
            create_branch_prefix,
            allow_artifact_closure: allow_closure_checkbox.checked,
        }).match(
            (updated_group: UpdatedGroup) => {
                allow_closure_display.textContent = updated_group.allow_artifact_closure
                    ? gettext_provider.gettext("Yes")
                    : gettext_provider.gettext("No");
                prefix_display.textContent = updated_group.create_branch_prefix;
                prefix_input.value = updated_group.create_branch_prefix;
                modal_instance.hide();
            },
            (fault) => {
                const modal_alert = selectOrThrow(modal_feedback, ALERT_SELECTOR);
                modal_feedback.classList.remove(FEEDBACK_HIDDEN_CLASSNAME);
                modal_alert.textContent = sprintf(
                    gettext_provider.gettext("Error during the update of configuration: %(error)s"),
                    { error: String(fault) },
                );
            },
        );
        toggleLoadingState(false);
    };

    return {
        init(): void {
            edit_button.addEventListener("click", onClickEdit);
            prefix_checkbox.addEventListener("input", onPrefixCheckboxChange);
            edit_modal_form.addEventListener("submit", onSubmit);
        },
    };
};

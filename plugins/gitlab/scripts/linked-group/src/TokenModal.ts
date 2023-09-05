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
import type { GetText } from "@tuleap/gettext";
import { createModal } from "@tuleap/tlp-modal";
import { get } from "@tuleap/plugin-git-gitlab-api-querier";
import type { Fault } from "@tuleap/fault";
import { sprintf } from "sprintf-js";
import { patchJSON, uri, rawUri } from "@tuleap/fetch-result";
import {
    FEEDBACK_HIDDEN_CLASSNAME,
    FORM_ELEMENT_DISABLED_CLASSNAME,
    HIDDEN_ICON_CLASSNAME,
} from "./classnames";
import type { GroupInformation } from "./GroupInformation";

export const EDIT_TOKEN_BUTTON_SELECTOR = "#edit-token-button";
export const TOKEN_MODAL_SELECTOR = "#token-modal";
const FORM_SELECTOR = "#token-modal-form";
export const MODAL_FEEDBACK_SELECTOR = "#token-modal-feedback";
const ALERT_SELECTOR = "#token-modal-alert";
export const TOKEN_INPUT_SELECTOR = "#token-modal-token-input";
export const EDIT_CONFIRM_SELECTOR = "#token-modal-confirm";
export const EDIT_CONFIRM_ICON_SELECTOR = "#token-icon";
export const FORM_ELEMENTS_SELECTOR = "[data-form-element]";
export const INPUTS_SELECTOR = "input";

type TokenModalType = {
    init(): void;
};

const isCredentialsFault = (fault: Fault): boolean =>
    "isUnauthenticated" in fault && fault.isUnauthenticated() === true;
const isGitLabFault = (fault: Fault): boolean =>
    "isGitlabAPIFault" in fault && fault.isGitlabAPIFault() === true;

export const TokenModal = (
    doc: Document,
    gettext_provider: GetText,
    group: GroupInformation,
): TokenModalType => {
    const edit_button = selectOrThrow(doc, EDIT_TOKEN_BUTTON_SELECTOR, HTMLButtonElement);
    const token_modal = selectOrThrow(doc, TOKEN_MODAL_SELECTOR);
    const token_form = selectOrThrow(token_modal, FORM_SELECTOR);
    const modal_feedback = selectOrThrow(token_modal, MODAL_FEEDBACK_SELECTOR);
    const token_input = selectOrThrow(token_modal, TOKEN_INPUT_SELECTOR, HTMLInputElement);
    const form_elements = token_modal.querySelectorAll(FORM_ELEMENTS_SELECTOR);
    const form_inputs = token_modal.querySelectorAll(INPUTS_SELECTOR);
    const confirm_button = selectOrThrow(token_modal, EDIT_CONFIRM_SELECTOR, HTMLButtonElement);
    const confirm_icon = selectOrThrow(confirm_button, EDIT_CONFIRM_ICON_SELECTOR);

    const modal_instance = createModal(token_modal);

    const toggleLoadingState = (is_loading: boolean): void => {
        confirm_icon.classList.toggle(HIDDEN_ICON_CLASSNAME, !is_loading);
        confirm_button.disabled = is_loading;
        form_inputs.forEach((input) => {
            input.disabled = is_loading;
        });
        form_elements.forEach((form_element) => {
            form_element.classList.toggle(FORM_ELEMENT_DISABLED_CLASSNAME, is_loading);
        });
    };

    const onClickEdit = (): void => {
        modal_instance.show();
    };

    const onSubmit = (event: Event): void => {
        event.preventDefault();
        modal_feedback.classList.add(FEEDBACK_HIDDEN_CLASSNAME);

        toggleLoadingState(true);
        const new_token = token_input.value;
        get(uri`${rawUri(group.gitlab_server_uri)}/api/v4/groups/${group.gitlab_group_id}`, {
            token: new_token,
        })
            .andThen(() =>
                patchJSON<undefined>(uri`/api/gitlab_groups/${group.id}`, {
                    gitlab_token: new_token,
                }),
            )
            .match(
                () => {
                    toggleLoadingState(false);
                    modal_instance.hide();
                },
                (fault) => {
                    toggleLoadingState(false);
                    const modal_alert = selectOrThrow(modal_feedback, ALERT_SELECTOR);
                    modal_feedback.classList.remove(FEEDBACK_HIDDEN_CLASSNAME);
                    if (isCredentialsFault(fault)) {
                        modal_alert.textContent = gettext_provider.gettext(
                            "Unable to connect to the GitLab server, please check your credentials.",
                        );
                        return;
                    }
                    if (isGitLabFault(fault)) {
                        modal_alert.textContent = sprintf(
                            gettext_provider.gettext(
                                "Unable to reach the GitLab server: %(error)s",
                            ),
                            { error: String(fault) },
                        );
                        return;
                    }
                    modal_alert.textContent = sprintf(
                        gettext_provider.gettext(
                            "Error during the update of the access token: %(error)s",
                        ),
                        { error: String(fault) },
                    );
                },
            );
    };

    return {
        init(): void {
            edit_button.addEventListener("click", onClickEdit);
            token_form.addEventListener("submit", onSubmit);
        },
    };
};

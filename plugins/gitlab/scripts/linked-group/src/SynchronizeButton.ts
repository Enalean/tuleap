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
import { postJSON, uri } from "@tuleap/fetch-result";
import type { GetText } from "@tuleap/gettext";
import { sprintf } from "sprintf-js";
import {
    BADGE_ERROR_CLASSNAME,
    BADGE_SUCCESS_CLASSNAME,
    FEEDBACK_ERROR_CLASSNAME,
    FEEDBACK_SUCCESS_CLASSNAME,
    SPIN_CLASSNAME,
} from "./classnames";

export const SYNCHRONIZE_BUTTON_SELECTOR = "#synchronize-button";
export const SYNCHRONIZE_BUTTON_ICON_SELECTOR = "#synchronize-icon";
export const PAGE_ALERT_SELECTOR = "#linked-group-alert";
export const BADGE_SELECTOR = "#last-sync-badge";

export const FEEDBACK_HIDDEN_CLASSNAME = "gitlab-linked-group-alert-hidden";

type SynchronizationResponse = {
    readonly number_of_integrations: number;
};

type SynchronizeButtonType = {
    init(): void;
};

export const SynchronizeButton = (
    doc: Document,
    gettext_provider: GetText,
    group_id: number,
): SynchronizeButtonType => {
    const button = selectOrThrow(doc, SYNCHRONIZE_BUTTON_SELECTOR, HTMLButtonElement);
    const button_icon = selectOrThrow(button, SYNCHRONIZE_BUTTON_ICON_SELECTOR);
    const feedback = selectOrThrow(doc, PAGE_ALERT_SELECTOR);
    const badge = selectOrThrow(doc, BADGE_SELECTOR);

    const toggleLoadingState = (is_loading: boolean): void => {
        button_icon.classList.toggle(SPIN_CLASSNAME, is_loading);
        button.disabled = is_loading;
    };

    const toggleFeedbackState = (success: boolean, message: string): void => {
        feedback.classList.remove(FEEDBACK_HIDDEN_CLASSNAME);
        feedback.classList.toggle(FEEDBACK_SUCCESS_CLASSNAME, success);
        feedback.classList.toggle(FEEDBACK_ERROR_CLASSNAME, !success);
        feedback.textContent = message;
    };

    const toggleBadgeState = (success: boolean, message: string): void => {
        badge.classList.toggle(BADGE_SUCCESS_CLASSNAME, success);
        badge.classList.toggle(BADGE_ERROR_CLASSNAME, !success);
        if (badge.lastChild) {
            badge.lastChild.textContent = message;
        }
    };

    const onClick = async (): Promise<void> => {
        feedback.classList.add(FEEDBACK_HIDDEN_CLASSNAME);
        toggleLoadingState(true);
        await postJSON<SynchronizationResponse>(
            uri`/api/gitlab_groups/${group_id}/synchronize`,
            undefined,
        ).match(
            (response) => {
                const feedback_message = sprintf(
                    gettext_provider.ngettext(
                        "%d repository has just been integrated.",
                        "%d repositories have just been integrated.",
                        response.number_of_integrations,
                    ),
                    response.number_of_integrations,
                );
                toggleFeedbackState(true, feedback_message);
                const badge_message = sprintf(
                    gettext_provider.ngettext(
                        "%d repository integrated just now",
                        "%d repositories integrated just now",
                        response.number_of_integrations,
                    ),
                    response.number_of_integrations,
                );
                toggleBadgeState(true, badge_message);
            },
            (fault) => {
                const feedback_message = sprintf(
                    gettext_provider.gettext("Error during last sync: %(error)s"),
                    { error: String(fault) },
                );
                toggleFeedbackState(false, feedback_message);
                const badge_message = gettext_provider.gettext("In error, just now");
                toggleBadgeState(false, badge_message);
            },
        );
        toggleLoadingState(false);
    };

    return {
        init(): void {
            button.addEventListener("click", onClick);
        },
    };
};

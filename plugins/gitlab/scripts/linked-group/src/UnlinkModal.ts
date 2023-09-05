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

import { del, uri } from "@tuleap/fetch-result";
import { selectOrThrow } from "@tuleap/dom";
import type { GetText } from "@tuleap/gettext";
import { sprintf } from "sprintf-js";
import { FEEDBACK_HIDDEN_CLASSNAME, HIDDEN_ICON_CLASSNAME } from "./classnames";

const UNLINK_MODAL_SELECTOR = "#unlink-modal";
export const UNLINK_MODAL_FEEDBACK_SELECTOR = "#unlink-modal-feedback";
const UNLINK_MODAL_ALERT_SELECTOR = "#unlink-modal-alert";
export const UNLINK_CONFIRM_SELECTOR = "#unlink-confirm";
export const UNLINK_CONFIRM_ICON_SELECTOR = "#unlink-icon";

type UnlinkModalType = {
    init(): void;
};

export const UnlinkModal = (
    loc: Location,
    doc: Document,
    gettext_provider: GetText,
    group_id: number,
): UnlinkModalType => {
    const unlink_modal = selectOrThrow(doc, UNLINK_MODAL_SELECTOR);
    const modal_feedback = selectOrThrow(unlink_modal, UNLINK_MODAL_FEEDBACK_SELECTOR);
    const confirm_unlink_button = selectOrThrow(
        unlink_modal,
        UNLINK_CONFIRM_SELECTOR,
        HTMLButtonElement,
    );
    const confirm_unlink_icon = selectOrThrow(confirm_unlink_button, UNLINK_CONFIRM_ICON_SELECTOR);

    const toggleLoadingState = (is_loading: boolean): void => {
        confirm_unlink_icon.classList.toggle(HIDDEN_ICON_CLASSNAME, !is_loading);
        confirm_unlink_button.disabled = is_loading;
    };

    const onClick = (): void => {
        modal_feedback.classList.add(FEEDBACK_HIDDEN_CLASSNAME);

        toggleLoadingState(true);
        del(uri`/api/gitlab_groups/${group_id}`).match(
            () => {
                const redirect = new URL(loc.href);
                redirect.searchParams.append("unlink_group", "1");
                loc.replace(redirect);
            },
            (fault) => {
                const alert_block = selectOrThrow(modal_feedback, UNLINK_MODAL_ALERT_SELECTOR);
                modal_feedback.classList.remove(FEEDBACK_HIDDEN_CLASSNAME);
                alert_block.textContent = sprintf(
                    gettext_provider.gettext("Error during the removal of the link: %(error)s"),
                    { error: String(fault) },
                );
                toggleLoadingState(false);
            },
        );
    };

    return {
        init(): void {
            confirm_unlink_button.addEventListener("click", onClick);
        },
    };
};

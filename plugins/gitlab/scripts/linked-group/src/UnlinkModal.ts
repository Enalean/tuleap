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

import { del } from "@tuleap/fetch-result";
import { selectOrThrow, getDatasetItemOrThrow } from "@tuleap/dom";
import type { GetText } from "@tuleap/gettext";
import { sprintf } from "sprintf-js";

const UNLINK_MODAL_SELECTOR = "#unlink-modal";
export const UNLINK_MODAL_FEEDBACK_SELECTOR = "#unlink-modal-feedback";
const UNLINK_MODAL_ALERT_SELECTOR = "#unlink-modal-alert";
export const UNLINK_CONFIRM_SELECTOR = "#unlink-confirm";
export const UNLINK_CONFIRM_ICON_SELECTOR = "#unlink-icon";
export const UNLINK_ICON_CLASSNAME = "fa-link-slash";
export const SPINNER_CLASSNAME = "fa-circle-notch";
export const SPIN_CLASSNAME = "fa-spin";
export const HIDDEN_CLASSNAME = "gitlab-modal-feebdack-hidden";

type UnlinkModalType = {
    init(): void;
};

export const UnlinkModal = (
    loc: Location,
    doc: Document,
    gettext_provider: GetText
): UnlinkModalType => ({
    init(): void {
        const unlink_modal = selectOrThrow(doc, UNLINK_MODAL_SELECTOR);
        const confirm_unlink_button = selectOrThrow(
            unlink_modal,
            UNLINK_CONFIRM_SELECTOR,
            HTMLButtonElement
        );
        const confirm_unlink_icon = selectOrThrow(
            confirm_unlink_button,
            UNLINK_CONFIRM_ICON_SELECTOR
        );

        const startLoading = (): void => {
            confirm_unlink_icon.classList.replace(UNLINK_ICON_CLASSNAME, SPINNER_CLASSNAME);
            confirm_unlink_icon.classList.add(SPIN_CLASSNAME);
            confirm_unlink_button.setAttribute("disabled", "disabled");
        };

        const stopLoading = (): void => {
            confirm_unlink_icon.classList.replace(SPINNER_CLASSNAME, UNLINK_ICON_CLASSNAME);
            confirm_unlink_icon.classList.remove(SPIN_CLASSNAME);
            confirm_unlink_button.removeAttribute("disabled");
        };

        confirm_unlink_button.addEventListener("click", () => {
            const group_id = getDatasetItemOrThrow(confirm_unlink_button, "groupId");

            startLoading();
            del(`/api/gitlab_groups/${group_id}`).match(
                () => loc.reload(),
                (fault) => {
                    const modal_feedback = selectOrThrow(
                        unlink_modal,
                        UNLINK_MODAL_FEEDBACK_SELECTOR
                    );
                    const alert_block = selectOrThrow(modal_feedback, UNLINK_MODAL_ALERT_SELECTOR);
                    modal_feedback.classList.remove(HIDDEN_CLASSNAME);
                    alert_block.textContent = sprintf(
                        gettext_provider.gettext("Error during the removal of the link: %(error)s"),
                        { error: String(fault) }
                    );
                    stopLoading();
                }
            );
        });
    },
});

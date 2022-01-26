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

import { define, html } from "hybrids";
import { getLinkedParentFeedback } from "../../../gettext-catalog";
import { sprintf } from "sprintf-js";
import type { ParentFeedbackPresenter } from "./ParentFeedbackPresenter";
import type { ModalFeedbackController } from "./ModalFeedbackController";
import { buildEmpty } from "./ParentFeedbackPresenter";

export interface ModalFeedback {
    presenter: ParentFeedbackPresenter;
    readonly controller: ModalFeedbackController | undefined;
    content(): HTMLElement;
}
export type HostElement = ModalFeedback & HTMLElement;

export const ModalFeedback = define<ModalFeedback>({
    tag: "modal-feedback",
    controller: {
        set(host, controller: ModalFeedbackController) {
            controller.displayParentFeedback().then((presenter) => (host.presenter = presenter));
        },
    },
    presenter: {
        get: (host, last_value) => last_value ?? buildEmpty(),
        set: (host, presenter) => presenter,
    },
    content: (host) =>
        html`
            ${host.presenter.parent_artifact !== null &&
            html`
                <div class="tlp-modal-feedback">
                    <div class="tlp-alert-info" data-test="parent-feedback">
                        ${sprintf(getLinkedParentFeedback(), host.presenter.parent_artifact.title)}
                    </div>
                </div>
            `}
        `,
});

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
import type { UpdateFunction } from "hybrids";
import { getLinkedParentFeedback } from "../../../gettext-catalog";
import { sprintf } from "sprintf-js";
import { ParentFeedbackPresenter } from "./ParentFeedbackPresenter";
import type { ParentFeedbackControllerType } from "./ParentFeedbackController";
import { FaultFeedbackPresenter } from "./FaultFeedbackPresenter";
import type { FaultFeedbackControllerType } from "./FaultFeedbackController";

export type ModalFeedback = {
    parent_presenter: ParentFeedbackPresenter;
    fault_presenter: FaultFeedbackPresenter;
    readonly parentController: ParentFeedbackControllerType;
    readonly faultController: FaultFeedbackControllerType;
    content(): HTMLElement;
};
export type HostElement = ModalFeedback & HTMLElement;

const displayParentIfNeeded = (
    presenter: ParentFeedbackPresenter
): UpdateFunction<ModalFeedback> => {
    if (presenter.parent_artifact === null) {
        return html``;
    }
    return html`
        <div class="tlp-alert-info" data-test="parent-feedback">
            ${sprintf(getLinkedParentFeedback(), presenter.parent_artifact.title)}
        </div>
    `;
};

const displayFaultIfNeeded = (presenter: FaultFeedbackPresenter): UpdateFunction<ModalFeedback> => {
    if (presenter.message === "") {
        return html``;
    }
    return html`
        <div class="tlp-alert-danger" data-test="fault-feedback">${presenter.message}</div>
    `;
};

const noFeedbackToShow = (host: ModalFeedback): boolean =>
    host.fault_presenter.message === "" && host.parent_presenter.parent_artifact === null;

export const ModalFeedback = define<ModalFeedback>({
    tag: "modal-feedback",
    parentController: {
        set(host, controller: ParentFeedbackControllerType) {
            controller
                .displayParentFeedback()
                .then((presenter) => (host.parent_presenter = presenter));
            return controller;
        },
    },
    faultController: {
        set(host, controller: FaultFeedbackControllerType) {
            controller.registerFaultListener((presenter) => (host.fault_presenter = presenter));
            return controller;
        },
    },
    parent_presenter: {
        get: (host, last_value) => last_value ?? ParentFeedbackPresenter.buildEmpty(),
        set: (host, presenter) => presenter,
    },
    fault_presenter: {
        get: (host, last_value) => last_value ?? FaultFeedbackPresenter.buildEmpty(),
        set: (host, presenter) => presenter,
    },
    content: (host) => {
        if (noFeedbackToShow(host)) {
            return html``;
        }
        return html`
            <div class="tlp-modal-feedback">
                ${displayParentIfNeeded(host.parent_presenter)}
                ${displayFaultIfNeeded(host.fault_presenter)}
            </div>
        `;
    },
});

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
import { sprintf } from "sprintf-js";
import type { Option } from "@tuleap/option";
import { getLinkedParentFeedback } from "../../../gettext-catalog";
import type { ParentArtifact } from "../../../domain/parent/ParentArtifact";
import type { ParentFeedbackControllerType } from "../../../domain/parent/ParentFeedbackController";
import { FaultFeedbackPresenter } from "./FaultFeedbackPresenter";
import type { FaultFeedbackControllerType } from "./FaultFeedbackController";

export type ModalFeedback = {
    parent_option: Option<ParentArtifact>;
    fault_presenter: FaultFeedbackPresenter;
    readonly parentController: ParentFeedbackControllerType;
    readonly faultController: FaultFeedbackControllerType;
    content(): HTMLElement;
};
export type HostElement = ModalFeedback & HTMLElement;

const displayParentIfNeeded = (
    parent_option: Option<ParentArtifact>
): UpdateFunction<ModalFeedback> => {
    return parent_option.mapOr(
        (parent_artifact) =>
            html`<div class="tlp-alert-info" data-test="parent-feedback">
                ${sprintf(getLinkedParentFeedback(), parent_artifact.title)}
            </div>`,
        html``
    );
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
    host.fault_presenter.message === "" && host.parent_option.isNothing();

export const ModalFeedback = define<ModalFeedback>({
    tag: "modal-feedback",
    parentController: {
        set(host, controller: ParentFeedbackControllerType) {
            controller
                .getParentArtifact()
                .then((parent_option) => (host.parent_option = parent_option));
            return controller;
        },
    },
    faultController: {
        set(host, controller: FaultFeedbackControllerType) {
            controller.registerFaultListener((presenter) => (host.fault_presenter = presenter));
            return controller;
        },
    },
    parent_option: undefined,
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
                ${displayParentIfNeeded(host.parent_option)}
                ${displayFaultIfNeeded(host.fault_presenter)}
            </div>
        `;
    },
});

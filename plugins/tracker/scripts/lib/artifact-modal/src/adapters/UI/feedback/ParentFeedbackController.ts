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

import type { Fault } from "@tuleap/fault";
import { ParentFeedbackPresenter } from "./ParentFeedbackPresenter";
import type { ParentArtifactIdentifier } from "../../../domain/parent/ParentArtifactIdentifier";
import type { RetrieveParent } from "../../../domain/parent/RetrieveParent";
import type { NotifyFault } from "../../../domain/NotifyFault";

export type ParentFeedbackControllerType = {
    displayParentFeedback(): Promise<ParentFeedbackPresenter>;
};

const isNoParentFault = (fault: Fault): boolean =>
    typeof fault.hasNoParent === "function" && fault.hasNoParent();

export const ParentFeedbackController = (
    retriever: RetrieveParent,
    fault_notifier: NotifyFault,
    parent_identifier: ParentArtifactIdentifier | null
): ParentFeedbackControllerType => ({
    displayParentFeedback: (): Promise<ParentFeedbackPresenter> => {
        return retriever.getParent(parent_identifier).match(
            (artifact) => ParentFeedbackPresenter.fromArtifact(artifact),
            (fault) => {
                if (!isNoParentFault(fault)) {
                    fault_notifier.onFault(fault);
                }
                return ParentFeedbackPresenter.buildEmpty();
            }
        );
    },
});

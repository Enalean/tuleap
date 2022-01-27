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

import type { RetrieveParent } from "../../../domain/parent/RetrieveParent";
import { ParentFeedbackPresenter } from "./ParentFeedbackPresenter";

export interface ModalFeedbackControllerType {
    displayParentFeedback(): Promise<ParentFeedbackPresenter>;
}

export const ModalFeedbackController = (
    retriever: RetrieveParent,
    parent_artifact_id: number | null
): ModalFeedbackControllerType => {
    return {
        displayParentFeedback: (): Promise<ParentFeedbackPresenter> => {
            if (parent_artifact_id === null) {
                return Promise.resolve(ParentFeedbackPresenter.buildEmpty());
            }
            return retriever
                .retrieveFutureParent(parent_artifact_id)
                .then(ParentFeedbackPresenter.fromArtifact);
        },
    };
};

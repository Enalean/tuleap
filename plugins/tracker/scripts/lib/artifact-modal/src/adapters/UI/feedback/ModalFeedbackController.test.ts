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

import { ModalFeedbackController } from "./ModalFeedbackController";
import type { ParentFeedbackPresenter } from "./ParentFeedbackPresenter";
import type { ParentArtifactIdentifier } from "../../../domain/parent/ParentArtifactIdentifier";
import { ParentArtifactIdentifierStub } from "../../../../tests/stubs/ParentArtifactIdentifierStub";
import { RetrieveArtifactStub } from "../../../../tests/stubs/RetrieveArtifactStub";
import type { Artifact } from "../../../domain/Artifact";
import { ParentRetriever } from "../../../domain/parent/ParentRetriever";

const PARENT_ARTIFACT_ID = 78;

describe(`ModalFeedbackController`, () => {
    let parent_identifier: ParentArtifactIdentifier | null;
    beforeEach(() => {
        parent_identifier = ParentArtifactIdentifierStub.withId(PARENT_ARTIFACT_ID);
    });

    const displayParentFeedback = (): Promise<ParentFeedbackPresenter> => {
        const parent_artifact: Artifact = { id: PARENT_ARTIFACT_ID, title: "nonhereditary" };
        const controller = ModalFeedbackController(
            ParentRetriever(RetrieveArtifactStub.withArtifact(parent_artifact)),
            parent_identifier
        );
        return controller.displayParentFeedback();
    };

    describe(`displayParentFeedback()`, () => {
        it(`when there is a parent, it will return a presenter with it`, async () => {
            const presenter = await displayParentFeedback();

            if (presenter.parent_artifact === null) {
                throw new Error("Expected a parent artifact");
            }
            expect(presenter.parent_artifact.id).toBe(PARENT_ARTIFACT_ID);
        });

        it(`when there is no parent artifact, it will return a presenter without parent`, async () => {
            parent_identifier = null;
            const presenter = await displayParentFeedback();
            expect(presenter.parent_artifact).toBeNull();
        });
    });
});

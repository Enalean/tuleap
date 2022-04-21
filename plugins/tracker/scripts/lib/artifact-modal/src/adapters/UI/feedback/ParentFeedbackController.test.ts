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

import { Fault } from "@tuleap/fault";
import { ParentFeedbackController } from "./ParentFeedbackController";
import type { ParentFeedbackPresenter } from "./ParentFeedbackPresenter";
import type { ParentArtifactIdentifier } from "../../../domain/parent/ParentArtifactIdentifier";
import { ParentArtifactIdentifierStub } from "../../../../tests/stubs/ParentArtifactIdentifierStub";
import { RetrieveParentStub } from "../../../../tests/stubs/RetrieveParentStub";
import type { ParentArtifact } from "../../../domain/parent/ParentArtifact";
import { NotifyFaultStub } from "../../../../tests/stubs/NotifyFaultStub";
import type { RetrieveParent } from "../../../domain/parent/RetrieveParent";

const PARENT_ARTIFACT_ID = 78;
const PARENT_TITLE = "nonhereditary";

describe(`ParentFeedbackController`, () => {
    let fault_notifier: NotifyFaultStub,
        artifact_retriever: RetrieveParent,
        parent_identifier: ParentArtifactIdentifier | null;

    beforeEach(() => {
        fault_notifier = NotifyFaultStub.withCount();
        const parent_artifact: ParentArtifact = { title: PARENT_TITLE };
        artifact_retriever = RetrieveParentStub.withParent(parent_artifact);
        parent_identifier = ParentArtifactIdentifierStub.withId(PARENT_ARTIFACT_ID);
    });

    const displayParentFeedback = (): Promise<ParentFeedbackPresenter> => {
        const controller = ParentFeedbackController(
            artifact_retriever,
            fault_notifier,
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
            expect(presenter.parent_artifact.title).toBe(PARENT_TITLE);
        });

        it(`when there is a problem while retrieving the parent, it will notify that there has been a fault`, async () => {
            const fault = Fault.fromMessage("Could not retrieve parent");
            artifact_retriever = RetrieveParentStub.withFault(fault);

            const presenter = await displayParentFeedback();

            expect(presenter.parent_artifact).toBeNull();
            expect(fault_notifier.getCallCount()).toBe(1);
        });

        it(`when there is no parent artifact,
            it won't notify that there has been a fault
            and it will return a presenter without parent`, async () => {
            parent_identifier = null;
            const presenter = await displayParentFeedback();
            expect(presenter.parent_artifact).toBeNull();
            expect(fault_notifier.getCallCount()).toBe(0);
        });
    });
});

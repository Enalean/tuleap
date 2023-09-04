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
import { Option } from "@tuleap/option";
import { ParentFeedbackController } from "./ParentFeedbackController";
import type { ParentArtifactIdentifier } from "./ParentArtifactIdentifier";
import { ParentArtifactIdentifierStub } from "../../../tests/stubs/ParentArtifactIdentifierStub";
import { RetrieveParentStub } from "../../../tests/stubs/RetrieveParentStub";
import type { ParentArtifact } from "./ParentArtifact";
import type { RetrieveParent } from "./RetrieveParent";
import { DispatchEventsStub } from "../../../tests/stubs/DispatchEventsStub";

const PARENT_ARTIFACT_ID = 78;
const PARENT_TITLE = "nonhereditary";

describe(`ParentFeedbackController`, () => {
    let event_dispatcher: DispatchEventsStub,
        artifact_retriever: RetrieveParent,
        parent_artifact_identifier: Option<ParentArtifactIdentifier>;

    beforeEach(() => {
        event_dispatcher = DispatchEventsStub.withRecordOfEventTypes();
        const parent_artifact: ParentArtifact = { title: PARENT_TITLE };
        artifact_retriever = RetrieveParentStub.withParent(parent_artifact);
        parent_artifact_identifier = Option.fromValue(
            ParentArtifactIdentifierStub.withId(PARENT_ARTIFACT_ID),
        );
    });

    const getParent = (): PromiseLike<Option<ParentArtifact>> => {
        const controller = ParentFeedbackController(
            artifact_retriever,
            event_dispatcher,
            parent_artifact_identifier,
        );
        return controller.getParentArtifact();
    };

    describe(`getParentArtifact()`, () => {
        it(`when there is a parent, it will return it`, async () => {
            const artifact_option = await getParent();
            expect(artifact_option.unwrapOr(null)?.title).toBe(PARENT_TITLE);
        });

        it(`when there is a problem while retrieving the parent,
            it will notify that there has been a fault
            and will return nothing`, async () => {
            const fault = Fault.fromMessage("Could not retrieve parent");
            artifact_retriever = RetrieveParentStub.withFault(fault);

            const artifact_option = await getParent();

            expect(artifact_option.isNothing()).toBe(true);
            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillNotifyFault");
        });

        it(`when there is no parent artifact, it will return nothing`, async () => {
            parent_artifact_identifier = Option.nothing();
            const artifact_option = await getParent();
            expect(artifact_option.isNothing()).toBe(true);
        });
    });
});

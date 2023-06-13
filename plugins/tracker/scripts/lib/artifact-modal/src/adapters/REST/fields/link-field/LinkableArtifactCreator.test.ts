/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import type { ResultAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { LinkableArtifact } from "../../../../domain/fields/link-field/LinkableArtifact";
import { LinkableArtifactCreator } from "./LinkableArtifactCreator";
import type { RetrieveTrackerWithTitleSemantic } from "../../RetrieveTrackerWithTitleSemantic";
import { RetrieveTrackerWithTitleSemanticStub } from "../../../../../tests/stubs/RetrieveTrackerWithTitleSemanticStub";
import { TrackerIdentifierStub } from "../../../../../tests/stubs/TrackerIdentifierStub";
import type { CreateArtifact } from "../../../../domain/submit/CreateArtifact";
import { CreateArtifactStub } from "../../../../../tests/stubs/CreateArtifactStub";

describe(`LinkableArtifactCreator`, () => {
    const TRACKER_ID = 201,
        TITLE = "pseudohallucinatory antheridiophore",
        ARTIFACT_ID = 710;
    let tracker_retriever: RetrieveTrackerWithTitleSemantic, artifact_creator: CreateArtifact;

    beforeEach(() => {
        tracker_retriever = RetrieveTrackerWithTitleSemanticStub.withTracker({
            id: TRACKER_ID,
            semantics: { title: { field_id: 968 } },
        });
        artifact_creator = CreateArtifactStub.withArtifactCreated({ id: ARTIFACT_ID });
    });

    const create = (): ResultAsync<LinkableArtifact, Fault> => {
        const creator = LinkableArtifactCreator(tracker_retriever, artifact_creator);
        return creator.createLinkableArtifact(TrackerIdentifierStub.withId(TRACKER_ID), TITLE);
    };

    it(`creates a LinkableArtifact from a Tracker id and a title`, async () => {
        const result = await create();

        if (!result.isOk()) {
            throw Error("Expected an Ok");
        }
        const artifact = result.value;
        expect(artifact.id).toBe(ARTIFACT_ID);
        expect(artifact.title).toBe(TITLE);
    });

    it(`when there is an error during the retrieval of the tracker, it will return a Fault`, async () => {
        tracker_retriever = RetrieveTrackerWithTitleSemanticStub.withFault(
            Fault.fromMessage("Something happened")
        );
        const result = await create();
        expect(result.isErr()).toBe(true);
    });

    it(`when there is an error during the creation of the artifact, it will return a Fault`, async () => {
        artifact_creator = CreateArtifactStub.withFault(Fault.fromMessage("Permission denied"));
        const result = await create();
        expect(result.isErr()).toBe(true);
    });
});

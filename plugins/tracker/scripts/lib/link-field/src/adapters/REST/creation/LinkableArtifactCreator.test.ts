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

import { beforeEach, describe, expect, it } from "vitest";
import type { ResultAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { LinkableArtifact } from "../../../domain/links/LinkableArtifact";
import { LinkableArtifactCreator } from "./LinkableArtifactCreator";
import type { RetrieveTrackerWithTitleSemantic } from "./RetrieveTrackerWithTitleSemantic";
import { RetrieveTrackerWithTitleSemanticStub } from "../../../../tests/stubs/RetrieveTrackerWithTitleSemanticStub";
import { TrackerIdentifier } from "../../../domain/TrackerIdentifier";
import type { CreateArtifactWithTitle } from "./CreateArtifactWithTitle";
import { CreateArtifactWithTitleStub } from "../../../../tests/stubs/CreateArtifactWithTitleStub";
import type { RetrieveMatchingArtifact } from "../../../domain/RetrieveMatchingArtifact";
import { RetrieveMatchingArtifactStub } from "../../../../tests/stubs/RetrieveMatchingArtifactStub";
import { LinkableArtifactStub } from "../../../../tests/stubs/links/LinkableArtifactStub";
import { ArtifactCreatedIdentifier } from "../../../domain/creation/ArtifactCreatedIdentifier";

describe(`LinkableArtifactCreator`, () => {
    const TRACKER_ID = 201,
        TITLE = "pseudohallucinatory antheridiophore",
        ARTIFACT_ID = 710;
    let tracker_retriever: RetrieveTrackerWithTitleSemantic,
        artifact_creator: CreateArtifactWithTitle,
        artifact_retriever: RetrieveMatchingArtifact;

    beforeEach(() => {
        tracker_retriever = RetrieveTrackerWithTitleSemanticStub.withTracker({
            id: TRACKER_ID,
            semantics: { title: { field_id: 968 } },
        });
        artifact_creator = CreateArtifactWithTitleStub.withArtifactCreated(
            ArtifactCreatedIdentifier.fromId(ARTIFACT_ID),
        );
        artifact_retriever = RetrieveMatchingArtifactStub.withMatchingArtifact(
            LinkableArtifactStub.withDefaults({ id: ARTIFACT_ID, title: TITLE }),
        );
    });

    const create = (): ResultAsync<LinkableArtifact, Fault> => {
        const creator = LinkableArtifactCreator(
            tracker_retriever,
            artifact_creator,
            artifact_retriever,
        );
        return creator.createLinkableArtifact(TrackerIdentifier.fromId(TRACKER_ID), TITLE);
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
            Fault.fromMessage("Something happened"),
        );
        const result = await create();
        expect(result.isErr()).toBe(true);
    });

    it(`when there is an error during the creation of the artifact, it will return a Fault`, async () => {
        artifact_creator = CreateArtifactWithTitleStub.withFault(
            Fault.fromMessage("Permission denied"),
        );
        const result = await create();
        expect(result.isErr()).toBe(true);
    });

    it(`when there is an error during the retrieval of the newly created artifact, it will return a Fault`, async () => {
        artifact_retriever = RetrieveMatchingArtifactStub.withFault(
            Fault.fromMessage("Something happened"),
        );
        const result = await create();
        expect(result.isErr()).toBe(true);
    });
});

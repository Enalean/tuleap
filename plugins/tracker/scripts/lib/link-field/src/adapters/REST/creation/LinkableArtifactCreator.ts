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
import type { Fault } from "@tuleap/fault";
import type { CreateLinkableArtifact } from "../../../domain/creation/CreateLinkableArtifact";
import type { LinkableArtifact } from "../../../domain/links/LinkableArtifact";
import type { RetrieveTrackerWithTitleSemantic } from "./RetrieveTrackerWithTitleSemantic";
import type { CreateArtifactWithTitle } from "./CreateArtifactWithTitle";
import type { RetrieveMatchingArtifact } from "../../../domain/RetrieveMatchingArtifact";
import { LinkableNumber } from "../../../domain/links/LinkableNumber";
import { TitleFieldIdentifier } from "../../../domain/creation/TitleFieldIdentifier";

export const LinkableArtifactCreator = (
    tracker_retriever: RetrieveTrackerWithTitleSemantic,
    artifact_creator: CreateArtifactWithTitle,
    artifact_retriever: RetrieveMatchingArtifact,
): CreateLinkableArtifact => ({
    createLinkableArtifact: (tracker_identifier, title): ResultAsync<LinkableArtifact, Fault> =>
        tracker_retriever
            .getTrackerWithTitleSemantic(tracker_identifier)
            .andThen((tracker) =>
                artifact_creator.createArtifactWithTitle(
                    tracker_identifier,
                    TitleFieldIdentifier.fromId(tracker.semantics.title.field_id),
                    title,
                ),
            )
            .andThen((artifact) =>
                artifact_retriever.getMatchingArtifact(
                    LinkableNumber.fromArtifactCreated(artifact),
                ),
            ),
});

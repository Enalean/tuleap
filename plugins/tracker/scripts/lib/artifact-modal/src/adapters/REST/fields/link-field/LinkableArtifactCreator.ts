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
import type { CreateLinkableArtifact } from "../../../../domain/fields/link-field/creation/CreateLinkableArtifact";
import type { LinkableArtifact } from "../../../../domain/fields/link-field/LinkableArtifact";
import type { RetrieveTrackerWithTitleSemantic } from "../../RetrieveTrackerWithTitleSemantic";
import type { CreateArtifact } from "../../../../domain/submit/CreateArtifact";
import type { RetrieveMatchingArtifact } from "../../../../domain/fields/link-field/RetrieveMatchingArtifact";
import { LinkableNumber } from "../../../../domain/fields/link-field/LinkableNumber";

export const LinkableArtifactCreator = (
    tracker_retriever: RetrieveTrackerWithTitleSemantic,
    artifact_creator: CreateArtifact,
    artifact_retriever: RetrieveMatchingArtifact
): CreateLinkableArtifact => ({
    createLinkableArtifact: (tracker_identifier, title): ResultAsync<LinkableArtifact, Fault> =>
        tracker_retriever
            .getTrackerWithTitleSemantic(tracker_identifier)
            .andThen((tracker) => {
                const title_field_id = tracker.semantics.title.field_id;
                return artifact_creator.createArtifact(tracker_identifier, [
                    { field_id: title_field_id, value: title },
                ]);
            })
            .andThen((artifact) =>
                artifact_retriever.getMatchingArtifact(LinkableNumber.fromArtifactCreated(artifact))
            ),
});

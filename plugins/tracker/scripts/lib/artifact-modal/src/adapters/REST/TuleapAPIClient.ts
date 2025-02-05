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

import { getAllJSON, getJSON, postFormWithTextResponse, postJSON, uri } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import type {
    ArtifactCreationPayload,
    ChangesetWithCommentRepresentation,
    JustCreatedArtifactResponse,
    PostFileResponse,
} from "@tuleap/plugin-tracker-rest-api-types";
import type {
    CurrentProjectIdentifier,
    ParentArtifactIdentifier,
} from "@tuleap/plugin-tracker-artifact-common";
import type { RetrieveParent } from "../../domain/parent/RetrieveParent";
import type { ParentArtifact } from "../../domain/parent/ParentArtifact";
import { ParentRetrievalFault } from "../../domain/parent/ParentRetrievalFault";
import type { CreateFileUpload } from "../../domain/fields/file-field/CreateFileUpload";
import type { FileUploadCreated } from "../../domain/fields/file-field/FileUploadCreated";
import type { RetrieveComments } from "../../domain/comments/RetrieveComments";
import type { FollowUpComment } from "../../domain/comments/FollowUpComment";
import { FollowUpCommentProxy } from "./comments/FollowUpCommentProxy";
import type { CreateArtifact } from "../../domain/submit/CreateArtifact";
import type { ArtifactCreated } from "../../domain/ArtifactCreated";
import { ArtifactCreationFault } from "../../domain/ArtifactCreationFault";
import type { InterpretCommonMark } from "../../domain/common/InterpretCommonMark";

type TuleapAPIClientType = RetrieveParent &
    CreateFileUpload &
    RetrieveComments &
    CreateArtifact &
    InterpretCommonMark;

export const TuleapAPIClient = (
    current_project_identifier: CurrentProjectIdentifier,
): TuleapAPIClientType => ({
    getParent: (artifact_id: ParentArtifactIdentifier): ResultAsync<ParentArtifact, Fault> =>
        getJSON<ParentArtifact>(uri`/api/v1/artifacts/${artifact_id.id}`).mapErr(
            ParentRetrievalFault,
        ),

    createFileUpload(file): ResultAsync<FileUploadCreated, Fault> {
        return postJSON<PostFileResponse>(uri`/api/v1/tracker_fields/${file.file_field_id}/files`, {
            name: file.file_handle.name,
            file_size: file.file_handle.size,
            file_type: file.file_type,
            description: file.description,
        }).map(
            (response): FileUploadCreated => ({
                file_id: response.id,
                upload_href: response.upload_href,
            }),
        );
    },

    getComments(artifact_id, is_order_inverted): ResultAsync<readonly FollowUpComment[], Fault> {
        return getAllJSON<ChangesetWithCommentRepresentation>(
            uri`/api/v1/artifacts/${artifact_id.id}/changesets`,
            { params: { limit: 50, fields: "comments", order: "asc" } },
        ).map((comments) => {
            const sorted_comments = is_order_inverted ? Array.from(comments).reverse() : comments;
            return sorted_comments.map(FollowUpCommentProxy.fromRepresentation);
        });
    },

    createArtifact(
        current_tracker_identifier,
        changeset_values,
    ): ResultAsync<ArtifactCreated, Fault> {
        const payload: ArtifactCreationPayload = {
            tracker: { id: current_tracker_identifier.id },
            values: changeset_values,
        };
        return postJSON<JustCreatedArtifactResponse>(uri`/api/v1/artifacts`, payload).mapErr(
            ArtifactCreationFault,
        );
    },

    interpretCommonMark(commonmark): ResultAsync<string, Fault> {
        const form_data = new FormData();
        form_data.set("content", commonmark);
        return postFormWithTextResponse(
            uri`/project/${current_project_identifier.id}/interpret-commonmark`,
            form_data,
        );
    },
});

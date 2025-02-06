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
import type { ResultAsync } from "neverthrow";
import { okAsync } from "neverthrow";
import * as fetch_result from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import type {
    ArtifactCreationPayload,
    ChangesetWithCommentRepresentation,
} from "@tuleap/plugin-tracker-rest-api-types";
import {
    CurrentArtifactIdentifier,
    CurrentProjectIdentifier,
    CurrentTrackerIdentifier,
    ParentArtifactIdentifier,
} from "@tuleap/plugin-tracker-artifact-common";
import { TuleapAPIClient } from "./TuleapAPIClient";
import type { ParentArtifact } from "../../domain/parent/ParentArtifact";
import type { FileUploadCreated } from "../../domain/fields/file-field/FileUploadCreated";
import type { NewFileUpload } from "../../domain/fields/file-field/NewFileUpload";
import type { FollowUpComment } from "../../domain/comments/FollowUpComment";
import { ChangesetWithCommentRepresentationBuilder } from "../../../tests/builders/ChangesetWithCommentRepresentationBuilder";
import type { ArtifactCreated } from "../../domain/ArtifactCreated";
import type { ChangesetValues } from "../../domain/submit/ChangesetValues";

const PROJECT_ID = 179;

describe(`TuleapAPIClient`, () => {
    const getClient = (): ReturnType<typeof TuleapAPIClient> =>
        TuleapAPIClient(CurrentProjectIdentifier.fromId(PROJECT_ID));

    describe(`getParent()`, () => {
        const ARTIFACT_ID = 90,
            ARTIFACT_TITLE = "thio";

        const getParent = (): ResultAsync<ParentArtifact, Fault> => {
            return getClient().getParent(ParentArtifactIdentifier.fromId(ARTIFACT_ID));
        };

        it(`will return the parent artifact matching the given id`, async () => {
            const artifact: ParentArtifact = { title: ARTIFACT_TITLE };
            const getJSON = jest.spyOn(fetch_result, "getJSON");
            getJSON.mockReturnValue(okAsync(artifact));

            const result = await getParent();

            expect(result.unwrapOr(null)).toBe(artifact);
            expect(getJSON.mock.calls[0][0]).toStrictEqual(uri`/api/v1/artifacts/${ARTIFACT_ID}`);
        });
    });

    describe(`createFileUpload()`, () => {
        const FILE_FIELD_ID = 380,
            FILE_ID = 886,
            UPLOAD_HREF = `/uploads/tracker/file/${FILE_ID}`,
            FILE_NAME = "edestan_bretelle.zip",
            FILE_SIZE = 1579343,
            FILE_TYPE = "application/zip",
            DESCRIPTION = "protestive visceration";

        const createFileUpload = (): ResultAsync<FileUploadCreated, Fault> => {
            const new_file_upload: NewFileUpload = {
                file_field_id: FILE_FIELD_ID,
                file_type: FILE_TYPE,
                description: DESCRIPTION,
                file_handle: { name: FILE_NAME, size: FILE_SIZE } as File,
            };

            return getClient().createFileUpload(new_file_upload);
        };

        it(`will create a new file upload to be completed with TUS`, async () => {
            const postJSON = jest.spyOn(fetch_result, "postJSON");
            postJSON.mockReturnValue(
                okAsync({
                    id: FILE_ID,
                    upload_href: UPLOAD_HREF,
                }),
            );

            const result = await createFileUpload();

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }
            expect(result.value.file_id).toBe(FILE_ID);
            expect(result.value.upload_href).toBe(UPLOAD_HREF);
            const first_call_arguments = postJSON.mock.calls[0];
            expect(first_call_arguments[0]).toStrictEqual(
                uri`/api/v1/tracker_fields/${FILE_FIELD_ID}/files`,
            );
            expect(first_call_arguments[1]).toStrictEqual({
                name: FILE_NAME,
                file_size: FILE_SIZE,
                file_type: FILE_TYPE,
                description: DESCRIPTION,
            });
        });
    });

    describe(`getComments()`, () => {
        const FIRST_COMMENT_BODY = "<p>An HTML comment</p>",
            SECOND_COMMENT_BODY = "Plain text comment",
            ARTIFACT_ID = 70;
        let is_order_inverted: boolean,
            first_comment: ChangesetWithCommentRepresentation,
            second_comment: ChangesetWithCommentRepresentation;

        beforeEach(() => {
            is_order_inverted = false;
            first_comment = ChangesetWithCommentRepresentationBuilder.aComment(100)
                .withPostProcessedBody(FIRST_COMMENT_BODY, "html")
                .build();
            second_comment = ChangesetWithCommentRepresentationBuilder.aComment(101)
                .withPostProcessedBody(SECOND_COMMENT_BODY, "text")
                .build();
        });

        const getComments = (): ResultAsync<readonly FollowUpComment[], Fault> => {
            return getClient().getComments(
                CurrentArtifactIdentifier.fromId(ARTIFACT_ID),
                is_order_inverted,
            );
        };

        it(`will return an array of comments`, async () => {
            const getAllSpy = jest
                .spyOn(fetch_result, "getAllJSON")
                .mockReturnValue(okAsync([first_comment, second_comment]));

            const result = await getComments();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toHaveLength(2);
            const [first_returned_comment, second_returned_comment] = result.value;
            expect(first_returned_comment.body).toBe(FIRST_COMMENT_BODY);
            expect(second_returned_comment.body).toBe(SECOND_COMMENT_BODY);

            expect(getAllSpy).toHaveBeenCalledWith(
                uri`/api/v1/artifacts/${ARTIFACT_ID}/changesets`,
                { params: { limit: 50, fields: "comments", order: "asc" } },
            );
        });

        it(`when the order of comments is inverted, it will still pass "asc"
            and reverse in-memory the order of comments`, async () => {
            is_order_inverted = true;

            const getAllSpy = jest
                .spyOn(fetch_result, "getAllJSON")
                .mockReturnValue(okAsync([first_comment, second_comment]));

            const result = await getComments();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toHaveLength(2);
            const [first_returned_comment, second_returned_comment] = result.value;
            expect(first_returned_comment.body).toBe(SECOND_COMMENT_BODY);
            expect(second_returned_comment.body).toBe(FIRST_COMMENT_BODY);

            expect(getAllSpy).toHaveBeenCalledWith(expect.anything(), {
                params: expect.objectContaining({ order: "asc" }),
            });
        });
    });

    describe(`createArtifact()`, () => {
        const TRACKER_ID = 22,
            FIELD_ID = 318;

        const createArtifact = (): ResultAsync<ArtifactCreated, Fault> => {
            const changeset_values: ChangesetValues = [
                { field_id: FIELD_ID, value: "genetic doctrinarianism" },
            ];

            return getClient().createArtifact(
                CurrentTrackerIdentifier.fromId(TRACKER_ID),
                changeset_values,
            );
        };

        it(`will create a new artifact`, async () => {
            const new_artifact_id = 263;
            const postJSON = jest
                .spyOn(fetch_result, "postJSON")
                .mockReturnValue(okAsync({ id: new_artifact_id }));

            const result = await createArtifact();
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }

            expect(result.value.id).toBe(new_artifact_id);
            const call_uri = postJSON.mock.calls[0][0];
            const body = postJSON.mock.calls[0][1] as ArtifactCreationPayload;
            expect(call_uri).toStrictEqual(uri`/api/v1/artifacts`);
            expect(body.tracker.id).toBe(TRACKER_ID);
            expect(body.values.map((changeset) => changeset.field_id)).toContain(FIELD_ID);
        });
    });

    describe(`interpretCommonMark()`, () => {
        const MARKDOWN_STRING = `**Markdown** content`;
        const HTML_STRING = `<p><strong>Markdown</strong> content</p>`;
        const interpretCuiller = (): ResultAsync<string, Fault> => {
            return getClient().interpretCommonMark(MARKDOWN_STRING);
        };

        it(`will return the HTML string resulting from interpretation of the given CommonMark`, async () => {
            const postSpy = jest
                .spyOn(fetch_result, "postFormWithTextResponse")
                .mockReturnValue(okAsync(HTML_STRING));

            const result = await interpretCuiller();
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toBe(HTML_STRING);
            expect(postSpy.mock.calls[0][0]).toStrictEqual(
                uri`/project/${PROJECT_ID}/interpret-commonmark`,
            );
            const request_body = postSpy.mock.calls[0][1];
            expect(request_body.get("content")).toBe(MARKDOWN_STRING);
        });
    });
});

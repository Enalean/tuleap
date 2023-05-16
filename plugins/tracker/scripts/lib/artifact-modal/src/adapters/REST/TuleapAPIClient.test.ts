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
import type { GetAllOptions } from "@tuleap/fetch-result";
import { ARTIFACT_TYPE } from "@tuleap/core-rest-api-types";
import type { ProjectResponse } from "@tuleap/core-rest-api-types";
import { Option } from "@tuleap/option";
import type { LinkedArtifactCollection } from "./TuleapAPIClient";
import { TuleapAPIClient } from "./TuleapAPIClient";
import type { LinkedArtifact } from "../../domain/fields/link-field/LinkedArtifact";
import type { ParentArtifact } from "../../domain/parent/ParentArtifact";
import { CurrentArtifactIdentifierStub } from "../../../tests/stubs/CurrentArtifactIdentifierStub";
import { ParentArtifactIdentifierStub } from "../../../tests/stubs/ParentArtifactIdentifierStub";
import type { LinkableArtifact } from "../../domain/fields/link-field/LinkableArtifact";
import { LinkableNumberStub } from "../../../tests/stubs/LinkableNumberStub";
import type { ArtifactWithStatus } from "./ArtifactWithStatus";
import type { LinkType } from "../../domain/fields/link-field/LinkType";
import { LinkTypeStub } from "../../../tests/stubs/LinkTypeStub";
import { CurrentTrackerIdentifierStub } from "../../../tests/stubs/CurrentTrackerIdentifierStub";
import type { FileUploadCreated } from "../../domain/fields/file-field/FileUploadCreated";
import type { NewFileUpload } from "../../domain/fields/file-field/NewFileUpload";
import { UserIdentifierStub } from "../../../tests/stubs/UserIdentifierStub";
import type { FollowUpComment } from "../../domain/comments/FollowUpComment";
import { ChangesetWithCommentRepresentationBuilder } from "../../../tests/builders/ChangesetWithCommentRepresentationBuilder";
import type { CurrentArtifactIdentifier } from "../../domain/CurrentArtifactIdentifier";
import type { Project } from "../../domain/Project";

const FORWARD_DIRECTION = "forward";
const IS_CHILD_SHORTNAME = "_is_child";
const ARTIFACT_ID = 90;
const ARTIFACT_2_ID = 10;
const FIRST_LINKED_ARTIFACT_ID = 40;
const SECOND_LINKED_ARTIFACT_ID = 60;
const ARTIFACT_TITLE = "thio";
const ARTIFACT_XREF = `story #${ARTIFACT_ID}`;
const COLOR = "deep-blue";
const TRACKER_ID = 36;
const PROJECT = { id: 179, label: "Guinea Pig", icon: "🐹" };

describe(`TuleapAPIClient`, () => {
    let current_artifact_option: Option<CurrentArtifactIdentifier>;

    beforeEach(() => {
        current_artifact_option = Option.nothing();
    });

    describe(`getParent()`, () => {
        const getParent = (): ResultAsync<ParentArtifact, Fault> => {
            const client = TuleapAPIClient(current_artifact_option);
            return client.getParent(ParentArtifactIdentifierStub.withId(ARTIFACT_ID));
        };

        it(`will return the parent artifact matching the given id`, async () => {
            const artifact: ParentArtifact = { title: ARTIFACT_TITLE };
            const getSpy = jest.spyOn(fetch_result, "getJSON");
            getSpy.mockReturnValue(okAsync(artifact));

            const result = await getParent();

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }
            expect(result.value).toBe(artifact);
        });
    });

    describe(`getMatchingArtifact()`, () => {
        const getMatching = (): ResultAsync<LinkableArtifact, Fault> => {
            const client = TuleapAPIClient(current_artifact_option);
            return client.getMatchingArtifact(LinkableNumberStub.withId(ARTIFACT_ID));
        };

        it(`will return a Linkable Artifact matching the given number`, async () => {
            const artifact = {
                id: ARTIFACT_ID,
                title: ARTIFACT_TITLE,
                xref: ARTIFACT_XREF,
                tracker: { color_name: COLOR, project: PROJECT },
            } as ArtifactWithStatus;
            const getSpy = jest.spyOn(fetch_result, "getJSON");
            getSpy.mockReturnValue(okAsync(artifact));

            const result = await getMatching();

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }
            expect(getSpy.mock.calls[0][0]).toStrictEqual(uri`/api/v1/artifacts/${ARTIFACT_ID}`);
            const linkable_artifact = result.value;
            expect(linkable_artifact.id).toBe(ARTIFACT_ID);
            expect(linkable_artifact.title).toBe(ARTIFACT_TITLE);
            expect(linkable_artifact.xref.ref).toBe(ARTIFACT_XREF);
            expect(linkable_artifact.xref.color).toBe(COLOR);
        });
    });

    describe(`getAllLinkTypes()`, () => {
        const getAllLinkTypes = (): ResultAsync<readonly LinkType[], Fault> => {
            const client = TuleapAPIClient(current_artifact_option);
            return client.getAllLinkTypes(CurrentArtifactIdentifierStub.withId(ARTIFACT_ID));
        };

        it(`will return an array of link types`, async () => {
            const parent_type = {
                shortname: "_is_child",
                direction: "forward",
                label: "Parent",
            };
            const child_type = {
                shortname: "_is_child",
                direction: "reverse",
                label: "Child",
            };

            const getSpy = jest.spyOn(fetch_result, "getJSON");
            getSpy.mockReturnValue(okAsync({ natures: [child_type, parent_type] }));

            const result = await getAllLinkTypes();

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }
            const types = result.value;
            expect(types).toHaveLength(2);
            expect(types).toContain(parent_type);
            expect(types).toContain(child_type);
            expect(getSpy.mock.calls[0][0]).toStrictEqual(
                uri`/api/v1/artifacts/${ARTIFACT_ID}/links`
            );
        });
    });

    describe(`getLinkedArtifactsByLinkType()`, () => {
        let link_type: LinkType;

        beforeEach(() => {
            link_type = LinkTypeStub.buildParentLinkType();
        });

        const getLinkedArtifactsByLinkType = (): ResultAsync<readonly LinkedArtifact[], Fault> => {
            const client = TuleapAPIClient(current_artifact_option);
            return client.getLinkedArtifactsByLinkType(
                CurrentArtifactIdentifierStub.withId(ARTIFACT_ID),
                link_type
            );
        };

        it(`will return an array of linked artifacts`, async () => {
            const first_artifact = {
                id: FIRST_LINKED_ARTIFACT_ID,
                tracker: { color_name: "army-green" },
            } as ArtifactWithStatus;
            const second_artifact = {
                id: SECOND_LINKED_ARTIFACT_ID,
                tracker: { color_name: "chrome-silver" },
            } as ArtifactWithStatus;

            const getAllSpy = jest.spyOn(fetch_result, "getAllJSON");
            getMockLinkedArtifactsRetrieval(getAllSpy, {
                collection: [first_artifact, second_artifact],
            });

            const result = await getLinkedArtifactsByLinkType();

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }
            expect(result.value).toHaveLength(2);
            const [first_returned_artifact, second_returned_artifact] = result.value;
            expect(first_returned_artifact.identifier.id).toBe(FIRST_LINKED_ARTIFACT_ID);
            expect(first_returned_artifact.link_type).toBe(link_type);
            expect(second_returned_artifact.identifier.id).toBe(SECOND_LINKED_ARTIFACT_ID);
            expect(second_returned_artifact.link_type).toBe(link_type);
            expect(getAllSpy.mock.calls[0]).toEqual([
                uri`/api/v1/artifacts/${ARTIFACT_ID}/linked_artifacts`,
                {
                    params: {
                        limit: 50,
                        offset: 0,
                        direction: FORWARD_DIRECTION,
                        nature: IS_CHILD_SHORTNAME,
                    },
                    getCollectionCallback: expect.any(Function),
                },
            ]);
        });
    });

    describe(`getPossibleParents()`, () => {
        const getPossibleParents = (): ResultAsync<readonly LinkableArtifact[], Fault> => {
            const client = TuleapAPIClient(current_artifact_option);
            return client.getPossibleParents(CurrentTrackerIdentifierStub.withId(TRACKER_ID));
        };

        it(`will return an array of linkable artifacts`, async () => {
            const first_artifact = {
                id: FIRST_LINKED_ARTIFACT_ID,
                tracker: { color_name: "chrome-silver", project: PROJECT },
            } as ArtifactWithStatus;
            const second_artifact = {
                id: SECOND_LINKED_ARTIFACT_ID,
                tracker: { color_name: "coral-pink", project: PROJECT },
            } as ArtifactWithStatus;
            const getAllSpy = jest.spyOn(fetch_result, "getAllJSON");
            getAllSpy.mockReturnValue(okAsync([first_artifact, second_artifact]));

            const result = await getPossibleParents();

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }
            expect(result.value).toHaveLength(2);
            const [first_returned_artifact, second_returned_artifact] = result.value;
            expect(first_returned_artifact.id).toBe(FIRST_LINKED_ARTIFACT_ID);
            expect(second_returned_artifact.id).toBe(SECOND_LINKED_ARTIFACT_ID);
            expect(getAllSpy).toHaveBeenCalledWith(
                uri`/api/v1/trackers/${TRACKER_ID}/parent_artifacts`,
                { params: { limit: 1000 } }
            );
        });
    });

    describe(`createFileUpload()`, () => {
        const FILE_FIELD_ID = 380;
        const FILE_ID = 886;
        const UPLOAD_HREF = `/uploads/tracker/file/${FILE_ID}`;
        const FILE_NAME = "edestan_bretelle.zip";
        const FILE_SIZE = 1579343;
        const FILE_TYPE = "application/zip";
        const DESCRIPTION = "protestive visceration";

        const createFileUpload = (): ResultAsync<FileUploadCreated, Fault> => {
            const new_file_upload: NewFileUpload = {
                file_field_id: FILE_FIELD_ID,
                file_type: FILE_TYPE,
                description: DESCRIPTION,
                file_handle: { name: FILE_NAME, size: FILE_SIZE } as File,
            };

            const client = TuleapAPIClient(current_artifact_option);
            return client.createFileUpload(new_file_upload);
        };

        it(`will create a new file upload to be completed with TUS`, async () => {
            const postJSON = jest.spyOn(fetch_result, "postJSON");
            postJSON.mockReturnValue(
                okAsync({
                    id: FILE_ID,
                    upload_href: UPLOAD_HREF,
                })
            );

            const result = await createFileUpload();

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }
            expect(result.value.file_id).toBe(FILE_ID);
            expect(result.value.upload_href).toBe(UPLOAD_HREF);
            const first_call_arguments = postJSON.mock.calls[0];
            expect(first_call_arguments[0]).toStrictEqual(
                uri`/api/v1/tracker_fields/${FILE_FIELD_ID}/files`
            );
            expect(first_call_arguments[1]).toStrictEqual({
                name: FILE_NAME,
                file_size: FILE_SIZE,
                file_type: FILE_TYPE,
                description: DESCRIPTION,
            });
        });
    });

    describe("getUserArtifactHistory()", () => {
        const USER_ID = 102;
        const getUserArtifactHistory = (): ResultAsync<readonly LinkableArtifact[], Fault> => {
            const client = TuleapAPIClient(current_artifact_option);
            return client.getUserArtifactHistory(UserIdentifierStub.fromUserId(USER_ID));
        };
        it(`will return user history entries which are "artifact" type as linkable artifact`, async () => {
            const first_entry = { per_type_id: ARTIFACT_ID, type: ARTIFACT_TYPE, badges: [] };
            const second_entry = { per_type_id: ARTIFACT_2_ID, type: ARTIFACT_TYPE, badges: [] };
            const third_entry = { per_type_id: 1158, type: "kanban", badges: [] };

            const getSpy = jest.spyOn(fetch_result, "getJSON");
            getSpy.mockReturnValue(okAsync({ entries: [first_entry, second_entry, third_entry] }));

            const result = await getUserArtifactHistory();

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }
            expect(result.value).toHaveLength(2);
            const [first_returned_artifact, second_returned_artifact] = result.value;
            expect(first_returned_artifact.id).toBe(ARTIFACT_ID);
            expect(second_returned_artifact.id).toBe(ARTIFACT_2_ID);
            expect(getSpy).toHaveBeenCalledWith(uri`/api/v1/users/${USER_ID}/history`);
        });
    });

    describe(`searchArtifacts()`, () => {
        const SEARCH_QUERY = "bookwright";

        const searchArtifacts = (): ResultAsync<readonly LinkableArtifact[], Fault> => {
            const client = TuleapAPIClient(current_artifact_option);
            return client.searchArtifacts(SEARCH_QUERY);
        };

        it(`will return search results with type "artifact" as linkable artifacts`, async () => {
            const first_entry = { per_type_id: ARTIFACT_ID, type: ARTIFACT_TYPE, badges: [] };
            const second_entry = { per_type_id: ARTIFACT_2_ID, type: ARTIFACT_TYPE, badges: [] };
            const third_entry = { per_type_id: 84, type: "kanban", badges: [] };

            const postSpy = jest
                .spyOn(fetch_result, "postJSON")
                .mockReturnValue(okAsync([first_entry, second_entry, third_entry]));

            const result = await searchArtifacts();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toHaveLength(2);
            const [first_artifact, second_artifact] = result.value;
            expect(first_artifact.id).toBe(ARTIFACT_ID);
            expect(second_artifact.id).toBe(ARTIFACT_2_ID);
            expect(postSpy).toHaveBeenCalledWith(uri`/api/search?limit=50`, {
                keywords: SEARCH_QUERY,
            });
        });
    });

    describe(`getComments()`, () => {
        const FIRST_COMMENT_BODY = "<p>An HTML comment</p>",
            SECOND_COMMENT_BODY = "Plain text comment";
        let is_order_inverted: boolean;

        beforeEach(() => {
            is_order_inverted = true;
        });

        const getComments = (): ResultAsync<readonly FollowUpComment[], Fault> => {
            const client = TuleapAPIClient(current_artifact_option);
            return client.getComments(
                CurrentArtifactIdentifierStub.withId(ARTIFACT_ID),
                is_order_inverted
            );
        };

        it(`will return an array of comments`, async () => {
            const first_comment = ChangesetWithCommentRepresentationBuilder.aComment(100)
                .withPostProcessedBody(FIRST_COMMENT_BODY, "html")
                .build();
            const second_comment = ChangesetWithCommentRepresentationBuilder.aComment(101)
                .withPostProcessedBody(SECOND_COMMENT_BODY, "text")
                .build();

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
                {
                    params: {
                        limit: 50,
                        fields: "comments",
                        order: "desc",
                    },
                }
            );
        });

        it(`will pass "asc" order when the order of comments is inverted`, async () => {
            is_order_inverted = false;

            const getAllSpy = jest.spyOn(fetch_result, "getAllJSON").mockReturnValue(okAsync([]));

            await getComments();

            expect(getAllSpy).toHaveBeenCalledWith(expect.anything(), {
                params: expect.objectContaining({ order: "asc" }),
            });
        });
    });

    describe(`getProjects()`, () => {
        const FIRST_PROJECT_ID = 113,
            SECOND_PROJECT_ID = 161,
            FIRST_PROJECT_LABEL = "🐷 Guinea Pig",
            SECOND_PROJECT_LABEL = "Hidden Street";

        const getProjects = (): ResultAsync<readonly Project[], Fault> => {
            const client = TuleapAPIClient(current_artifact_option);
            return client.getProjects();
        };

        it(`will return an array of Projects`, async () => {
            const first_project = {
                id: FIRST_PROJECT_ID,
                label: FIRST_PROJECT_LABEL,
            } as ProjectResponse;
            const second_project = {
                id: SECOND_PROJECT_ID,
                label: SECOND_PROJECT_LABEL,
            } as ProjectResponse;
            const getAllJSON = jest
                .spyOn(fetch_result, "getAllJSON")
                .mockReturnValue(okAsync([first_project, second_project]));

            const result = await getProjects();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toHaveLength(2);
            const [first_returned_project, second_returned_project] = result.value;
            expect(first_returned_project.id).toBe(FIRST_PROJECT_ID);
            expect(first_returned_project.label).toBe(FIRST_PROJECT_LABEL);
            expect(second_returned_project.id).toBe(SECOND_PROJECT_ID);
            expect(second_returned_project.label).toBe(SECOND_PROJECT_LABEL);
            expect(getAllJSON).toHaveBeenCalledWith(uri`/api/projects`, { params: { limit: 50 } });
        });
    });
});

function getMockLinkedArtifactsRetrieval(
    recursiveGetSpy: jest.SpyInstance,
    linked_artifacts: LinkedArtifactCollection
): void {
    recursiveGetSpy.mockImplementation(
        <TypeOfLinkedArtifact>(
            url: string,
            options?: GetAllOptions<LinkedArtifactCollection, TypeOfLinkedArtifact>
        ): ResultAsync<readonly TypeOfLinkedArtifact[], Fault> => {
            if (!options || !options.getCollectionCallback) {
                throw new Error("Unexpected options for getAllJSON");
            }
            return okAsync(options.getCollectionCallback(linked_artifacts));
        }
    );
}

/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { okAsync, type ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { GetAllOptions } from "@tuleap/fetch-result";
import * as fetch_result from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import { Option } from "@tuleap/option";
import type { ColorName } from "@tuleap/plugin-tracker-constants";
import {
    ARTIFACT_TYPE,
    FORWARD_DIRECTION,
    IS_CHILD_LINK_TYPE,
} from "@tuleap/plugin-tracker-constants";
import {
    CurrentArtifactIdentifier,
    CurrentTrackerIdentifier,
} from "@tuleap/plugin-tracker-artifact-common";
import type { LinkableArtifact } from "../../domain/links/LinkableArtifact";
import type { ArtifactWithStatus } from "./ArtifactWithStatus";
import type { LinkedArtifactCollection } from "./LinkFieldAPIClient";
import { LinkFieldAPIClient } from "./LinkFieldAPIClient";
import { LinkableNumberStub } from "../../../tests/stubs/links/LinkableNumberStub";
import type { LinkType } from "../../domain/links/LinkType";
import { LinkTypeStub } from "../../../tests/stubs/links/LinkTypeStub";
import type { LinkedArtifact } from "../../domain/links/LinkedArtifact";
import { ProjectStub } from "../../../tests/stubs/ProjectStub";
import { UserIdentifier } from "../../domain/UserIdentifier";

describe(`LinkFieldAPIClient`, () => {
    const PROJECT = { id: 102, label: "Eternal Ray", icon: "🌅" };

    const getClient = (): LinkFieldAPIClient => {
        const current_artifact_option: Option<CurrentArtifactIdentifier> = Option.nothing();
        return LinkFieldAPIClient(current_artifact_option);
    };

    describe(`getAllLinkTypes()`, () => {
        const ARTIFACT_ID = 350;

        const getAllLinkTypes = (): ResultAsync<readonly LinkType[], Fault> => {
            return getClient().getAllLinkTypes(CurrentArtifactIdentifier.fromId(ARTIFACT_ID));
        };

        it(`will return an array of link types`, async () => {
            const parent_type: LinkType = {
                shortname: "_is_child",
                direction: "forward",
                label: "Parent",
            };
            const child_type: LinkType = {
                shortname: "_is_child",
                direction: "reverse",
                label: "Child",
            };

            const getSpy = vi.spyOn(fetch_result, "getJSON");
            getSpy.mockReturnValue(okAsync({ natures: [child_type, parent_type] }));

            const result = await getAllLinkTypes();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            const types = result.value;
            expect(types).toHaveLength(2);
            expect(types).toContain(parent_type);
            expect(types).toContain(child_type);
            expect(getSpy.mock.calls[0][0]).toStrictEqual(
                uri`/api/v1/artifacts/${ARTIFACT_ID}/links`,
            );
        });
    });

    describe(`getLinkedArtifactsByLinkType()`, () => {
        const ARTIFACT_ID = 347,
            FIRST_LINKED_ARTIFACT_ID = 40,
            SECOND_LINKED_ARTIFACT_ID = 60;
        let link_type: LinkType;

        beforeEach(() => {
            link_type = LinkTypeStub.buildParentLinkType();
        });

        const getLinkedArtifactsByLinkType = (): ResultAsync<readonly LinkedArtifact[], Fault> => {
            return getClient().getLinkedArtifactsByLinkType(
                CurrentArtifactIdentifier.fromId(ARTIFACT_ID),
                link_type,
            );
        };

        function getMockLinkedArtifactsRetrieval(
            recursiveGetSpy: MockInstance,
            linked_artifacts: LinkedArtifactCollection,
        ): void {
            recursiveGetSpy.mockImplementation(
                <TypeOfLinkedArtifact>(
                    url: string,
                    options?: GetAllOptions<TypeOfLinkedArtifact, LinkedArtifactCollection>,
                ): ResultAsync<readonly TypeOfLinkedArtifact[], Fault> => {
                    if (!options || !options.getCollectionCallback) {
                        throw Error("Unexpected options for getAllJSON");
                    }
                    return okAsync(options.getCollectionCallback(linked_artifacts));
                },
            );
        }

        it(`will return an array of linked artifacts`, async () => {
            const first_artifact = {
                id: FIRST_LINKED_ARTIFACT_ID,
                tracker: { color_name: "army-green", project: ProjectStub.withDefaults() },
            } as ArtifactWithStatus;
            const second_artifact = {
                id: SECOND_LINKED_ARTIFACT_ID,
                tracker: { color_name: "chrome-silver", project: ProjectStub.withDefaults() },
            } as ArtifactWithStatus;

            const getAllSpy = vi.spyOn(fetch_result, "getAllJSON");
            getMockLinkedArtifactsRetrieval(getAllSpy, {
                collection: [first_artifact, second_artifact],
            });

            const result = await getLinkedArtifactsByLinkType();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toHaveLength(2);
            const [first_returned_artifact, second_returned_artifact] = result.value;
            expect(first_returned_artifact.identifier.id).toBe(FIRST_LINKED_ARTIFACT_ID);
            expect(first_returned_artifact.link_type).toBe(link_type);
            expect(second_returned_artifact.identifier.id).toBe(SECOND_LINKED_ARTIFACT_ID);
            expect(second_returned_artifact.link_type).toBe(link_type);
            const call_uri = getAllSpy.mock.calls[0][0];
            const options = getAllSpy.mock.calls[0][1];
            expect(call_uri).toStrictEqual(uri`/api/v1/artifacts/${ARTIFACT_ID}/linked_artifacts`);
            expect(options).toStrictEqual({
                params: {
                    limit: 50,
                    direction: FORWARD_DIRECTION,
                    nature: IS_CHILD_LINK_TYPE,
                },
                getCollectionCallback: expect.any(Function),
            });
        });
    });

    describe(`getMatchingArtifact()`, () => {
        const COLOR: ColorName = "deep-blue",
            ARTIFACT_ID = 779,
            ARTIFACT_TITLE = "attemperation",
            ARTIFACT_XREF = `story #${ARTIFACT_ID}`;

        const getMatching = (): ResultAsync<LinkableArtifact, Fault> => {
            return getClient().getMatchingArtifact(LinkableNumberStub.withId(ARTIFACT_ID));
        };

        it(`will return a Linkable Artifact matching the given number`, async () => {
            const artifact = {
                id: ARTIFACT_ID,
                title: ARTIFACT_TITLE,
                xref: ARTIFACT_XREF,
                tracker: { color_name: COLOR, project: PROJECT },
            } as ArtifactWithStatus;
            const getSpy = vi.spyOn(fetch_result, "getJSON");
            getSpy.mockReturnValue(okAsync(artifact));

            const result = await getMatching();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(getSpy.mock.calls[0][0]).toStrictEqual(uri`/api/v1/artifacts/${ARTIFACT_ID}`);
            const linkable_artifact = result.value;
            expect(linkable_artifact.id).toBe(ARTIFACT_ID);
            expect(linkable_artifact.title).toBe(ARTIFACT_TITLE);
            expect(linkable_artifact.xref.ref).toBe(ARTIFACT_XREF);
            expect(linkable_artifact.xref.color).toBe(COLOR);
        });
    });

    describe(`getPossibleParents()`, () => {
        const TRACKER_ID = 36,
            FIRST_LINKED_ARTIFACT_ID = 17,
            SECOND_LINKED_ARTIFACT_ID = 109;

        const getPossibleParents = (): ResultAsync<readonly LinkableArtifact[], Fault> => {
            return getClient().getPossibleParents(CurrentTrackerIdentifier.fromId(TRACKER_ID));
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
            const getAllSpy = vi.spyOn(fetch_result, "getAllJSON");
            getAllSpy.mockReturnValue(okAsync([first_artifact, second_artifact]));

            const result = await getPossibleParents();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toHaveLength(2);
            const [first_returned_artifact, second_returned_artifact] = result.value;
            expect(first_returned_artifact.id).toBe(FIRST_LINKED_ARTIFACT_ID);
            expect(second_returned_artifact.id).toBe(SECOND_LINKED_ARTIFACT_ID);
            expect(getAllSpy).toHaveBeenCalledWith(
                uri`/api/v1/trackers/${TRACKER_ID}/parent_artifacts`,
                { params: { limit: 1000 } },
            );
        });
    });

    describe("getUserArtifactHistory()", () => {
        const USER_ID = 102,
            ARTIFACT_1_ID = 85,
            ARTIFACT_2_ID = 174;

        const getUserArtifactHistory = (): ResultAsync<readonly LinkableArtifact[], Fault> => {
            return getClient().getUserArtifactHistory(UserIdentifier.fromId(USER_ID));
        };

        it(`will return user history entries which are "artifact" type as linkable artifact`, async () => {
            const first_entry = { per_type_id: ARTIFACT_1_ID, type: ARTIFACT_TYPE, badges: [] };
            const second_entry = { per_type_id: ARTIFACT_2_ID, type: ARTIFACT_TYPE, badges: [] };
            const third_entry = { per_type_id: 1158, type: "kanban", badges: [] };

            const getSpy = vi.spyOn(fetch_result, "getJSON");
            getSpy.mockReturnValue(okAsync({ entries: [first_entry, second_entry, third_entry] }));

            const result = await getUserArtifactHistory();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toHaveLength(2);
            const [first_returned_artifact, second_returned_artifact] = result.value;
            expect(first_returned_artifact.id).toBe(ARTIFACT_1_ID);
            expect(second_returned_artifact.id).toBe(ARTIFACT_2_ID);
            expect(getSpy).toHaveBeenCalledWith(uri`/api/v1/users/${USER_ID}/history`);
        });
    });

    describe(`searchArtifacts()`, () => {
        const SEARCH_QUERY = "bookwright",
            ARTIFACT_1_ID = 58,
            ARTIFACT_2_ID = 94;

        const searchArtifacts = (): ResultAsync<readonly LinkableArtifact[], Fault> => {
            return getClient().searchArtifacts(SEARCH_QUERY);
        };

        it(`will return search results with type "artifact" as linkable artifacts`, async () => {
            const first_entry = { per_type_id: ARTIFACT_1_ID, type: ARTIFACT_TYPE, badges: [] };
            const second_entry = { per_type_id: ARTIFACT_2_ID, type: ARTIFACT_TYPE, badges: [] };
            const third_entry = { per_type_id: 84, type: "kanban", badges: [] };

            const postSpy = vi
                .spyOn(fetch_result, "postJSON")
                .mockReturnValue(okAsync([first_entry, second_entry, third_entry]));

            const result = await searchArtifacts();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toHaveLength(2);
            const [first_artifact, second_artifact] = result.value;
            expect(first_artifact.id).toBe(ARTIFACT_1_ID);
            expect(second_artifact.id).toBe(ARTIFACT_2_ID);
            expect(postSpy).toHaveBeenCalledWith(uri`/api/search?limit=50`, {
                keywords: SEARCH_QUERY,
            });
        });
    });
});

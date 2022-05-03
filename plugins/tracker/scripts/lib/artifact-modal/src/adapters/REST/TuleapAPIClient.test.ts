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

import * as tlp_fetch from "@tuleap/tlp-fetch";
import type { Fault } from "@tuleap/fault";
import { isFault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import type { LinkedArtifactCollection } from "./TuleapAPIClient";
import { TuleapAPIClient } from "./TuleapAPIClient";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import * as fetch_result from "@tuleap/fetch-result";
import { ResponseStub } from "@tuleap/fetch-result/tests/helper/ResponseStub";
import type { LinkedArtifact } from "../../domain/fields/link-field-v2/LinkedArtifact";
import type { ParentArtifact } from "../../domain/parent/ParentArtifact";
import { CurrentArtifactIdentifierStub } from "../../../tests/stubs/CurrentArtifactIdentifierStub";
import { ParentArtifactIdentifierStub } from "../../../tests/stubs/ParentArtifactIdentifierStub";
import type { LinkableArtifact } from "../../domain/fields/link-field-v2/LinkableArtifact";
import { LinkableNumberStub } from "../../../tests/stubs/LinkableNumberStub";
import type { ArtifactWithStatus } from "./ArtifactWithStatus";
import type { LinkType } from "../../domain/fields/link-field-v2/LinkType";
import { okAsync } from "neverthrow";
import type { GetAllOptions } from "@tuleap/fetch-result";

const FORWARD_DIRECTION = "forward";
const IS_CHILD_SHORTNAME = "_is_child";
const ARTIFACT_ID = 90;
const FIRST_LINKED_ARTIFACT_ID = 40;
const SECOND_LINKED_ARTIFACT_ID = 60;
const ARTIFACT_TITLE = "thio";
const ARTIFACT_XREF = `story #${ARTIFACT_ID}`;
const COLOR = "deep-blue";

describe(`TuleapAPIClient`, () => {
    describe(`getParent()`, () => {
        const getParent = (): ResultAsync<ParentArtifact, Fault> => {
            const client = TuleapAPIClient();
            return client.getParent(ParentArtifactIdentifierStub.withId(ARTIFACT_ID));
        };

        it(`will return the parent artifact matching the given id`, async () => {
            const artifact: ParentArtifact = { title: ARTIFACT_TITLE };
            const getSpy = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(getSpy, {
                return_json: artifact,
            });

            const result = await getParent();

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }
            expect(result.value).toBe(artifact);
        });

        it(`will return a Fault wrapping an Error if it fails`, async () => {
            const getSpy = jest.spyOn(tlp_fetch, "get");
            mockFetchError(getSpy, { status: 404, statusText: "Not found" });

            const result = await getParent();

            if (!result.isErr()) {
                throw new Error("Expected an Err");
            }
            expect(isFault(result.error)).toBe(true);
        });
    });

    describe(`getMatchingArtifact()`, () => {
        const getMatching = (): ResultAsync<LinkableArtifact, Fault> => {
            const client = TuleapAPIClient();
            return client.getMatchingArtifact(LinkableNumberStub.withId(ARTIFACT_ID));
        };

        it(`will return a Linkable Artifact matching the given number`, async () => {
            const artifact = {
                id: ARTIFACT_ID,
                title: ARTIFACT_TITLE,
                xref: ARTIFACT_XREF,
                tracker: { color_name: COLOR },
            } as ArtifactWithStatus;
            const getSpy = jest.spyOn(fetch_result, "getJSON");
            getSpy.mockReturnValue(ResponseStub.withSuccessfulPayload(artifact));

            const result = await getMatching();

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }
            expect(getSpy.mock.calls[0][0]).toBe(`/api/v1/artifacts/${ARTIFACT_ID}`);
            const linkable_artifact = result.value;
            expect(linkable_artifact.id).toBe(ARTIFACT_ID);
            expect(linkable_artifact.title).toBe(ARTIFACT_TITLE);
            expect(linkable_artifact.xref.ref).toBe(ARTIFACT_XREF);
            expect(linkable_artifact.xref.color).toBe(COLOR);
        });
    });

    describe(`getAllLinkTypes()`, () => {
        const getAllLinkTypes = (): ResultAsync<readonly LinkType[], Fault> => {
            const client = TuleapAPIClient();
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
            getSpy.mockReturnValue(
                ResponseStub.withSuccessfulPayload({ natures: [child_type, parent_type] })
            );

            const result = await getAllLinkTypes();

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }
            const types = result.value;
            expect(types).toHaveLength(2);
            expect(types).toContain(parent_type);
            expect(types).toContain(child_type);
            expect(getSpy.mock.calls[0][0]).toBe(`/api/v1/artifacts/${ARTIFACT_ID}/links`);
        });
    });

    describe(`getLinkedArtifactsByLinkType()`, () => {
        let link_type: LinkType;

        beforeEach(() => {
            link_type = {
                shortname: IS_CHILD_SHORTNAME,
                direction: FORWARD_DIRECTION,
                label: "Parent",
            };
        });

        const getLinkedArtifactsByLinkType = (): ResultAsync<readonly LinkedArtifact[], Fault> => {
            const client = TuleapAPIClient();
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
                `/api/v1/artifacts/${ARTIFACT_ID}/linked_artifacts`,
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

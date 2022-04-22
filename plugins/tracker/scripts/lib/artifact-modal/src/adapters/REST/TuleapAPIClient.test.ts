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

import type { RecursiveGetInit } from "@tuleap/tlp-fetch";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import type { Fault } from "@tuleap/fault";
import { isFault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import type { LinkedArtifactCollection } from "./TuleapAPIClient";
import { TuleapAPIClient } from "./TuleapAPIClient";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { LinkedArtifact, LinkType } from "../../domain/fields/link-field-v2/LinkedArtifact";
import type { APILinkedArtifact } from "./APILinkedArtifact";
import type { ParentArtifact } from "../../domain/parent/ParentArtifact";
import { CurrentArtifactIdentifierStub } from "../../../tests/stubs/CurrentArtifactIdentifierStub";
import { ParentArtifactIdentifierStub } from "../../../tests/stubs/ParentArtifactIdentifierStub";
import type { LinkableArtifact } from "../../domain/fields/link-field-v2/LinkableArtifact";
import { LinkableNumberStub } from "../../../tests/stubs/LinkableNumberStub";
import type { ArtifactWithStatus } from "./ArtifactWithStatus";

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
            const getSpy = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(getSpy, {
                return_json: artifact,
            });

            const result = await getMatching();

            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }
            const linkable_artifact = result.value;
            expect(linkable_artifact.id).toBe(ARTIFACT_ID);
            expect(linkable_artifact.title).toBe(ARTIFACT_TITLE);
            expect(linkable_artifact.xref.ref).toBe(ARTIFACT_XREF);
            expect(linkable_artifact.xref.color).toBe(COLOR);
        });

        it(`will return a Fault if it fails`, async () => {
            const getSpy = jest.spyOn(tlp_fetch, "get");
            mockFetchError(getSpy, { status: 404, statusText: "Not found" });

            const result = await getMatching();

            if (!result.isErr()) {
                throw new Error("Expected an Err");
            }
            expect(isFault(result.error)).toBe(true);
        });
    });

    describe(`getAllLinkTypes()`, () => {
        const getAllLinkTypes = (): Promise<LinkType[]> => {
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

            const getSpy = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(getSpy, {
                return_json: { natures: [child_type, parent_type] },
            });

            const types = await getAllLinkTypes();

            expect(types).toHaveLength(2);
            expect(types).toContain(parent_type);
            expect(types).toContain(child_type);
        });

        it(`when there is an error, it will unwrap the error message from the response`, () => {
            const getSpy = jest.spyOn(tlp_fetch, "get");
            mockFetchError(getSpy, {
                error_json: { error: { code: 403, message: "You cannot" } },
            });

            return expect(getAllLinkTypes()).rejects.toThrowError("403 You cannot");
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

        const getLinkedArtifactsByLinkType = (): Promise<LinkedArtifact[]> => {
            const client = TuleapAPIClient();
            return client.getLinkedArtifactsByLinkType(
                CurrentArtifactIdentifierStub.withId(ARTIFACT_ID),
                link_type
            );
        };

        it(`will return an array of linked artifacts`, async () => {
            const first_artifact = { id: FIRST_LINKED_ARTIFACT_ID } as APILinkedArtifact;
            const second_artifact = { id: SECOND_LINKED_ARTIFACT_ID } as APILinkedArtifact;

            const recursiveGetSpy = jest.spyOn(tlp_fetch, "recursiveGet");

            getMockLinkedArtifactsRetrieval(recursiveGetSpy, {
                collection: [first_artifact, second_artifact],
            });

            const artifacts = await getLinkedArtifactsByLinkType();

            expect(artifacts).toHaveLength(2);
            const [first_returned_artifact, second_returned_artifact] = artifacts;
            expect(first_returned_artifact.identifier.id).toBe(FIRST_LINKED_ARTIFACT_ID);
            expect(first_returned_artifact.link_type).toBe(link_type);
            expect(second_returned_artifact.identifier.id).toBe(SECOND_LINKED_ARTIFACT_ID);
            expect(second_returned_artifact.link_type).toBe(link_type);
            expect(recursiveGetSpy.mock.calls[0]).toEqual([
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

        it(`when there is an error, it will unwrap the error message from the response`, () => {
            const getSpy = jest.spyOn(tlp_fetch, "recursiveGet");
            mockFetchError(getSpy, {
                error_json: { error: { code: 403, message: "You cannot" } },
            });

            return expect(getLinkedArtifactsByLinkType()).rejects.toThrowError("403 You cannot");
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
            init?: RecursiveGetInit<LinkedArtifactCollection, TypeOfLinkedArtifact>
        ): Promise<Array<TypeOfLinkedArtifact>> => {
            if (!init || !init.getCollectionCallback) {
                throw new Error();
            }

            return Promise.resolve(init.getCollectionCallback(linked_artifacts));
        }
    );
}

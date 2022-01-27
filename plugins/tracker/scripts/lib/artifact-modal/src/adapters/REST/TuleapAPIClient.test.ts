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

import type { LinkedArtifactCollection } from "./TuleapAPIClient";
import { TuleapAPIClient } from "./TuleapAPIClient";
import type { RecursiveGetInit } from "tlp";
import * as tlp from "tlp";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { LinkedArtifact, LinkType } from "../../domain/fields/link-field-v2/LinkedArtifact";

const FORWARD_DIRECTION = "forward";
const IS_CHILD_SHORTNAME = "_is_child";
const ARTIFACT_ID = 90;

describe(`TuleapAPIClient`, () => {
    describe(`getAllLinkTypes()`, () => {
        const getAllLinkTypes = (): Promise<LinkType[]> => {
            const client = TuleapAPIClient();
            return client.getAllLinkTypes(95);
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

            const getSpy = jest.spyOn(tlp, "get");
            mockFetchSuccess(getSpy, {
                return_json: { natures: [child_type, parent_type] },
            });

            const types = await getAllLinkTypes();

            expect(types).toHaveLength(2);
            expect(types).toContain(parent_type);
            expect(types).toContain(child_type);
        });

        it(`when there is an error, it will unwrap the error message from the response`, () => {
            const getSpy = jest.spyOn(tlp, "get");
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
            return client.getLinkedArtifactsByLinkType(ARTIFACT_ID, link_type);
        };

        it(`will return an array of linked artifacts`, async () => {
            const first_artifact = { title: "implosive" } as LinkedArtifact;
            const second_artifact = { title: "belight" } as LinkedArtifact;

            const recursiveGetSpy = jest.spyOn(tlp, "recursiveGet");

            getMockLinkedArtifactsRetrieval(recursiveGetSpy, {
                collection: [first_artifact, second_artifact],
            });

            const artifacts = await getLinkedArtifactsByLinkType();

            expect(artifacts).toHaveLength(2);
            const [first_returned_artifact, second_returned_artifact] = artifacts;
            expect(first_returned_artifact.link_type).toBe(link_type);
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
            const getSpy = jest.spyOn(tlp, "recursiveGet");
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

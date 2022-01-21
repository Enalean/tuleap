/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import * as tlp from "tlp";

import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { getLinkedArtifacts } from "./links-retriever";

import type { RecursiveGetInit } from "tlp";
import type { LinkType, LinkedArtifact, LinkedArtifactCollection } from "./links-retriever";

describe("links-retriever", () => {
    let current_artifact_id: number,
        getSpy: jest.SpyInstance,
        recursiveGetSpy: jest.SpyInstance,
        nature_is_child_reverse: LinkType,
        nature_is_child_forward: LinkType,
        parent: LinkedArtifact,
        child: LinkedArtifact;

    beforeEach(() => {
        current_artifact_id = 1601;
        nature_is_child_reverse = {
            shortname: "_is_child",
            direction: "reverse",
            label: "Child",
        };

        nature_is_child_forward = {
            shortname: "_is_child",
            direction: "forward",
            label: "Parent",
        };

        parent = {
            xref: "art #123",
            title: "A parent",
            html_url: "/url/to/artifact/123",
            tracker: {
                color_name: "red-wine",
            },
            link_type: nature_is_child_reverse,
            status: "Open",
            is_open: true,
        };

        child = {
            xref: "art #234",
            title: "A child",
            html_url: "/url/to/artifact/234",
            tracker: {
                color_name: "surf-green",
            },
            link_type: nature_is_child_forward,
            status: "Open",
            is_open: true,
        };

        getSpy = jest.spyOn(tlp, "get");
        recursiveGetSpy = jest.spyOn(tlp, "recursiveGet");
    });

    it("Fetches the linked artifacts by type", async () => {
        mockFetchSuccess(getSpy, {
            return_json: {
                natures: [nature_is_child_reverse, nature_is_child_forward],
            },
        });

        getMockLinkedArtifactsRetrieval(recursiveGetSpy, { collection: [parent] });
        getMockLinkedArtifactsRetrieval(recursiveGetSpy, { collection: [child] });

        const artifacts = await getLinkedArtifacts(current_artifact_id);

        expect(getSpy).toHaveBeenCalledWith("/api/v1/artifacts/1601/links");
        expect(recursiveGetSpy.mock.calls[0]).toEqual([
            "/api/v1/artifacts/1601/linked_artifacts",
            {
                params: {
                    limit: 50,
                    offset: 0,
                    direction: nature_is_child_reverse.direction,
                    nature: nature_is_child_reverse.shortname,
                },
                getCollectionCallback: expect.any(Function),
            },
        ]);
        expect(recursiveGetSpy.mock.calls[1]).toEqual([
            "/api/v1/artifacts/1601/linked_artifacts",
            {
                params: {
                    limit: 50,
                    offset: 0,
                    direction: nature_is_child_forward.direction,
                    nature: nature_is_child_forward.shortname,
                },
                getCollectionCallback: expect.any(Function),
            },
        ]);

        expect(artifacts).toEqual([parent, child]);
    });

    it("When the retrieval of link types fails, then it throws an Error containing the extracted error message", async () => {
        mockFetchError(getSpy, {
            error_json: {
                error: {
                    code: 403,
                    message: "You cannot (links)",
                },
            },
        });

        getMockLinkedArtifactsRetrieval(recursiveGetSpy, { collection: [parent] });

        await expect(() => getLinkedArtifacts(current_artifact_id)).rejects.toThrowError(
            "403 You cannot (links)"
        );
    });

    it("When the retrieval of artifacts by types of links fails, then it throws an Error containing the extracted error message", async () => {
        mockFetchSuccess(getSpy, {
            return_json: {
                natures: [nature_is_child_reverse, nature_is_child_forward],
            },
        });

        mockFetchError(recursiveGetSpy, {
            error_json: {
                error: {
                    code: 403,
                    message: "You cannot (linked artifacts)",
                },
            },
        });

        await expect(() => getLinkedArtifacts(current_artifact_id)).rejects.toThrowError(
            "403 You cannot (linked artifacts)"
        );
    });
});

function getMockLinkedArtifactsRetrieval(
    recursiveGetSpy: jest.SpyInstance,
    linked_artifacts: LinkedArtifactCollection
): void {
    recursiveGetSpy.mockImplementationOnce(
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

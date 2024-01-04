/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import {
    getOpenMilestones,
    getBaselines,
    getComparisons,
    getBaselineArtifactsByIds,
    createBaseline,
} from "./rest-querier";
import type { Baseline, Comparison, Milestone } from "../type";

describe("Rest queries:", () => {
    describe("getOpenMilestones()", () => {
        let recursive_get_mock: jest.SpyInstance;

        const simplified_milestone: Milestone = {
            id: 1,
            label: "Milestone label",
            description: "",
        };

        beforeEach(async () => {
            recursive_get_mock = jest.spyOn(tlp_fetch, "recursiveGet");
            recursive_get_mock.mockResolvedValue(simplified_milestone);
            await getOpenMilestones(1);
        });

        it("calls projects API to get opened milestones", () => {
            expect(recursive_get_mock).toHaveBeenCalledWith(
                "/api/projects/1/milestones",
                expect.objectContaining({
                    params: {
                        query: '{"status":"open"}',
                        limit: 10,
                        offset: 0,
                    },
                }),
            );
        });
    });

    describe("getBaselines()", () => {
        let recursive_get_mock: jest.SpyInstance;
        const baseline: Baseline = {
            id: 1001,
            name: "Baseline label",
            artifact_id: 9,
            snapshot_date: "2019-03-22T10:01:48+00:00",
            author_id: 3,
        };

        beforeEach(async () => {
            recursive_get_mock = jest.spyOn(tlp_fetch, "recursiveGet");
            recursive_get_mock.mockResolvedValue({ baselines: [baseline] });
            await getBaselines(1);
        });

        it("calls projects API to get baselines", () => {
            expect(recursive_get_mock).toHaveBeenCalledWith(
                "/api/projects/1/baselines",
                expect.objectContaining({
                    params: {
                        limit: 50,
                        offset: 0,
                    },
                }),
            );
        });
    });

    describe("getComparisons()", () => {
        let recursive_get_mock: jest.SpyInstance;
        const comparison: Comparison = {
            base_baseline_id: 1,
            compared_to_baseline_id: 2,
        } as Comparison;

        beforeEach(async () => {
            recursive_get_mock = jest.spyOn(tlp_fetch, "recursiveGet");
            recursive_get_mock.mockResolvedValue({ comparisons: [comparison] });
            await getComparisons(1);
        });

        it("calls projects API to get comparisons", () => {
            expect(recursive_get_mock).toHaveBeenCalledWith(
                "/api/projects/1/baselines_comparisons",
                expect.objectContaining({
                    params: {
                        limit: 50,
                        offset: 0,
                    },
                }),
            );
        });
    });

    describe("createBaseline()", () => {
        let post: jest.SpyInstance;
        let result: Baseline;

        const baseline = {
            id: 1001,
            name: "Baseline label",
            artifact_id: 9,
            snapshot_date: "2019-03-22T10:01:48+00:00",
            author_id: 3,
        };
        const headers = {
            "content-type": "application/json",
        };
        const body = JSON.stringify({
            name: "My first baseline",
            artifact_id: 3,
            snapshot_date: null,
        });

        beforeEach(async () => {
            post = jest.spyOn(tlp_fetch, "post");
            mockFetchSuccess(post, { return_json: baseline });

            result = await createBaseline(
                "My first baseline",
                {
                    id: 3,
                    label: "milestone Label",
                } as Milestone,
                null,
            );
        });

        it("calls baselines API to create baseline", () =>
            expect(post).toHaveBeenCalledWith("/api/baselines/", { headers, body }));

        it("returns created baseline", () => expect(result).toStrictEqual(baseline));
    });

    describe("getBaselineArtifactsByIds()", () => {
        let get: jest.SpyInstance;

        beforeEach(async () => {
            get = jest.spyOn(tlp_fetch, "get");
            mockFetchSuccess(get, { return_json: {} });

            await getBaselineArtifactsByIds(1, [1, 2, 3, 4]);
        });

        it("calls baselines API to get baseline artifacts by ids", () =>
            expect(get).toHaveBeenCalledWith(
                "/api/baselines/1/artifacts?query=%7B%22ids%22%3A%5B1%2C2%2C3%2C4%5D%7D",
            ));
    });
});

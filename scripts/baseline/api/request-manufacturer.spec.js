/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

import { restore, rewire$getBaselineArtifactsByIds } from "./rest-querier";
import { create } from "../support/factories";
import { fetchAllArtifacts } from "./request-manufacturer";

describe("fetchAllArtifacts()", () => {
    let getBaselineArtifactsByIds;

    let baseline;
    let first_level_artifacts;
    let child_artifacts;
    let grandchild_artifacts;

    let baseline_data_ids;
    let all_artifacts;

    beforeEach(() => {
        getBaselineArtifactsByIds = jasmine.createSpy("getBaselineArtifactsByIds");
        rewire$getBaselineArtifactsByIds(getBaselineArtifactsByIds);
    });

    afterEach(() => {
        restore();
    });

    beforeEach(async () => {
        baseline = create("baseline");

        first_level_artifacts = [
            create("baseline_artifact", { id: 1, linked_artifact_ids: [4] }),
            create("baseline_artifact", { id: 2, linked_artifact_ids: [5] })
        ];

        child_artifacts = [
            create("baseline_artifact", { id: 4, linked_artifact_ids: [] }),
            create("baseline_artifact", { id: 5, linked_artifact_ids: [6] })
        ];

        grandchild_artifacts = [create("baseline_artifact", { id: 6, linked_artifact_ids: [] })];

        getBaselineArtifactsByIds
            .withArgs(baseline.id, [4, 5])
            .and.returnValue(Promise.resolve(child_artifacts));
        getBaselineArtifactsByIds
            .withArgs(baseline.id, [6])
            .and.returnValue(Promise.resolve(grandchild_artifacts));

        all_artifacts = await fetchAllArtifacts(baseline.id, first_level_artifacts);
        baseline_data_ids = all_artifacts.map(artifact => artifact.id);
    });

    it("returns first level artifacts", () => {
        expect(baseline_data_ids).toContain(1);
        expect(baseline_data_ids).toContain(2);
    });

    it("returns all artifacts directly linked to given first level artifacts", () => {
        expect(baseline_data_ids).toContain(4);
        expect(baseline_data_ids).toContain(5);
    });

    it("returns all artifacts indirectly linked to given first level artifacts", () => {
        expect(baseline_data_ids).toContain(6);
    });

    it("calls getBaselineArtifactsByIds() once by depth", () => {
        expect(getBaselineArtifactsByIds).toHaveBeenCalledWith(baseline.id, [4, 5]);
    });

    describe("when there is a cyclic dependency between two artifacts", () => {
        beforeEach(async () => {
            first_level_artifacts = [
                create("baseline_artifact", { id: 1, linked_artifact_ids: [1] })
            ];

            getBaselineArtifactsByIds.calls.reset();
            getBaselineArtifactsByIds
                .withArgs(baseline.id, [1])
                .and.returnValue(Promise.resolve(first_level_artifacts));

            all_artifacts = await fetchAllArtifacts(baseline.id, first_level_artifacts);
            baseline_data_ids = all_artifacts.map(artifact => artifact.id);
        });

        it("returns single occurrence or artifacts involved in cycle", () => {
            expect(baseline_data_ids).toEqual([1]);
        });
    });

    describe("when an artifact is linked to different parents", () => {
        beforeEach(async () => {
            first_level_artifacts = [
                create("baseline_artifact", { id: 1, linked_artifact_ids: [3] }),
                create("baseline_artifact", { id: 2, linked_artifact_ids: [3] })
            ];

            getBaselineArtifactsByIds.calls.reset();
            getBaselineArtifactsByIds
                .withArgs(baseline.id, [3])
                .and.returnValue(Promise.resolve(first_level_artifacts));

            all_artifacts = await fetchAllArtifacts(baseline.id, first_level_artifacts);
            baseline_data_ids = all_artifacts.map(artifact => artifact.id);
        });

        it("calls getBaselineArtifactsByIds() with distinct values", () => {
            expect(getBaselineArtifactsByIds).toHaveBeenCalledWith(baseline.id, [3]);
        });
    });
});

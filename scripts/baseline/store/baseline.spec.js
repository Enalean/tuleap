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

import {
    restore,
    rewire$getBaselineArtifactsByIds,
    rewire$getBaseline,
    rewire$getBaselineArtifacts
} from "../api/rest-querier";
import { create } from "../support/factories";
import store from "./baseline";

describe("Baseline store:", () => {
    let state;

    let getBaselineArtifactsByIds;
    let getBaseline;
    let getBaselineArtifacts;

    let baseline;
    let first_depth_artifacts;
    let child_artifacts;

    beforeEach(() => {
        state = { ...store.state };

        getBaseline = jasmine.createSpy("getBaseline");
        rewire$getBaseline(getBaseline);

        getBaselineArtifacts = jasmine.createSpy("getBaselineArtifacts");
        rewire$getBaselineArtifacts(getBaselineArtifacts);

        getBaselineArtifactsByIds = jasmine.createSpy("getBaselineArtifactsByIds");
        rewire$getBaselineArtifactsByIds(getBaselineArtifactsByIds);

        baseline = create("baseline");

        first_depth_artifacts = [create("baseline_artifact", { id: 1, linked_artifact_ids: [2] })];
        child_artifacts = [create("baseline_artifact", { id: 2, linked_artifact_ids: [] })];
        getBaseline.and.returnValue(Promise.resolve(baseline));
        getBaselineArtifacts.and.returnValue(Promise.resolve(first_depth_artifacts));
        getBaselineArtifactsByIds.and.returnValue(Promise.resolve(child_artifacts));
    });

    afterEach(restore);

    describe("actions", () => {
        let context;
        let author;
        let artifact;

        beforeEach(() => {
            const findUserById = jasmine.createSpy("findUserById");
            author = create("user");
            findUserById.and.returnValue(author);

            const findArtifactById = jasmine.createSpy("findUserById");
            artifact = create("artifact");
            findArtifactById.and.returnValue(artifact);

            const commit = jasmine.createSpy("commit");
            const dispatch = jasmine.createSpy("commit");
            const rootGetters = {
                findUserById,
                findArtifactById
            };
            context = { state, commit, dispatch, rootGetters };
        });

        describe("#load", () => {
            beforeEach(async () => {
                await store.actions.load(context, baseline.id);
            });

            it("updates baseline", () => {
                const expected_baseline = { ...baseline, first_depth_artifacts, author, artifact };
                expect(context.commit).toHaveBeenCalledWith("updateBaseline", expected_baseline);
            });
        });
    });
});

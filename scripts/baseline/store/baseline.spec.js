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
    rewire$getBaselineArtifacts,
    rewire$getUser
} from "../api/rest-querier";
import { create } from "../support/factories";
import store from "./baseline";

describe("fetchAllArtifacts()", () => {
    let state;

    let getBaselineArtifactsByIds;
    let getBaseline;
    let getBaselineArtifacts;
    let getUser;

    let baseline;
    let expected_baseline;
    let user;
    let first_level_artifacts;
    let child_artifacts;

    beforeEach(() => {
        state = { ...store.state };

        getBaseline = jasmine.createSpy("getBaseline");
        rewire$getBaseline(getBaseline);

        getBaselineArtifacts = jasmine.createSpy("getBaselineArtifacts");
        rewire$getBaselineArtifacts(getBaselineArtifacts);

        getUser = jasmine.createSpy("getUser");
        rewire$getUser(getUser);

        getBaselineArtifactsByIds = jasmine.createSpy("getBaselineArtifactsByIds");
        rewire$getBaselineArtifactsByIds(getBaselineArtifactsByIds);
    });

    afterEach(() => {
        restore();
    });

    beforeEach(() => {
        baseline = create("baseline");
        user = create("user");

        first_level_artifacts = [create("baseline_artifact", { id: 1, linked_artifact_ids: [2] })];
        child_artifacts = [create("baseline_artifact", { id: 2, linked_artifact_ids: [] })];
        getBaseline.and.returnValue(Promise.resolve(baseline));
        getBaselineArtifacts.and.returnValue(Promise.resolve(first_level_artifacts));
        getUser.and.returnValue(Promise.resolve(user));
        getBaselineArtifactsByIds.and.returnValue(Promise.resolve(child_artifacts));
    });

    describe("actions", () => {
        let context;
        beforeEach(() => {
            const commit = jasmine.createSpy("commit");
            context = { state, commit };
        });

        describe("#load", () => {
            beforeEach(async () => {
                await store.actions.load(context, baseline.id);
            });

            beforeEach(() => {
                baseline.first_level_artifacts = first_level_artifacts;
                baseline.author = user;
                expected_baseline = baseline;
            });

            it("updates baseline", () => {
                expect(context.commit).toHaveBeenCalledWith("updateBaseline", expected_baseline);
            });
        });
    });
});

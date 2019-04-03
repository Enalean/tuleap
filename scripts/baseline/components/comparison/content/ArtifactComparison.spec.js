/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { create } from "../../../support/factories";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../support/local-vue";
import ArtifactComparison from "./ArtifactComparison.vue";
import ArtifactsListComparison from "./ArtifactsListComparison.vue";
import BaselineDepthLimitReachedMessage from "../../common/BaselineDepthLimitReachedMessage.vue";
import { restore, rewire$getBaselineArtifactsByIds } from "../../../api/rest-querier";

describe("ArtifactComparison", () => {
    let wrapper;
    let getBaselineArtifactsByIds;

    beforeEach(async () => {
        getBaselineArtifactsByIds = jasmine.createSpy("getBaselineArtifactsByIds");
        getBaselineArtifactsByIds.and.returnValue(Promise.resolve([create("artifact")]));
        rewire$getBaselineArtifactsByIds(getBaselineArtifactsByIds);

        wrapper = shallowMount(ArtifactComparison, {
            localVue,
            propsData: {
                reference: create("baseline_artifact", { linked_artifact_ids: [1] }),
                compared_to: create("baseline_artifact"),
                current_depth: 1
            }
        });

        await wrapper.vm.$nextTick();
    });

    afterEach(restore);

    it("does not show depth limit message", () => {
        expect(wrapper.contains(BaselineDepthLimitReachedMessage)).toBeFalsy();
    });

    it("shows artifacts list comparison", () => {
        expect(wrapper.contains(ArtifactsListComparison)).toBeTruthy();
    });

    it("does calls getBaselineArtifactsByIds", () => {
        expect(getBaselineArtifactsByIds).toHaveBeenCalled();
    });

    describe("when artifacts does not have linked artifact", () => {
        beforeEach(async () => {
            getBaselineArtifactsByIds.calls.reset();

            wrapper.setProps({
                reference: create("baseline_artifact", { linked_artifact_ids: [] }),
                compared_to: create("baseline_artifact", { linked_artifact_ids: [] })
            });

            await wrapper.vm.$nextTick();
        });

        it("does not show information message", () => {
            expect(wrapper.contains(BaselineDepthLimitReachedMessage)).toBeFalsy();
        });

        it("does not show artifacts list comparison", () => {
            expect(wrapper.contains(ArtifactsListComparison)).toBeFalsy();
        });

        it("does not call getBaselineArtifactsByIds", () => {
            expect(getBaselineArtifactsByIds).not.toHaveBeenCalled();
        });
    });

    describe("when the current depth has reached the limit", () => {
        beforeEach(async () => {
            getBaselineArtifactsByIds.calls.reset();

            wrapper.setProps({
                current_depth: 1000
            });

            await wrapper.vm.$nextTick();
        });

        it("shows information message", () => {
            expect(wrapper.contains(BaselineDepthLimitReachedMessage)).toBeTruthy();
        });

        it("does not call getBaselineArtifactsByIds", () => {
            expect(getBaselineArtifactsByIds).not.toHaveBeenCalled();
        });

        describe("when artifacts does not have linked artifact", () => {
            beforeEach(async () => {
                wrapper.setProps({
                    reference: create("artifact", { linked_artifact_ids: [] }),
                    compared_to: create("artifact", { linked_artifact_ids: [] })
                });
                await wrapper.vm.$nextTick();
            });

            it("does not show depth limit message", () => {
                expect(wrapper.contains(BaselineDepthLimitReachedMessage)).toBeFalsy();
            });

            it("does not shows artifacts list comparison component", () => {
                expect(wrapper.contains(ArtifactsListComparison)).toBeFalsy();
            });
        });
    });
});

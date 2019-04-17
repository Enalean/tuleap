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

describe("ArtifactComparison", () => {
    let wrapper;
    let linked_artifact;

    beforeEach(() => {
        linked_artifact = create("baseline_artifact", "presented", { id: 2 });
        wrapper = shallowMount(ArtifactComparison, {
            localVue,
            propsData: {
                reference: create("baseline_artifact", "presented", {
                    linked_artifact_ids: [2],
                    linked_artifacts: [linked_artifact]
                }),
                compared_to: create("baseline_artifact", "presented")
            }
        });
    });

    it("does not show depth limit message", () => {
        expect(wrapper.contains(BaselineDepthLimitReachedMessage)).toBeFalsy();
    });

    it("shows artifacts list comparison", () => {
        expect(wrapper.contains(ArtifactsListComparison)).toBeTruthy();
    });

    describe("when artifacts does not have linked artifact", () => {
        beforeEach(async () => {
            wrapper.setProps({
                reference: create("baseline_artifact", "presented", "without_linked_artifacts"),
                compared_to: create("baseline_artifact", "presented", "without_linked_artifacts")
            });

            await wrapper.vm.$nextTick();
        });

        it("does not show information message", () => {
            expect(wrapper.contains(BaselineDepthLimitReachedMessage)).toBeFalsy();
        });

        it("does not show artifacts list comparison", () => {
            expect(wrapper.contains(ArtifactsListComparison)).toBeFalsy();
        });
    });

    describe("when the current depth has reached the limit", () => {
        beforeEach(async () => {
            wrapper.setProps({
                reference: create("baseline_artifact", "presented", {
                    is_depth_limit_reached: true
                }),
                compared_to: create("baseline_artifact", "presented", {
                    is_depth_limit_reached: true
                })
            });

            await wrapper.vm.$nextTick();
        });

        it("shows depth limit message", () => {
            expect(wrapper.contains(BaselineDepthLimitReachedMessage)).toBeTruthy();
        });
    });
});

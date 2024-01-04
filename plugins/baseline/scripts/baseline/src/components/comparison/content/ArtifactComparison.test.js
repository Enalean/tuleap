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

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "../../../support/store-wrapper.test-helper";
import localVue from "../../../support/local-vue";
import ArtifactComparison from "./ArtifactComparison.vue";
import ArtifactsListComparison from "./ArtifactsListComparison.vue";
import DepthLimitReachedMessage from "../../common/DepthLimitReachedMessage.vue";
import store_options from "../../../store/store_options";

describe("ArtifactComparison", () => {
    let wrapper;
    let $store;
    let linked_artifact;

    beforeEach(() => {
        $store = createStoreMock({
            ...store_options,
            getters: {
                "comparison/filterArtifacts": () => [linked_artifact],
                "comparison/base/findArtifactsByIds": () => [linked_artifact],
                "comparison/base/isLimitReachedOnArtifact": () => false,
                "comparison/compared_to/findArtifactsByIds": () => [],
                "comparison/compared_to/isLimitReachedOnArtifact": () => false,
            },
        });
        linked_artifact = { id: 2 };

        wrapper = shallowMount(ArtifactComparison, {
            localVue,
            propsData: {
                base: {
                    id: 1,
                    linked_artifact_ids: [2],
                },
                compared_to: { id: 2 },
            },
            mocks: { $store },
        });
    });

    it("does not show depth limit message", () => {
        expect(wrapper.findComponent(DepthLimitReachedMessage).exists()).toBeFalsy();
    });

    it("shows artifacts list comparison", () => {
        expect(wrapper.findComponent(ArtifactsListComparison).exists()).toBeTruthy();
    });

    describe("when artifacts does not have linked artifact", () => {
        beforeEach(async () => {
            wrapper.setProps({
                base: {
                    linked_artifact_ids: [],
                    linked_artifacts: [],
                },
                compared_to: {
                    linked_artifact_ids: [],
                    linked_artifacts: [],
                },
            });

            await wrapper.vm.$nextTick();
        });

        it("does not show depth limit message", () => {
            expect(wrapper.findComponent(DepthLimitReachedMessage).exists()).toBeFalsy();
        });

        it("does not show artifacts list comparison", () => {
            expect(wrapper.findComponent(ArtifactsListComparison).exists()).toBeFalsy();
        });
    });

    describe("when the current depth has reached the limit", () => {
        beforeEach(async () => {
            $store.getters["comparison/base/isLimitReachedOnArtifact"] = () => true;
            wrapper.setProps({
                reference: { id: 1 },
                compared_to: { id: 2 },
            });

            await wrapper.vm.$nextTick();
        });

        it("shows depth limit message", () => {
            expect(wrapper.findComponent(DepthLimitReachedMessage).exists()).toBeTruthy();
        });
    });
});

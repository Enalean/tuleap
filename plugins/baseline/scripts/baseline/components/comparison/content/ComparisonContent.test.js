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
 *
 */

import { shallowMount } from "@vue/test-utils";
import localVue from "../../../support/local-vue.ts";
import ComparisonContent from "./ComparisonContent.vue";
import { createList } from "../../../support/factories";
import { createStoreMock } from "../../../support/store-wrapper.test-helper";
import store_options from "../../../store/store_options";
import ArtifactsListComparison from "./ArtifactsListComparison.vue";

describe("ComparisonContent", () => {
    let $store;
    let wrapper;

    beforeEach(() => {
        $store = createStoreMock(
            {
                ...store_options,
                getters: {
                    "comparison/filterArtifacts": () => [],
                },
            },
            {
                comparison: {
                    base: {
                        first_depth_artifacts: [],
                    },
                    compared_to: {
                        first_depth_artifacts: [],
                    },
                },
            },
        );

        wrapper = shallowMount(ComparisonContent, {
            localVue,
            mocks: { $store },
        });
    });

    describe("when some artifacts available", () => {
        beforeEach(() => {
            $store.getters["comparison/filterArtifacts"] = () => createList("baseline_artifact", 2);
            $store.state.comparison.base.first_depth_artifacts = createList("baseline_artifact", 2);
            $store.state.comparison.compared_to.first_depth_artifacts = [];
        });
        it("shows artifacts list comparison", () => {
            expect(wrapper.findComponent(ArtifactsListComparison).exists()).toBeTruthy();
        });
    });

    describe("when no artifact available", () => {
        beforeEach(() => {
            $store.state.comparison.base.first_depth_artifacts = [];
            $store.state.comparison.compared_to.first_depth_artifacts = [];
        });
        it("shows artifacts list comparison", () => {
            expect(
                wrapper.find('[data-test-type="no-comparison-available-message"]').exists(),
            ).toBeTruthy();
        });
    });
});

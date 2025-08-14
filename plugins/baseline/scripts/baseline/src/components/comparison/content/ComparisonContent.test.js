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
import { getGlobalTestOptions } from "../../../support/global-options-for-tests";
import ComparisonContent from "./ComparisonContent.vue";
import ArtifactsListComparison from "./ArtifactsListComparison.vue";

describe("ComparisonContent", () => {
    function createWrapper(
        filter_artifact,
        base_first_depth_artifacts,
        compared_first_depth_artifacts,
    ) {
        return shallowMount(ComparisonContent, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        comparison: {
                            namespaced: true,
                            getters: {
                                filterArtifacts: () => () => filter_artifact,
                            },
                            state: {
                                base: {
                                    first_depth_artifacts: base_first_depth_artifacts,
                                },
                                compared_to: {
                                    first_depth_artifacts: compared_first_depth_artifacts,
                                },
                            },
                        },
                    },
                }),
            },
        });
    }

    it("when there are some artifacts available then it shows artifacts list comparison", () => {
        const filter_artifacts = [
            {
                id: 101,
                title: "Sprint-1",
                status: "Planned",
                tracker_id: 1,
                initial_effort: null,
                tracker_name: "Sprint",
                description:
                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                linked_artifact_ids: [],
            },
            {
                id: 102,
                title: "Sprint-2",
                status: "Planned",
                tracker_id: 1,
                initial_effort: null,
                tracker_name: "Sprint",
                description:
                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                linked_artifact_ids: [],
            },
        ];

        const base_first_depth_artifacts = [
            {
                id: 101,
                title: "Sprint-1",
                status: "Planned",
                tracker_id: 1,
                initial_effort: null,
                tracker_name: "Sprint",
                description:
                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                linked_artifact_ids: [],
            },
            {
                id: 102,
                title: "Sprint-2",
                status: "Planned",
                tracker_id: 1,
                initial_effort: null,
                tracker_name: "Sprint",
                description:
                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                linked_artifact_ids: [],
            },
        ];

        const wrapper = createWrapper(filter_artifacts, base_first_depth_artifacts, []);
        expect(wrapper.findComponent(ArtifactsListComparison).exists()).toBeTruthy();
    });

    it("when no artifact are available then it shows artifacts list comparison", () => {
        const wrapper = createWrapper([], [], []);

        expect(
            wrapper.find('[data-test-type="no-comparison-available-message"]').exists(),
        ).toBeTruthy();
    });
});

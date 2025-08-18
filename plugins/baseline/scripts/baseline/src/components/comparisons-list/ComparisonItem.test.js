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
import ComparisonItem from "./ComparisonItem.vue";
import ArtifactLink from "../common/ArtifactLink.vue";
import { getGlobalTestOptions } from "../../support/global-options-for-tests";

describe("Comparison", () => {
    let wrapper, base_baseline_artifact;

    beforeEach(() => {
        base_baseline_artifact = {
            id: 1,
            tracker: {
                id: 9,
            },
        };

        const tracker = { id: 9 };

        wrapper = shallowMount(ComparisonItem, {
            props: {
                comparison: { id: 1, base_baseline_id: 11, compared_to_baseline_id: 12 },
            },
            global: {
                ...getGlobalTestOptions({
                    getters: {
                        findBaselineById: () => (id) => {
                            if (id === 11) {
                                return { artifact_id: 22 };
                            }
                            if (id === 12) {
                                return {
                                    id: 1001,
                                    name: "Baseline label",
                                    artifact_id: 9,
                                    snapshot_date: "2019-03-22T10:01:48+00:00",
                                    author_id: 3,
                                };
                            }
                            throw new Error("Not expected ID: " + id);
                        },
                        findArtifactById: () => () => base_baseline_artifact,
                        findTrackerById: () => () => tracker,
                    },
                }),
            },
        });
    });

    it("shows base baseline as milestone", () => {
        expect(wrapper.findComponent(ArtifactLink).vm.artifact).toEqual(base_baseline_artifact);
    });
});

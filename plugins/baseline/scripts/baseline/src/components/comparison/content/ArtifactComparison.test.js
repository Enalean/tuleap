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
import { getGlobalTestOptions } from "../../../support/global-options-for-tests";
import ArtifactComparison from "./ArtifactComparison.vue";

describe("ArtifactComparison", () => {
    function createWrapper(isLimitReachedOnArtifact) {
        const linked_artifact = { id: 2 };
        return shallowMount(ArtifactComparison, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        "comparison/base": {
                            namespaced: true,
                            getters: {
                                filterArtifacts: () => () => [linked_artifact],
                                findArtifactsByIds: () => () => [linked_artifact],
                                isLimitReachedOnArtifact: () => () => isLimitReachedOnArtifact,
                            },
                            actions: {
                                load: jest.fn(),
                            },
                        },
                        "comparison/compared_to": {
                            namespaced: true,
                            getters: {
                                filterArtifacts: () => () => [linked_artifact],
                                findArtifactsByIds: () => () => [linked_artifact],
                                isLimitReachedOnArtifact: () => () => isLimitReachedOnArtifact,
                            },
                            actions: {
                                load: jest.fn(),
                            },
                        },
                    },
                }),
            },
            props: {
                base: { id: 1, linked_artifact_ids: [2] },
                compared_to: { id: 2 },
            },
        });
    }

    it("does not show depth limit message", () => {
        const wrapper = createWrapper(false);
        expect(wrapper.vm.is_depth_limit_reached).toBeFalsy();
        expect(wrapper.vm.are_linked_artifacts_available).toBeTruthy();
    });

    describe("when artifacts does not have linked artifact", () => {
        it("does not show depth limit message and list comparison", async () => {
            const wrapper = createWrapper(false);
            await wrapper.setProps({
                base: {
                    linked_artifact_ids: [],
                    linked_artifacts: [],
                },
                compared_to: {
                    linked_artifact_ids: [],
                    linked_artifacts: [],
                },
            });

            expect(wrapper.vm.is_depth_limit_reached).toBeFalsy();
            expect(wrapper.vm.are_linked_artifacts_available).toBeFalsy();
        });
    });

    describe("when the current depth has reached the limit", () => {
        it("shows depth limit message", async () => {
            const wrapper = createWrapper(true);
            await wrapper.setProps({
                reference: { id: 1 },
                compared_to: { id: 2 },
            });

            expect(wrapper.vm.is_depth_limit_reached).toBeTruthy();
        });
    });
});

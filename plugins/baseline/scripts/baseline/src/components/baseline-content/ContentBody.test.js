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
import { getGlobalTestOptions } from "../../support/global-options-for-tests";
import ContentBody from "./ContentBody.vue";
import ArtifactsList from "./ArtifactsList.vue";

describe("ContentBody", () => {
    let first_depth_artifacts, filter_artifacts;

    beforeEach(() => {
        first_depth_artifacts = [];
        filter_artifacts = () => [];
    });

    const getWrapper = () => {
        return shallowMount(ContentBody, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        current_baseline: {
                            namespaced: true,
                            state: { first_depth_artifacts },
                            getters: {
                                filterArtifacts: () => filter_artifacts,
                            },
                        },
                    },
                }),
            },
        });
    };

    describe("when no first depth artifacts", () => {
        it("shows empty artifact message", () => {
            expect(
                getWrapper().find('[data-test-type="empty-artifact-message"]').exists(),
            ).toBeTruthy();
        });
    });

    describe("when some first depth artifacts", () => {
        beforeEach(() => {
            first_depth_artifacts = [
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
        });

        describe("when all artifacts hidden", () => {
            it("shows all artifacts filtered message", () => {
                const wrapper = getWrapper();

                expect(
                    wrapper.find('[data-test-type="all-artifacts-filtered-message"]').exists(),
                ).toBeTruthy();
            });
        });

        describe("when some artifacts are visible", () => {
            const filtered_artifacts = [
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

            beforeEach(() => {
                filter_artifacts = () => filtered_artifacts;
            });

            it("shows all visible artifacts", () => {
                const wrapper = getWrapper();

                expect(wrapper.findComponent(ArtifactsList).exists()).toBeTruthy();
                expect(wrapper.findComponent(ArtifactsList).props().artifacts).toEqual(
                    filtered_artifacts,
                );
            });
        });
    });
});

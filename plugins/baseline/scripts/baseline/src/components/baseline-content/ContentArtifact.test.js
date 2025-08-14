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
import { getGlobalTestOptions } from "../../support/global-options-for-tests";
import DepthLimitReachedMessage from "../common/DepthLimitReachedMessage.vue";
import ContentArtifact from "./ContentArtifact.vue";
import ArtifactsList from "./ArtifactsList.vue";

describe("Artifact", () => {
    let filter_artifact;

    const artifact_fields_selector = '[data-test-type="artifact-fields"]';
    const artifact_description_selector = '[data-test-type="artifact-description"]';
    const artifact_status_selector = '[data-test-type="artifact-status"]';

    const artifact_where_not_limit_reached = {
        id: 1,
        description: "",
        status: "Planned",
        tracker_id: 1,
    };
    const artifact_where_limit_reached = {
        id: 2,
        description: "",
        status: "Planned",
        tracker_id: 1,
    };

    function getWrapper() {
        const linked_artifact = { id: 3, title: "Story" };

        return shallowMount(ContentArtifact, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        current_baseline: {
                            namespaced: true,
                            getters: {
                                findArtifactsByIds: () => () => [linked_artifact],
                                isLimitReachedOnArtifact: () => (value) => {
                                    return value.id === artifact_where_limit_reached.id;
                                },
                                filterArtifacts: () => filter_artifact,
                            },
                        },
                        semantics: {
                            namespaced: true,
                            getters: {
                                field_label: () => "My description",
                                is_field_label_available: () => true,
                            },
                        },
                    },
                }),
            },
            props: {
                artifact: {
                    id: 1,
                    description: "",
                    status: "Planned",
                    tracker_id: 1,
                    title: "Epic",
                    linked_artifact_ids: [linked_artifact.id],
                },
            },
        });
    }

    beforeEach(() => {
        filter_artifact = () => [];
    });

    describe("when artifact has description", () => {
        it("shows artifact descriptions", async () => {
            const wrapper = getWrapper();
            await wrapper.setProps({
                artifact: {
                    status: "Planned",
                    description: "my description",
                    tracker_id: 9,
                },
            });

            expect(wrapper.find(artifact_description_selector).exists()).toBeTruthy();
        });
    });

    describe("when artifact has no description", () => {
        it("does not show description", async () => {
            const wrapper = getWrapper();
            await wrapper.setProps({
                artifact: { description: null, status: "Planned", tracker_id: 9 },
            });

            expect(
                wrapper.get(artifact_fields_selector).find(artifact_description_selector).exists(),
            ).toBeFalsy();
        });
    });

    describe("when artifact has status", () => {
        it("shows artifact status", async () => {
            const wrapper = getWrapper();
            await wrapper.setProps({
                artifact: { description: "lorem", status: "my status", tracker_id: 9 },
            });
            expect(wrapper.find(artifact_status_selector).exists()).toBeTruthy();
        });
    });

    describe("when artifact has no status", () => {
        it("does not show status", async () => {
            const wrapper = getWrapper();
            await wrapper.setProps({
                artifact: { status: null, description: "" },
            });

            expect(
                wrapper.get(artifact_fields_selector).find(artifact_status_selector).exists(),
            ).toBeFalsy();
        });
    });

    describe("when artifacts tree has reached depth limit", () => {
        let wrapper;

        beforeEach(async () => {
            wrapper = getWrapper();
            await wrapper.setProps({ artifact: artifact_where_limit_reached });
        });

        it("shows depth limit reached message", () => {
            expect(wrapper.findComponent(DepthLimitReachedMessage).exists()).toBeTruthy();
        });

        it("does not show linked artifact", () => {
            expect(wrapper.findComponent(ArtifactsList).exists()).toBeFalsy();
        });
    });

    describe("when artifacts tree has not reached depth limit", () => {
        describe("when some linked artifacts are filtered", () => {
            const filtered_linked_artifacts = [
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
                {
                    id: 103,
                    title: "Sprint-3",
                    status: "Planned",
                    tracker_id: 1,
                    initial_effort: null,
                    tracker_name: "Sprint",
                    description:
                        "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                    linked_artifact_ids: [],
                },
            ];

            it("shows only visible linked artifacts", async () => {
                filter_artifact = () => filtered_linked_artifacts;

                const wrapper = getWrapper();
                await wrapper.setProps({ artifact: artifact_where_not_limit_reached });

                expect(wrapper.findComponent(ArtifactsList).exists()).toBeTruthy();
                expect(wrapper.findComponent(ArtifactsList).props().artifacts).toEqual(
                    filtered_linked_artifacts,
                );
            });
        });
    });
});

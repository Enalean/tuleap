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
import { createLocalVueForTests } from "../../support/local-vue.ts";
import { createStoreMock } from "../../support/store-wrapper.test-helper.js";
import store_options from "../../store/store_options";
import ContentBody from "./ContentBody.vue";
import ArtifactsList from "./ArtifactsList.vue";

describe("ContentBody", () => {
    let $store, wrapper;

    beforeEach(async () => {
        $store = createStoreMock(
            {
                ...store_options,
                getters: {
                    "current_baseline/filterArtifacts": () => [],
                },
            },
            {
                current_baseline: { first_depth_artifacts: [] },
            },
        );

        wrapper = shallowMount(ContentBody, {
            localVue: await createLocalVueForTests(),
            mocks: {
                $store,
            },
        });
    });

    describe("when no first depth artifacts", () => {
        beforeEach(() => ($store.state.current_baseline.first_depth_artifacts = []));
        it("shows empty artifact message", () => {
            expect(wrapper.find('[data-test-type="empty-artifact-message"]').exists()).toBeTruthy();
        });
    });

    describe("when some first depth artifacts", () => {
        beforeEach(
            () =>
                ($store.state.current_baseline.first_depth_artifacts = [
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
                ]),
        );

        describe("when all artifacts hidden", () => {
            beforeEach(() => ($store.getters["current_baseline/filterArtifacts"] = () => []));
            it("shows all artifacts filtered message", () => {
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

            beforeEach(
                () =>
                    ($store.getters["current_baseline/filterArtifacts"] = () => filtered_artifacts),
            );

            it("shows all visible artifacts", () => {
                expect(wrapper.findComponent(ArtifactsList).exists()).toBeTruthy();
                expect(wrapper.findComponent(ArtifactsList).props().artifacts).toEqual(
                    filtered_artifacts,
                );
            });
        });
    });
});

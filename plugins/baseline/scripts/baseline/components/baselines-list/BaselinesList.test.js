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
import localVue from "../../support/local-vue.ts";
import BaselinesList from "./BaselinesList.vue";
import BaselineSkeleton from "./BaselineSkeleton.vue";
import BaselineListItem from "./BaselineListItem.vue";
import { createStoreMock } from "../../support/store-wrapper.test-helper";
import store_options from "../../store/store_options";

describe("BaselinesList", () => {
    const empty_baseline_selector = '[data-test-type="empty-baseline"]';
    let $store;
    let wrapper;

    beforeEach(() => {
        $store = createStoreMock({
            ...store_options,
            getters: {
                "baselines/are_baselines_available": false,
            },
        });

        wrapper = shallowMount(BaselinesList, {
            propsData: {
                project_id: 102,
            },
            localVue,
            mocks: { $store },
        });
    });

    it("loads all baselines from given project id", () => {
        expect($store.dispatch).toHaveBeenCalledWith("baselines/load", { project_id: 102 });
    });

    describe("when baselines are loading", () => {
        beforeEach(() => ($store.state.baselines.are_baselines_loading = true));

        it("does not show any baseline", () => {
            expect(wrapper.findComponent(BaselineListItem).exists()).toBeFalsy();
        });

        it("shows baseline skeleton", () => {
            expect(wrapper.findComponent(BaselineSkeleton).exists()).toBeTruthy();
        });

        it("does not show a message that specifies an empty state", () => {
            expect(wrapper.find(empty_baseline_selector).exists()).toBeFalsy();
        });
    });

    describe("when baselines loaded", () => {
        beforeEach(() => ($store.state.baselines.are_baselines_loading = false));

        describe("with many baselines", () => {
            beforeEach(() => {
                $store.state.baselines.baselines = [
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
                $store.getters["baselines/are_baselines_available"] = true;
            });

            it("shows as many baselines as given", () => {
                let baselines = wrapper.findAllComponents(BaselineListItem);
                expect(baselines).toHaveLength(3);
            });

            it("does not show baseline skeleton", () => {
                expect(wrapper.findComponent(BaselineSkeleton).exists()).toBeFalsy();
            });

            it("does not show a message that specifies an empty state", () => {
                expect(wrapper.find(empty_baseline_selector).exists()).toBeFalsy();
            });
        });

        describe("without any baseline", () => {
            beforeEach(() => {
                $store.state.baselines.baselines = [];
                $store.getters["baselines/are_baselines_available"] = false;
            });

            it("does not show baselines", () => {
                expect(wrapper.findComponent(BaselineListItem).exists()).toBeFalsy();
            });

            it("does not show baseline skeleton", () => {
                expect(wrapper.findComponent(BaselineSkeleton).exists()).toBeFalsy();
            });

            it("shows a message that specifies an empty state", () => {
                expect(wrapper.find(empty_baseline_selector).exists()).toBeTruthy();
            });
        });
    });
});

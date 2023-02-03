/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import SearchCriteriaPanel from "./SearchCriteriaPanel.vue";
import SearchCriteriaBreadcrumb from "./SearchCriteriaBreadcrumb.vue";
import type { ConfigurationState } from "../../store/configuration";
import CriterionGlobalText from "./Criteria/CriterionGlobalText.vue";
import type { AdvancedSearchParams, SearchDate } from "../../type";
import { buildAdvancedSearchParams } from "../../helpers/build-advanced-search-params";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { nextTick } from "vue";

describe("SearchCriteriaPanel", () => {
    it("should allow user to search for new terms", async () => {
        // Need to attach the wrapper to a parent node so that Vue Test Utils can
        // submit the form when clicking on the submit button.
        // See https://github.com/vuejs/vue-test-utils/issues/1030#issuecomment-441166455
        const parent_node = document.createElement("div");
        if (document.body) {
            document.body.appendChild(parent_node);
        }

        const state = {
            root_id: 101,
            criteria: [
                { name: "id", type: "number", title: "Id" },
                { name: "type", type: "list", title: "Type" },
                { name: "filename", type: "text", title: "Filename" },
                { name: "title", type: "text", title: "Title" },
                { name: "description", type: "text", title: "Description" },
                { name: "owner", type: "owner", title: "Owner" },
                { name: "create_date", type: "date", title: "Create date" },
                { name: "update_date", type: "date", title: "Update date" },
                {
                    name: "obsolescence_date",
                    type: "date",
                    title: "Obsolescence date",
                },
                { name: "status", type: "list", title: "Status" },
            ],
        } as unknown as ConfigurationState;
        const wrapper = shallowMount(SearchCriteriaPanel, {
            attachTo: parent_node,
            propsData: {
                query: buildAdvancedSearchParams({ global_search: "Lorem" }),
                folder_id: 101,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            namespaced: true,
                            state,
                        },
                    },
                }),
            },
        });

        await nextTick();

        wrapper.findComponent(CriterionGlobalText).setValue("Lorem ipsum");
        wrapper.findComponent("criterion-number-stub").setValue("123");
        wrapper.findComponent("criterion-list-stub").setValue("folder");
        wrapper.findComponent("criterion-text-stub").setValue("bob.jpg");
        wrapper.findComponent("criterion-owner-stub").setValue("jdoe");
        const create_date: SearchDate = { date: "2022-01-01", operator: ">" };
        wrapper.findComponent("criterion-date-stub").setValue(create_date);
        wrapper.find("[data-test=submit]").trigger("click");

        const expected_params: AdvancedSearchParams = {
            global_search: "Lorem ipsum",
            id: "123",
            type: "folder",
            filename: "bob.jpg",
            title: "",
            description: "",
            owner: "jdoe",
            create_date,
            update_date: null,
            obsolescence_date: null,
            status: "",
            sort: { name: "update_date", order: "desc" },
        };
        expect(wrapper.emitted()["advanced-search"]).toStrictEqual([[expected_params]]);

        // Avoid memory leaks when attaching to a parent node.
        // See https://vue-test-utils.vuejs.org/api/options.html#attachto
        wrapper.unmount();
    });

    it("should not display the breadcrumbs if we are searching in root folder", async () => {
        const wrapper = shallowMount(SearchCriteriaPanel, {
            propsData: {
                query: buildAdvancedSearchParams({ global_search: "Lorem" }),
                folder_id: 101,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                root_id: 101,
                                criteria: [],
                            },
                            namespaced: true,
                        } as unknown as ConfigurationState,
                    },
                }),
            },
        });

        expect(wrapper.findComponent(SearchCriteriaBreadcrumb).exists()).toBe(false);

        await wrapper.setProps({ folder_id: 102 });

        expect(wrapper.findComponent(SearchCriteriaBreadcrumb).exists()).toBe(true);
    });
});

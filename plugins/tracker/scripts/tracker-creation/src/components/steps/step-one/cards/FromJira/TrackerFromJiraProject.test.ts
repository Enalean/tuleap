/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

import TrackerFromJiraProject from "./TrackerFromJiraProject.vue";
import { createTrackerCreationLocalVue } from "../../../../../helpers/local-vue-for-tests";
import { shallowMount, Wrapper } from "@vue/test-utils";
import { Credentials, JiraImportData, ProjectList, State } from "../../../../../store/type";
import { createStoreMock } from "../../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";

describe("TrackerFromJiraProject", () => {
    let state: State;
    let wrapper: Wrapper<TrackerFromJiraProject>;
    beforeEach(async () => {
        state = {
            from_jira_data: {
                credentials: {
                    server_url: "https://example.com",
                    user_email: "user-email@example.com",
                    token: "azerty1234",
                } as Credentials,
            } as JiraImportData,
        } as State;

        wrapper = shallowMount(TrackerFromJiraProject, {
            localVue: await createTrackerCreationLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state,
                }),
            },
            propsData: {
                project_list: [
                    { id: "TO", label: "toto" } as ProjectList,
                    { id: "TU", label: "tutu" } as ProjectList,
                ],
            },
        });
    });

    it("renders the component", () => {
        expect(wrapper.element).toMatchSnapshot();
    });

    it("load the project list", async () => {
        const value = "TO";
        await wrapper.vm.$nextTick();

        (wrapper.find("[data-test=project-TO]").element as HTMLOptionElement).selected = true;

        wrapper.get("[data-test=project-list]").trigger("change");

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("getJiraTrackerList", {
            credentials: state.from_jira_data.credentials,
            project_key: value,
        });
        expect(wrapper.vm.$data.error_message).toBe("");

        expect(wrapper.find("[data-test=jira-fail-load-project]").exists()).toBe(false);
    });

    it("display the error message", async () => {
        wrapper.vm.$data.error_message = "Oh snap!";

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=jira-fail-load-trackers]").exists()).toBe(true);
    });
});

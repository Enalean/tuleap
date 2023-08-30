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
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type {
    Credentials,
    JiraImportData,
    ProjectList,
    State,
    TrackerList,
} from "../../../../../store/type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("TrackerFromJiraProject", () => {
    let state: State;
    let wrapper: Wrapper<TrackerFromJiraProject>;
    beforeEach(async () => {
        const tracker_list: TrackerList[] = [];
        state = {
            from_jira_data: {
                credentials: {
                    server_url: "https://example.com",
                    user_email: "user-email@example.com",
                    token: "azerty1234",
                },
                project: null,
                tracker_list,
                tracker: null,
            },
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

    it("load the project list", () => {
        const value = "TO";

        (wrapper.find("[data-test=project-TO]").element as HTMLOptionElement).selected = true;

        wrapper.get("[data-test=project-list]").trigger("change");

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

describe("TrackerFromJiraProject reload state", () => {
    let state: State;
    let wrapper: Wrapper<TrackerFromJiraProject>;
    beforeEach(async () => {
        const project_one: ProjectList = { id: "TO", label: "Toto" };
        const project_two: ProjectList = { id: "TU", label: "Tutu" };

        const tracker_one: TrackerList = { id: "Tr", name: "Tracker 1" };
        const tracker_two: TrackerList = { id: "Tra", name: "Tracker 2" };

        state = {
            from_jira_data: {
                credentials: {
                    server_url: "https://example.com",
                    user_email: "user-email@example.com",
                    token: "azerty1234",
                } as Credentials,
                project_list: [project_one, project_two] as ProjectList[],
                tracker_list: [tracker_one, tracker_two] as TrackerList[],
                project: project_two as ProjectList,
                tracker: tracker_two as TrackerList,
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
    it("does not load twice the data", () => {
        const selected_project = (
            wrapper.find("[data-test=project-list]").element as HTMLSelectElement
        ).value;
        expect(selected_project).toBe(JSON.stringify({ id: "TU", label: "tutu" }));

        const selected_tracker = (
            wrapper.find("[data-test=tracker-name]").element as HTMLSelectElement
        ).value;
        expect(selected_tracker).toBe(JSON.stringify({ id: "Tra", name: "Tracker 2" }));

        expect(wrapper.vm.$store.dispatch).not.toHaveBeenCalled();
    });
});

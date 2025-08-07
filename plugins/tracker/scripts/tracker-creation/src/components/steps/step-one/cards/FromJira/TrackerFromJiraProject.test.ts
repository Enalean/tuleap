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
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-tests";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type {
    Credentials,
    JiraImportData,
    ProjectList,
    State,
    TrackerList,
} from "../../../../../store/type";

function noop(): void {
    //Do nothing
}

describe("TrackerFromJiraProject", () => {
    let state: State, mock_get_jira_tracker_list: jest.Mock;

    function getWrapper(): VueWrapper<InstanceType<typeof TrackerFromJiraProject>> {
        return shallowMount(TrackerFromJiraProject, {
            global: {
                ...getGlobalTestOptions({
                    state,
                    mutations: {
                        setTrackerList: noop,
                        setProject: noop,
                    },
                    actions: { getJiraTrackerList: mock_get_jira_tracker_list },
                }),
            },
            props: {
                project_list: [
                    { id: "TO", label: "toto" } as ProjectList,
                    { id: "TU", label: "tutu" } as ProjectList,
                ],
            },
        });
    }

    beforeEach(() => {
        mock_get_jira_tracker_list = jest.fn();

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
    });

    it("load the project list", () => {
        const wrapper = getWrapper();

        const value = "TO";

        wrapper.get<HTMLOptionElement>("[data-test=project-TO]").element.selected = true;

        wrapper.get("[data-test=project-list]").trigger("change");

        expect(mock_get_jira_tracker_list).toHaveBeenCalledWith(expect.anything(), {
            credentials: state.from_jira_data.credentials,
            project_key: value,
        });
        expect(wrapper.find("[data-test=jira-fail-load-trackers]").exists()).toBe(false);
    });

    it("display the error message", async () => {
        const wrapper = getWrapper();

        wrapper.vm.error_message = "Oh snap!";
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=jira-fail-load-trackers]").exists()).toBe(true);
    });

    describe("TrackerFromJiraProject reload state", () => {
        beforeEach(() => {
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
        });

        it("does not load twice the data", () => {
            const wrapper = getWrapper();

            const selected_project = wrapper.find<HTMLSelectElement>("[data-test=project-list]")
                .element.value;
            expect(selected_project).toBe(JSON.stringify({ id: "TU", label: "tutu" }));

            const selected_tracker = wrapper.find<HTMLSelectElement>("[data-test=tracker-name]")
                .element.value;
            expect(selected_tracker).toBe(JSON.stringify({ id: "Tra", name: "Tracker 2" }));

            expect(mock_get_jira_tracker_list).not.toHaveBeenCalled();
        });
    });
});

/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import TrackerFromJira from "./TrackerFromJira.vue";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-tests";
import type { Credentials, JiraImportData, State } from "../../../../../store/type";

function noop(): void {
    //Do nothing
}

describe("TrackerFromJira", () => {
    let mock_get_jira_project_list: jest.Mock;

    beforeEach(() => {
        mock_get_jira_project_list = jest.fn();
    });

    function getWrapper(): VueWrapper<InstanceType<typeof TrackerFromJira>> {
        const state = {
            from_jira_data: {
                credentials: {
                    server_url: "https://example.com",
                    user_email: "user-email@example.com",
                    token: "azerty1234",
                } as Credentials,
            } as JiraImportData,
        } as State;

        return shallowMount(TrackerFromJira, {
            global: {
                ...getGlobalTestOptions({
                    state,
                    mutations: {
                        setJiraCredentials: noop,
                        setProjectList: noop,
                    },
                    actions: {
                        getJiraProjectList: mock_get_jira_project_list,
                    },
                }),
            },
        });
    }

    it("load the project list", async () => {
        const credentials = {
            server_url: "https://example.com",
            user_email: "user-email@example.com",
            token: "azerty1234",
        } as Credentials;

        const wrapper = getWrapper();

        wrapper.vm.credentials = credentials;
        wrapper.trigger("submit");

        expect(mock_get_jira_project_list).toHaveBeenCalledWith(expect.anything(), credentials);
        await wrapper.vm.$nextTick();
        expect(wrapper.find("[data-test=should-display-connexion]").exists()).toBe(true);
        expect(wrapper.find("[data-test=jira-fail-load-project]").exists()).toBe(false);

        expect(wrapper.find("[data-test=jira-fail-load-project]").exists()).toBe(false);
    });

    it("display the error message", async () => {
        const wrapper = getWrapper();

        wrapper.vm.error_message = "Oh snap!";
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=jira-fail-load-project]").exists()).toBe(true);
    });
});

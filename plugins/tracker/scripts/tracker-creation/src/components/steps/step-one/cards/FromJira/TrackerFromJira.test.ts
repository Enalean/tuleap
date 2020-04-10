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

import { shallowMount, Wrapper } from "@vue/test-utils";
import TrackerFromJira from "./TrackerFromJira.vue";
import { createTrackerCreationLocalVue } from "../../../../../helpers/local-vue-for-tests";
import { Credentials, JiraImportData, State } from "../../../../../store/type";
import { createStoreMock } from "../../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";

describe("TrackerFromJira", () => {
    let wrapper: Wrapper<TrackerFromJira>;

    beforeEach(async () => {
        const state = {
            from_jira_data: {
                credentials: {
                    server_url: "https://example.com",
                    user_email: "user-email@example.com",
                    token: "azerty1234",
                } as Credentials,
            } as JiraImportData,
        } as State;

        wrapper = shallowMount(TrackerFromJira, {
            localVue: await createTrackerCreationLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state,
                }),
            },
        });
    });

    it("renders the component", () => {
        expect(wrapper.element).toMatchSnapshot();
    });

    it("load the project list", async () => {
        const credentials = {
            server_url: "https://example.com",
            user_email: "user-email@example.com",
            token: "azerty1234",
        } as Credentials;

        wrapper.vm.$data.credentials = credentials;
        await wrapper.vm.$nextTick();

        const button = wrapper.find("[data-test=create-from-jira]");

        button.trigger("click");

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("getJiraProjectList", credentials);
        expect(wrapper.vm.$data.is_connection_valid).toBe(true);
        expect(wrapper.vm.$data.error_message).toBe("");

        expect(wrapper.find("[data-test=jira-fail-load-project]").exists()).toBe(false);
    });

    it("display the error message", async () => {
        wrapper.vm.$data.error_message = "Oh snap!";

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=jira-fail-load-project]").exists()).toBe(true);
    });
});

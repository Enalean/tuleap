/*
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

import { nextTick } from "vue";
import { flushPromises, shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { Fault } from "@tuleap/fault";

import PaneGitlabServer from "./PaneGitlabServer.vue";
import * as gitlab_api_querier from "../api/gitlab-api-querier";
import { getGlobalTestOptions } from "../tests/helpers/global-options-for-tests";
import { GitlabApiQuerierStub } from "../tests/stubs/GitlabApiQuerierStub";
import { useGitLabGroupsStore } from "../stores/groups";
import type { GitlabGroup } from "../stores/types";

const server_url = "https://example.com";
const token = "glpat-a1e2i3o4u5y6";

function getWrapper(): VueWrapper<InstanceType<typeof PaneGitlabServer>> {
    return shallowMount(PaneGitlabServer, {
        global: {
            stubs: ["router-link"],
            ...getGlobalTestOptions(),
        },
    });
}

function getFetchGroupsButton(
    wrapper: VueWrapper<InstanceType<typeof PaneGitlabServer>>
): HTMLButtonElement {
    const fetch_groups_button = wrapper.get("[data-test=gitlab-fetch-groups-button]").element;
    if (!(fetch_groups_button instanceof HTMLButtonElement)) {
        throw new Error("Could not find the fetch groups button");
    }
    return fetch_groups_button;
}

describe("PaneGitlabServer", () => {
    it("should fetch the gitlab groups with the credentials provided by the user and store them", async () => {
        const groups = [
            {
                id: "818532",
                name: "R&D fellows",
            } as GitlabGroup,
            {
                id: "984142",
                name: "QA folks",
            } as GitlabGroup,
        ];

        const querier = GitlabApiQuerierStub.withGitlabGroups(groups);
        jest.spyOn(gitlab_api_querier, "createGitlabApiQuerier").mockReturnValue(querier);

        const wrapper = getWrapper();
        const store = useGitLabGroupsStore();

        wrapper.get("[data-test=gitlab-server-url]").setValue(server_url);
        wrapper.get("[data-test=gitlab-access-token]").setValue(token);

        await nextTick();

        getFetchGroupsButton(wrapper).click();

        await flushPromises();

        expect(querier.getUsedCredentials()).toStrictEqual({
            server_url: new URL(server_url),
            token,
        });
        expect(store.setGroups).toHaveBeenCalledWith(groups);
    });

    it("should display an error when a fault is detected", async () => {
        const querier = GitlabApiQuerierStub.withFault(Fault.fromMessage("Nope"));

        jest.spyOn(gitlab_api_querier, "createGitlabApiQuerier").mockReturnValue(querier);

        const wrapper = getWrapper();

        wrapper.get("[data-test=gitlab-server-url]").setValue(server_url);
        wrapper.get("[data-test=gitlab-access-token]").setValue(token);

        await nextTick();

        getFetchGroupsButton(wrapper).click();

        await flushPromises();

        const alert = wrapper.find("[data-test=gitlab-server-fetch-error]");
        expect(alert.exists()).toBe(true);
        expect(alert.element.textContent).toContain("Nope");
    });

    it("should not try to retrieve the groups when the form is not valid", async () => {
        const wrapper = getWrapper();

        const fetcher = GitlabApiQuerierStub.withGitlabGroups([]);
        jest.spyOn(gitlab_api_querier, "createGitlabApiQuerier").mockReturnValue(fetcher);

        wrapper.get("[data-test=gitlab-server-url]").setValue("not a url");
        wrapper.get("[data-test=gitlab-access-token]").setValue(token);

        await nextTick();

        getFetchGroupsButton(wrapper).click();

        expect(fetcher.getCallsNumber()).toBe(0);
    });

    it("should disable the fetch button when the form is not filled", async () => {
        const wrapper = getWrapper();

        expect(getFetchGroupsButton(wrapper).hasAttribute("disabled")).toBe(true);

        wrapper.get("[data-test=gitlab-server-url]").setValue(server_url);
        wrapper.get("[data-test=gitlab-access-token]").setValue(token);

        await nextTick();

        expect(getFetchGroupsButton(wrapper).hasAttribute("disabled")).toBe(false);
    });
});

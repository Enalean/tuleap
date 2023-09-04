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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { StateTree } from "pinia";
import { flushPromises, shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { Fault } from "@tuleap/fault";

import PaneGitlabServer from "./PaneGitlabServer.vue";
import * as gitlab_api_querier from "../api/gitlab-api-querier";
import { getGlobalTestOptions } from "../tests/helpers/global-options-for-tests";
import { GitlabApiQuerierStub } from "../tests/stubs/GitlabApiQuerierStub";
import { useGitLabGroupsStore } from "../stores/groups";
import type { GitlabGroup } from "../stores/types";
import * as router from "vue-router";
import type { Router } from "vue-router";
import type { GitlabGroupLinkStepName } from "../types";
import { STEP_GITLAB_GROUP } from "../types";
import { useCredentialsStore } from "../stores/credentials";

vi.mock("vue-router");

const server_url = "https://example.com/";
const token = "glpat-a1e2i3o4u5y6";

function getWrapper(state: StateTree = {}): VueWrapper<InstanceType<typeof PaneGitlabServer>> {
    return shallowMount(PaneGitlabServer, {
        global: {
            ...getGlobalTestOptions(state),
        },
    });
}

function getFetchGroupsButton(
    wrapper: VueWrapper<InstanceType<typeof PaneGitlabServer>>,
): HTMLButtonElement {
    const fetch_groups_button = wrapper.get("[data-test=gitlab-fetch-groups-button]").element;
    if (!(fetch_groups_button instanceof HTMLButtonElement)) {
        throw new Error("Could not find the fetch groups button");
    }
    return fetch_groups_button;
}

describe("PaneGitlabServer", () => {
    let push_route_spy: (to: { name: GitlabGroupLinkStepName }) => void;

    beforeEach(() => {
        push_route_spy = vi.fn();
        vi.spyOn(router, "useRouter").mockReturnValue({
            push: push_route_spy,
        } as unknown as Router);
    });

    it("should fetch the gitlab groups with the credentials provided by the user, store them and navigate to the next step", async () => {
        const groups = [
            {
                id: 818532,
                name: "R&D fellows",
            } as GitlabGroup,
            {
                id: 984142,
                name: "QA folks",
            } as GitlabGroup,
        ];

        const querier = GitlabApiQuerierStub.withGitlabGroups(groups);
        vi.spyOn(gitlab_api_querier, "createGitlabApiQuerier").mockReturnValue(querier);

        const wrapper = getWrapper();
        const groups_store = useGitLabGroupsStore();
        const credentials_store = useCredentialsStore();
        const expected_credentials = {
            server_url: new URL(server_url),
            token,
        };

        await wrapper.get("[data-test=gitlab-server-url]").setValue(server_url);
        await wrapper.get("[data-test=gitlab-access-token]").setValue(token);

        getFetchGroupsButton(wrapper).click();

        await flushPromises();
        expect(querier.getUsedCredentials()).toStrictEqual(expected_credentials);
        expect(credentials_store.setCredentials).toHaveBeenCalledWith(expected_credentials);
        expect(groups_store.setGroups).toHaveBeenCalledWith(groups);
        expect(push_route_spy).toHaveBeenCalledWith({ name: STEP_GITLAB_GROUP });
    });

    it("should display an error when a fault is detected", async () => {
        const querier = GitlabApiQuerierStub.withFault(Fault.fromMessage("Nope"));

        vi.spyOn(gitlab_api_querier, "createGitlabApiQuerier").mockReturnValue(querier);

        const wrapper = getWrapper();

        await wrapper.get("[data-test=gitlab-server-url]").setValue(server_url);
        await wrapper.get("[data-test=gitlab-access-token]").setValue(token);

        getFetchGroupsButton(wrapper).click();

        await flushPromises();

        const alert = wrapper.find("[data-test=gitlab-server-fetch-error]");
        expect(alert.exists()).toBe(true);
        expect(alert.element.textContent).toContain("Nope");
    });

    it("should not try to retrieve the groups when the form is not valid", async () => {
        const wrapper = getWrapper();

        const fetcher = GitlabApiQuerierStub.withGitlabGroups([]);
        vi.spyOn(gitlab_api_querier, "createGitlabApiQuerier").mockReturnValue(fetcher);

        await wrapper.get("[data-test=gitlab-server-url]").setValue("not a url");
        await wrapper.get("[data-test=gitlab-access-token]").setValue(token);

        getFetchGroupsButton(wrapper).click();

        expect(fetcher.getCallsNumber()).toBe(0);
    });

    it("should disable the fetch button when the form is not filled", async () => {
        const wrapper = getWrapper();

        expect(getFetchGroupsButton(wrapper).hasAttribute("disabled")).toBe(true);

        await wrapper.get("[data-test=gitlab-server-url]").setValue(server_url);
        await wrapper.get("[data-test=gitlab-access-token]").setValue(token);

        expect(getFetchGroupsButton(wrapper).hasAttribute("disabled")).toBe(false);
    });

    describe("stores", () => {
        let wrapper: VueWrapper<InstanceType<typeof PaneGitlabServer>>;

        beforeEach(() => {
            const group_1 = {
                id: 818532,
                name: "R&D fellows",
            } as GitlabGroup;
            wrapper = getWrapper({
                credentials: {
                    credentials: {
                        server_url: new URL(server_url),
                        token,
                    },
                },
                groups: {
                    groups: [
                        group_1,
                        {
                            id: 984142,
                            name: "QA folks",
                        } as GitlabGroup,
                    ],
                    selected_group: group_1,
                },
            });
        });

        it("should fill the server_url and the token if they are defined in store during setup", () => {
            expect(
                wrapper.get<HTMLInputElement>("[data-test=gitlab-server-url]").element.value,
            ).toStrictEqual(server_url);
            expect(
                wrapper.get<HTMLInputElement>("[data-test=gitlab-access-token]").element.value,
            ).toStrictEqual(token);
        });

        it("should reset the group store and the credentials store when user cancels", () => {
            const groups_store = useGitLabGroupsStore();
            const credentials_store = useCredentialsStore();

            wrapper
                .get<HTMLButtonElement>("[data-test=gitlab-group-link-cancel-button]")
                .element.click();

            expect(groups_store.groups).toHaveLength(0);
            expect(groups_store.selected_group).toBeNull();
            expect(credentials_store.credentials).toStrictEqual({
                server_url: "",
                token: "",
            });
        });
    });
});

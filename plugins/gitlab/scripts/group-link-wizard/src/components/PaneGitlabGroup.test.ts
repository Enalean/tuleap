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
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import type { Router } from "vue-router";
import * as router from "vue-router";

import PaneGitlabGroup from "./PaneGitlabGroup.vue";
import { getGlobalTestOptions } from "../tests/helpers/global-options-for-tests";
import { useGitLabGroupsStore } from "../stores/groups";
import type { GitlabGroupLinkStepName } from "../types";
import { STEP_GITLAB_CONFIGURATION } from "../types";
import type { GroupsState } from "../stores/types";

vi.mock("vue-router");

function getWrapper(groups_state: GroupsState): VueWrapper {
    return shallowMount(PaneGitlabGroup, {
        global: {
            stubs: ["router-link"],
            ...getGlobalTestOptions({
                groups: groups_state,
            }),
        },
    });
}

const group_1 = {
    id: 818532,
    name: "R&D fellows",
    full_path: "r-and-d-fellows",
    avatar_url: "some/url/to/r-and-d-fellows/avatar",
};

describe("PaneGitlabGroup", () => {
    let push_route_spy: (to: { name: GitlabGroupLinkStepName }) => void;

    beforeEach(() => {
        push_route_spy = vi.fn();
        vi.spyOn(router, "useRouter").mockReturnValue({
            push: push_route_spy,
        } as unknown as Router);
    });

    it("should display the group that have been previously fetched with their avatars if they have one", () => {
        const wrapper = getWrapper({
            groups: [
                group_1,
                {
                    id: 984142,
                    name: "QA folks",
                    full_path: "qa-folks",
                    avatar_url: null,
                },
            ],
            selected_group: null,
        });

        const groups_in_table = wrapper.findAll("[data-test=gitlab-group-row]");

        expect(groups_in_table).toHaveLength(2);

        const [group_1_row, group_2_row] = groups_in_table;

        const group_1_avatar = group_1_row.get("[data-test=gitlab-group-avatar]");
        const group_1_name = group_1_row.get("[data-test=gitlab-group-name]");

        expect(group_1_avatar.element).toBeInstanceOf(HTMLImageElement);
        expect(group_1_avatar.attributes().src).toBe("some/url/to/r-and-d-fellows/avatar");
        expect(group_1_avatar.attributes().alt).toBe("r-and-d-fellows");

        expect(group_1_name.element.textContent?.trim()).toBe("R&D fellows  (r-and-d-fellows)");

        const group_2_avatar = group_2_row.get("[data-test=gitlab-group-avatar]");
        const group_2_name = group_2_row.get("[data-test=gitlab-group-name]");

        expect(group_2_avatar.element).toBeInstanceOf(HTMLDivElement);
        expect(group_2_avatar.classes()).toContain("default-gitlab-group-avatar");
        expect(group_2_avatar.element.textContent).toBe("Q");

        expect(group_2_name.element.textContent?.trim()).toBe("QA folks  (qa-folks)");
    });

    it("should display an empty state if there are no groups to display", () => {
        const wrapper = getWrapper({
            groups: [],
            selected_group: null,
        });

        expect(wrapper.find("[data-test=gitlab-group-empty-state]")).toBeDefined();
    });

    it(`When user submits
        Then the selected group is saved
        And the user is redirected to the configuration step`, async () => {
        const wrapper = getWrapper({
            groups: [group_1],
            selected_group: null,
        });

        const groups_store = useGitLabGroupsStore();
        const group_1_row = wrapper.get("[data-test=gitlab-group-row]");

        await group_1_row.get("[data-test=gitlab-select-group-radio-button]").setValue(true);

        wrapper
            .get<HTMLButtonElement>("[data-test=gitlab-select-group-submit-button]")
            .element.click();

        expect(groups_store.setSelectedGroup).toHaveBeenCalledWith(group_1);
        expect(push_route_spy).toHaveBeenCalledWith({ name: STEP_GITLAB_CONFIGURATION });
    });

    it("When a group is already selected in store during setup, Then it should set it as selected in the table", () => {
        const wrapper = getWrapper({
            groups: [group_1],
            selected_group: group_1,
        });

        const group_1_radio_button = wrapper
            .get("[data-test=gitlab-group-row]")
            .get<HTMLInputElement>("[data-test=gitlab-select-group-radio-button]");

        expect(group_1_radio_button.element.checked).toBe(true);
    });
});

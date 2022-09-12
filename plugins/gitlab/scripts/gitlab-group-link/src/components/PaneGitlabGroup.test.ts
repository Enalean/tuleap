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

import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import PaneGitlabGroup from "./PaneGitlabGroup.vue";
import { getGlobalTestOptions } from "../tests/helpers/global-options-for-tests";
import type { GitlabGroup } from "../stores/types";

function getWrapper(groups: GitlabGroup[]): VueWrapper<InstanceType<typeof PaneGitlabGroup>> {
    return shallowMount(PaneGitlabGroup, {
        global: {
            stubs: ["router-link"],
            ...getGlobalTestOptions({
                groups: {
                    groups,
                },
            }),
        },
    });
}

describe("PaneGitlabGroup", () => {
    it("should display the group that have been previously fetched with their avatars if they have one", () => {
        const wrapper = getWrapper([
            {
                id: "818532",
                name: "R&D fellows",
                full_path: "r-and-d-fellows",
                avatar_url: "some/url/to/r-and-d-fellows/avatar",
            },
            {
                id: "984142",
                name: "QA folks",
                full_path: "qa-folks",
                avatar_url: null,
            },
        ]);

        const groups_in_table = wrapper.findAll("[data-test=gitlab-group-row]");

        expect(groups_in_table).toHaveLength(2);

        const [group_1, group_2] = groups_in_table;

        const group_1_avatar = group_1.get("[data-test=gitlab-group-avatar]");
        const group_1_name = group_1.get("[data-test=gitlab-group-name]");

        expect(group_1_avatar.element).toBeInstanceOf(HTMLImageElement);
        expect(group_1_avatar.attributes().src).toBe("some/url/to/r-and-d-fellows/avatar");
        expect(group_1_avatar.attributes().alt).toBe("r-and-d-fellows");

        expect(group_1_name.element.textContent?.trim()).toBe("R&D fellows  (r-and-d-fellows)");

        const group_2_avatar = group_2.get("[data-test=gitlab-group-avatar]");
        const group_2_name = group_2.get("[data-test=gitlab-group-name]");

        expect(group_2_avatar.element).toBeInstanceOf(HTMLDivElement);
        expect(group_2_avatar.classes()).toContain("default-gitlab-group-avatar");
        expect(group_2_avatar.element.textContent).toBe("Q");

        expect(group_2_name.element.textContent?.trim()).toBe("QA folks  (qa-folks)");
    });

    it("should display an empty state if there are no groups to display", () => {
        const wrapper = getWrapper([]);

        expect(wrapper.find("[data-test=gitlab-group-empty-state]")).toBeDefined();
    });
});

/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
import BranchesSection from "./BranchesSection.vue";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import type { Tag } from "../type";
import { createBranchTagSelectorLocalVue } from "../helpers/local-vue-for-test";

jest.useFakeTimers();

describe("TagsSection", () => {
    it("Displays two tags referencing the same commit", async () => {
        const tlpRecursiveGetMock = jest.spyOn(tlp_fetch, "recursiveGet");
        const commit = { id: "commitref" };
        const branches: Array<Tag> = [
            { name: "v1.0.0", commit },
            { name: "v1.0.0RC3", commit },
        ];
        tlpRecursiveGetMock.mockResolvedValue(branches);

        const wrapper = shallowMount(BranchesSection, {
            localVue: await createBranchTagSelectorLocalVue(),
            propsData: {
                repository_id: 1,
                repository_url: "https://example.com/repo/",
                repository_default_branch: "main",
                is_displaying_branches: true,
                is_tag: false,
                current_ref_name: "main",
            },
        });

        await jest.runOnlyPendingTimersAsync();

        expect(wrapper).toMatchInlineSnapshot(`
            <section class="git-repository-branch-tag-selector-refs">
              <!---->
              <div class="git-repository-branch-tag-selector-filter"><input type="search" placeholder="Branch name" class="tlp-search tlp-search-small" value=""></div> <a href="https://example.com/repo/?hb=v1.0.0" role="menuitem" class="tlp-dropdown-menu-item">
                <!---->
                v1.0.0
                <!----></a><a href="https://example.com/repo/?hb=v1.0.0RC3" role="menuitem" class="tlp-dropdown-menu-item">
                <!---->
                v1.0.0RC3
                <!----></a>
              <!---->
              <!---->
              <!---->
            </section>
        `);
    });
});

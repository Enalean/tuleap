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
import type { Branch } from "../type";
import { createBranchTagSelectorLocalVue } from "../helpers/local-vue-for-test";

describe("BranchesSection", () => {
    it("Displays two branches referencing the same commit", async () => {
        const tlpRecursiveGetMock = jest.spyOn(tlp_fetch, "recursiveGet");
        const commit = { id: "commitref" };
        const branches: Array<Branch> = [
            { name: "main", commit },
            { name: "develop", commit },
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

        await wrapper.vm.$nextTick(); // Init the component & load the branches
        await wrapper.vm.$nextTick(); // Display the loaded branches

        expect(wrapper).toMatchInlineSnapshot(`
            <section class="git-repository-branch-tag-selector-refs">
              <!---->
              <div class="git-repository-branch-tag-selector-filter"><input type="search" placeholder="Branch name" class="tlp-search tlp-search-small" value=""></div> <a href="https://example.com/repo/?hb=develop" role="menuitem" class="tlp-dropdown-menu-item">
                <!---->
                develop
                <!----></a><a href="https://example.com/repo/?hb=main" role="menuitem" class="tlp-dropdown-menu-item"><i class="fa fa-check fa-fw tlp-dropdown-menu-item-icon"></i>
                main
                <span class="tlp-badge-secondary tlp-badge-outline" data-msgid="
                        default
                    " data-current-language="en_US">
                        default
                    </span></a>
              <!---->
              <!---->
              <!---->
            </section>
        `);
    });
});

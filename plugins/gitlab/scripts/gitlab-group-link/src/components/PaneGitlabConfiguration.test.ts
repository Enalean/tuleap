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
import PaneGitlabConfiguration from "./PaneGitlabConfiguration.vue";
import { getGlobalTestOptions } from "../tests/helpers/global-options-for-tests";

describe("PaneGitlabConfiguration", () => {
    it(`When the "Prefix the branch name" option is selected,
        Then it should enable the "Prefix" input,
        make it required
        and disable the submit button until the input is filled`, async () => {
        const wrapper = shallowMount(PaneGitlabConfiguration, {
            global: {
                stubs: ["router-link"],
                ...getGlobalTestOptions(),
            },
        });

        const branch_prefix_form_element = wrapper.get(
            "[data-test=branch-name-prefix-form-element]"
        );
        const branch_prefix_input = wrapper.get<HTMLInputElement>(
            "[data-test=branch-name-prefix-input]"
        );
        const submit_button = wrapper.get<HTMLButtonElement>(
            "[data-test=gitlab-configuration-submit-button]"
        );

        expect(submit_button.element.disabled).toBe(false);
        expect(branch_prefix_input.element.required).toBe(false);
        expect(branch_prefix_form_element.classes()).toContain("tlp-form-element-disabled");
        expect(wrapper.find("[data-test=branch-name-prefix-required-flag]").exists()).toBe(false);

        await wrapper.get("[data-test=checkbox-prefix-branch-name]").setValue(true);

        expect(submit_button.element.disabled).toBe(true);
        expect(branch_prefix_input.element.required).toBe(true);
        expect(branch_prefix_form_element.classes()).not.toContain("tlp-form-element-disabled");
        expect(wrapper.find("[data-test=branch-name-prefix-required-flag]").exists()).toBe(true);

        await branch_prefix_input.setValue("my-prefix");

        expect(submit_button.element.disabled).toBe(false);
    });
});

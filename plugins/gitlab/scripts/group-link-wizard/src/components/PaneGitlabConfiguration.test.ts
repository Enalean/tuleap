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
import { shallowMount, flushPromises } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import PaneGitlabConfiguration from "./PaneGitlabConfiguration.vue";
import { getGlobalTestOptions } from "../tests/helpers/global-options-for-tests";
import * as tuleap_api from "../api/tuleap-api-querier";

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
            "[data-test=branch-name-prefix-form-element]",
        );
        const branch_prefix_input = wrapper.get<HTMLInputElement>(
            "[data-test=branch-name-prefix-input]",
        );
        const submit_button = wrapper.get<HTMLButtonElement>(
            "[data-test=gitlab-configuration-submit-button]",
        );

        expect(submit_button.element.disabled).toBe(false);
        expect(branch_prefix_input.element.required).toBe(false);
        expect(branch_prefix_input.element.disabled).toBe(true);
        expect(branch_prefix_form_element.classes()).toContain("tlp-form-element-disabled");
        expect(wrapper.find("[data-test=branch-name-prefix-required-flag]").exists()).toBe(false);

        await wrapper.get("[data-test=checkbox-prefix-branch-name]").setValue(true);

        expect(submit_button.element.disabled).toBe(true);
        expect(branch_prefix_input.element.required).toBe(true);
        expect(branch_prefix_input.element.disabled).toBe(false);
        expect(branch_prefix_form_element.classes()).not.toContain("tlp-form-element-disabled");
        expect(wrapper.find("[data-test=branch-name-prefix-required-flag]").exists()).toBe(true);

        await branch_prefix_input.setValue("my-prefix");

        expect(submit_button.element.disabled).toBe(false);
    });

    describe("When user submits", () => {
        let wrapper: VueWrapper<InstanceType<typeof PaneGitlabConfiguration>>;

        beforeEach(() => {
            wrapper = shallowMount(PaneGitlabConfiguration, {
                global: {
                    stubs: ["router-link"],
                    ...getGlobalTestOptions({
                        root: {
                            current_project: {
                                id: 101,
                            },
                        },
                        credentials: {
                            credentials: {
                                server_url: new URL("https://example.com/"),
                                token: "a1e2i3o4u5y6",
                            },
                        },
                        groups: {
                            selected_group: {
                                id: 7894568453,
                            },
                        },
                    }),
                },
            });
        });

        it(`should call the tuleap api with all the information needed
            and should keep the button disabled with a spinner
            to prevent user from triggering create again while the page is reloading`, async () => {
            const create_link_spy = vi
                .spyOn(tuleap_api, "linkGitlabGroupWithTuleap")
                .mockReturnValue(okAsync(undefined));

            await wrapper.get("[data-test=checkbox-prefix-branch-name]").setValue(true);
            await wrapper.get("[data-test=branch-name-prefix-input]").setValue("my-prefix");

            const button = wrapper.get<HTMLButtonElement>(
                "[data-test=gitlab-configuration-submit-button]",
            );
            button.element.click();

            await flushPromises();

            expect(create_link_spy).toHaveBeenCalledWith(
                101,
                7894568453,
                "https://example.com/",
                "a1e2i3o4u5y6",
                "my-prefix",
                false,
            );
            expect(button.element.disabled).toBe(true);
        });

        it("When an error occurs, Then it should display an error message", async () => {
            vi.spyOn(tuleap_api, "linkGitlabGroupWithTuleap").mockReturnValue(
                errAsync(Fault.fromMessage("some-reason")),
            );

            wrapper
                .get<HTMLButtonElement>("[data-test=gitlab-configuration-submit-button]")
                .element.click();

            await flushPromises();

            const error_message = wrapper.get("[data-test=gitlab-configuration-save-error]");
            expect(error_message.text()).toContain("some-reason");
        });
    });
});

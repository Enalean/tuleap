/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import ModalContent from "./ModalContent.vue";
import type { VueWrapper } from "@vue/test-utils";
import { flushPromises, shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "./global-options-for-test";
import type { GitRepository } from "../types";
import * as rest_queries from "../../api/rest_querier";
import { okAsync } from "neverthrow";
import type { GitCreateBranchResponse } from "../../api/rest_querier";

describe("ModalContent", () => {
    let all_repositories: ReadonlyArray<GitRepository>;

    beforeEach(() => {
        all_repositories = [
            {
                id: 4,
                name: "repo01",
                default_branch: "main",
                html_url: "repo_url",
            },
            {
                id: 5,
                name: "repo02",
                default_branch: "other_branch",
                html_url: "repo02_url",
            },
        ];
    });

    function mountComponent(
        are_pullrequests_available: boolean,
    ): VueWrapper<InstanceType<typeof ModalContent>> {
        return shallowMount(ModalContent, {
            global: getGlobalTestOptions(),
            props: {
                repositories: all_repositories,
                branch_name_preview: "tuleap-123-artifact-title",
                are_pullrequest_endpoints_available: are_pullrequests_available,
            },
        });
    }

    it("asks to create a Git branch and a PR", async () => {
        const create_git_branch = vi.spyOn(rest_queries, "postGitBranch").mockReturnValue(
            okAsync({
                html_url: "branch_url",
            } as GitCreateBranchResponse),
        );

        const create_pull_request = vi
            .spyOn(rest_queries, "postPullRequestOnDefaultBranch")
            .mockReturnValue(
                okAsync({
                    json: () => Promise.resolve({ id: 123 }),
                } as unknown as Response),
            );

        const wrapper = mountComponent(true);

        expect(
            (wrapper.get("[data-test=create-pr-checkbox]").element as HTMLInputElement).checked,
        ).toBe(true);

        const submit_button = wrapper.find("[data-test=create-branch-submit-button]")
            .element as HTMLButtonElement;
        if (!(submit_button instanceof HTMLButtonElement)) {
            throw new Error("Could not find the submit button");
        }

        submit_button.click();

        expect(create_git_branch).toHaveBeenCalled();
        await flushPromises();
        expect(create_pull_request).toHaveBeenCalled();
    });
    it("asks to create only a Git branch", () => {
        const create_git_branch = vi.spyOn(rest_queries, "postGitBranch").mockReturnValue(
            okAsync({
                html_url: "branch_url",
            } as GitCreateBranchResponse),
        );

        const wrapper = mountComponent(true);

        wrapper.get("[data-test=create-pr-checkbox]").setValue(false);

        const submit_button = wrapper.find("[data-test=create-branch-submit-button]")
            .element as HTMLButtonElement;
        if (!(submit_button instanceof HTMLButtonElement)) {
            throw new Error("Could not find the submit button");
        }

        submit_button.click();

        expect(create_git_branch).toHaveBeenCalled();
    });
    it("asks to create only a Git branch when pull requests are not available", () => {
        const create_git_branch = vi.spyOn(rest_queries, "postGitBranch").mockReturnValue(
            okAsync({
                html_url: "branch_url",
            } as GitCreateBranchResponse),
        );

        const wrapper = mountComponent(false);

        const submit_button = wrapper.find("[data-test=create-branch-submit-button]")
            .element as HTMLButtonElement;
        if (!(submit_button instanceof HTMLButtonElement)) {
            throw new Error("Could not find the submit button");
        }

        submit_button.click();

        expect(create_git_branch).toHaveBeenCalled();
        expect(wrapper.find("[data-test=create-pr-checkbox]").exists()).toBe(false);
    });
    it("updates the reference when changing the repository", async () => {
        const wrapper = mountComponent(true);

        await flushPromises();
        const reference_input = wrapper.find("[data-test=branch-reference-input]")
            .element as HTMLInputElement;
        if (!(reference_input instanceof HTMLInputElement)) {
            throw new Error("Could not find the reference input");
        }

        expect(reference_input.value).toBe("main");

        const repositories_select = wrapper.find("[data-test=repositories-select]")
            .element as HTMLSelectElement;
        if (!(repositories_select instanceof HTMLSelectElement)) {
            throw new Error("Could not find the repositories select");
        }

        repositories_select.selectedIndex = 1;
        repositories_select.dispatchEvent(new Event("change"));

        await flushPromises();
        expect(reference_input.value).toBe("other_branch");
    });
});

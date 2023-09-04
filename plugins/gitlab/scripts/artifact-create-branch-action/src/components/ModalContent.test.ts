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
import * as rest_queries from "../api/rest-querier";
import { okAsync } from "neverthrow";
import type { GitlabIntegrationWithDefaultBranch } from "../fetch-gitlab-repositories-information";
import type { GitLabIntegrationCreatedBranchInformation } from "../api/rest-querier";

describe("ModalContent", () => {
    let all_integrations: ReadonlyArray<GitlabIntegrationWithDefaultBranch>;

    beforeEach(() => {
        all_integrations = [
            {
                gitlab_repository_url: "gitlab_url_01",
                id: 4,
                name: "repo01",
                default_branch: "main",
                create_branch_prefix: "prefix01/",
            },
            {
                gitlab_repository_url: "gitlab_url_02",
                create_branch_prefix: "",
                id: 5,
                name: "repo02",
                default_branch: "other_branch",
            },
        ];
    });

    function mountComponent(): VueWrapper<InstanceType<typeof ModalContent>> {
        return shallowMount(ModalContent, {
            global: getGlobalTestOptions(),
            props: {
                integrations: all_integrations,
                branch_name: "tuleap-123-artifact-title",
                artifact_id: 123,
            },
        });
    }

    it("asks to create a GitLab branch and the associated merge request", async () => {
        const create_gitlab_branch = vi.spyOn(rest_queries, "postGitlabBranch").mockReturnValue(
            okAsync({
                branch_name: "prefix01/tuleap-123-artifact-title",
            } as GitLabIntegrationCreatedBranchInformation),
        );

        const create_pull_request = vi
            .spyOn(rest_queries, "postGitlabMergeRequest")
            .mockReturnValue(okAsync(undefined));

        const wrapper = mountComponent();

        expect(
            (wrapper.get("[data-test=create-merge-request-checkbox]").element as HTMLInputElement)
                .checked,
        ).toBe(true);

        const submit_button = wrapper.find("[data-test=create-branch-submit-button]")
            .element as HTMLButtonElement;
        if (!(submit_button instanceof HTMLButtonElement)) {
            throw new Error("Could not find the submit button");
        }

        submit_button.click();

        expect(create_gitlab_branch).toHaveBeenCalled();
        await flushPromises();
        expect(create_pull_request).toHaveBeenCalled();
    });

    it("asks to create only a GitLab branch", () => {
        const create_gitlab_branch = vi.spyOn(rest_queries, "postGitlabBranch").mockReturnValue(
            okAsync({
                branch_name: "prefix01/tuleap-123-artifact-title",
            } as GitLabIntegrationCreatedBranchInformation),
        );

        const wrapper = mountComponent();

        wrapper.get("[data-test=create-merge-request-checkbox]").setValue(false);

        const submit_button = wrapper.find("[data-test=create-branch-submit-button]")
            .element as HTMLButtonElement;
        if (!(submit_button instanceof HTMLButtonElement)) {
            throw new Error("Could not find the submit button");
        }

        submit_button.click();

        expect(create_gitlab_branch).toHaveBeenCalled();
    });
    it("updates the reference when changing the integration", async () => {
        const wrapper = mountComponent();

        await flushPromises();
        const reference_input = wrapper.find("[data-test=branch-reference-input]")
            .element as HTMLInputElement;
        if (!(reference_input instanceof HTMLInputElement)) {
            throw new Error("Could not find the reference input");
        }

        expect(reference_input.value).toBe("main");

        const repositories_select = wrapper.find("[data-test=integrations-select]")
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

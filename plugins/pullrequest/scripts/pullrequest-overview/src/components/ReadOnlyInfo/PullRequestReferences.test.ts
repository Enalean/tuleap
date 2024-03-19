/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, beforeEach, it, expect, vi } from "vitest";
import { mount, shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { PullRequestStub } from "@tuleap/plugin-pullrequest-stub";
import type { PullRequest, PullRequestRepository } from "@tuleap/plugin-pullrequest-rest-api-types";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import PullRequestReferences from "./PullRequestReferences.vue";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { CURRENT_REPOSITORY_ID } from "../../constants";

vi.mock("@tuleap/vue-strict-inject");

const current_repository_id = 5;

describe("PullRequestReferences", () => {
    beforeEach(() => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key) => {
            if (key !== CURRENT_REPOSITORY_ID) {
                throw new Error("Tried to strictInject a value while it was not mocked");
            }

            return current_repository_id;
        });
    });

    it("should display a skeleton while the pull request is loading, and the references when finished", async () => {
        const wrapper = mount(PullRequestReferences, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                pull_request_info: null,
            },
        });

        expect(wrapper.findAll("[data-test=pullrequest-property-skeleton]")).toHaveLength(2);
        expect(wrapper.find("[data-test=pullrequest-source-reference]").exists()).toBe(false);
        expect(wrapper.find("[data-test=pull-request-source-destination]").exists()).toBe(false);

        const repository = {
            id: current_repository_id,
        } as PullRequestRepository;

        const pull_request_info = PullRequestStub.buildOpenPullRequest({
            reference_src: "a1e2i3o4u5y6",
            branch_src: "vowels-and-numbers",
            branch_dest: "master",
            repository,
            repository_dest: repository,
        });

        await wrapper.setProps({
            pull_request_info,
        });

        const source_reference = wrapper.find("[data-test=pullrequest-source-reference]");

        expect(wrapper.findAll("[data-test=pullrequest-property-skeleton]")).toHaveLength(0);
        expect(source_reference.exists()).toBe(true);
        expect(source_reference.text()).toBe("a1e2i3o4u5y6");
        expect(wrapper.find("[data-test=pull-request-source-destination]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pull-request-source-branch]").text()).toBe(
            pull_request_info.branch_src,
        );
        expect(wrapper.find("[data-test=pull-request-destination-branch]").text()).toBe(
            pull_request_info.branch_dest,
        );
    });

    describe("Branches references", () => {
        let pull_request_info: PullRequest;

        const getWrapper = (): VueWrapper =>
            shallowMount(PullRequestReferences, {
                global: {
                    ...getGlobalTestOptions(),
                },
                props: {
                    pull_request_info,
                },
            });

        beforeEach(() => {
            pull_request_info = PullRequestStub.buildOpenPullRequest();
        });

        it(`When the source repository is different than the current repository
            Then its source branch name should be prefixed with its source repository name`, () => {
            pull_request_info = PullRequestStub.buildOpenPullRequest({
                branch_src: "a-nice-feature",
                repository: {
                    id: 15,
                    name: "u/jdoe/cool-features",
                } as PullRequestRepository,
            });

            const source_branch = getWrapper().find("[data-test=pull-request-source-branch]");

            expect(source_branch.text()).toBe("u/jdoe/cool-features:a-nice-feature");
        });

        it(`When the source repository is the current repository
            Then its source branch name should NOT be prefixed with its source repository name`, () => {
            pull_request_info = PullRequestStub.buildOpenPullRequest({
                branch_src: "a-nice-feature",
                repository: {
                    id: current_repository_id,
                    name: "u/jdoe/cool-features",
                } as PullRequestRepository,
            });

            const source_branch = getWrapper().find("[data-test=pull-request-source-branch]");

            expect(source_branch.text()).toBe("a-nice-feature");
        });

        it(`When the destination repository is different than the current repository
            Then its destination branch name should be prefixed with its destination repository name`, () => {
            pull_request_info = PullRequestStub.buildOpenPullRequest({
                branch_dest: "master",
                repository_dest: {
                    id: 15,
                    name: "cool-features",
                } as PullRequestRepository,
            });

            const destination_branch = getWrapper().find(
                "[data-test=pull-request-destination-branch]",
            );

            expect(destination_branch.text()).toBe("cool-features:master");
        });

        it(`When the destination repository is the current repository
            Then its destination branch name should NOT be prefixed with its destination repository name`, () => {
            pull_request_info = PullRequestStub.buildOpenPullRequest({
                branch_dest: "master",
                repository_dest: {
                    id: current_repository_id,
                    name: "cool-features",
                } as PullRequestRepository,
            });

            const destination_branch = getWrapper().find(
                "[data-test=pull-request-destination-branch]",
            );

            expect(destination_branch.text()).toBe("master");
        });
    });
});

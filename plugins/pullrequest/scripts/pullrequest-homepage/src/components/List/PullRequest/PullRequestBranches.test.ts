/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import { getGlobalTestOptions } from "../../../../tests/global-options-for-tests";
import { REPOSITORY_ID } from "../../../injection-symbols";
import PullRequestBranches from "./PullRequestBranches.vue";
import type { PullRequest, PullRequestRepository } from "@tuleap/plugin-pullrequest-rest-api-types";
import { PullRequestStub } from "@tuleap/plugin-pullrequest-stub";

describe(`PullRequestBranches`, () => {
    const current_repository_id = 55;

    const getWrapper = (pull_request: PullRequest): VueWrapper =>
        shallowMount(PullRequestBranches, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [REPOSITORY_ID.valueOf()]: current_repository_id,
                },
            },
            props: { pull_request },
        });

    it(`when the source repository is not the current repository (e.g. a fork),
        then the source branch name should be prefixed with the source repository name`, () => {
        const pull_request = PullRequestStub.buildOpenPullRequest({
            branch_src: "metacoele-spodium",
            repository: { id: 461, name: "u/rtan/triazolic-hatter" } as PullRequestRepository,
        });

        const source_branch = getWrapper(pull_request).find(
            "[data-test=pull-request-source-branch]",
        );

        expect(source_branch.text()).toBe("u/rtan/triazolic-hatter:metacoele-spodium");
    });

    it(`when the source repository is the current repository
        then only the source branch name is used`, () => {
        const pull_request = PullRequestStub.buildOpenPullRequest({
            branch_src: "favous-unforkedness",
            repository: {
                id: current_repository_id,
                name: "unaiding-amain",
            } as PullRequestRepository,
        });

        const source_branch = getWrapper(pull_request).find(
            "[data-test=pull-request-source-branch]",
        );

        expect(source_branch.text()).toBe("favous-unforkedness");
    });

    it(`when the destination repository is not the current repository (e.g. from a fork to origin repo),
        then the destination branch name should be prefixed with the destination repository name`, () => {
        const pull_request = PullRequestStub.buildOpenPullRequest({
            branch_dest: "master",
            repository_dest: { id: 957, name: "pseudomonocyclic-shogaol" } as PullRequestRepository,
        });

        const destination_branch = getWrapper(pull_request).find(
            "[data-test=pull-request-destination-branch]",
        );

        expect(destination_branch.text()).toBe("pseudomonocyclic-shogaol:master");
    });

    it(`when the destination repository is the current repository
        then only the destination branch name is used`, () => {
        const pull_request = PullRequestStub.buildOpenPullRequest({
            branch_dest: "main",
            repository_dest: {
                id: current_repository_id,
                name: "varisse/zayin",
            } as PullRequestRepository,
        });

        const destination_branch = getWrapper(pull_request).find(
            "[data-test=pull-request-destination-branch]",
        );

        expect(destination_branch.text()).toBe("main");
    });
});

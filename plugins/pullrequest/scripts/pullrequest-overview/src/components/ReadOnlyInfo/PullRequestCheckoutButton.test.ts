/*
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import PullRequestCheckoutButton from "./PullRequestCheckoutButton.vue";

vi.mock("@tuleap/tlp-dropdown", () => ({
    createDropdown: (): void => {
        // do nothing
    },
}));

describe("PullRequestCheckoutButton", () => {
    it(`Should open the drop down and use the SSH checkout command by default`, () => {
        const wrapper = mount(PullRequestCheckoutButton, {
            global: {
                ...getGlobalTestOptions(),
                stubs: {
                    "copy-to-clipboard": true,
                },
            },
            props: {
                pull_request_info: {
                    user_id: 102,
                    repository_dest: {
                        clone_ssh_url: "ssh://example.com",
                        clone_http_url: "https://example.com",
                    },
                    head_reference: "refs/tlpr/7/head",
                } as PullRequest,
            },
        });

        const commands = wrapper.find("[data-test=pull-request-commands]");
        expect(commands.html()).toContain("git fetch ssh://example.com refs/tlpr/7/head");
    });

    it(`Should open the drop down and use the HTTP checkout command when SSH is not defined`, async () => {
        const wrapper = mount(PullRequestCheckoutButton, {
            global: {
                ...getGlobalTestOptions(),
                stubs: {
                    "copy-to-clipboard": true,
                },
            },
            props: {
                pull_request_info: null,
            },
        });

        await wrapper.setProps({
            pull_request_info: {
                user_id: 102,
                repository_dest: {
                    clone_http_url: "https://example.com",
                },
                head_reference: "refs/tlpr/7/head",
            } as PullRequest,
        });

        const commands = wrapper.find("[data-test=pull-request-commands]");
        expect(commands.html()).toContain("git fetch https://example.com refs/tlpr/7/head");
    });
});

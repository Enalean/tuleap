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

import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import PullRequestReferences from "./PullRequestReferences.vue";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";

describe("PullRequestReferences", () => {
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

        const pull_request_info = {
            reference_src: "a1e2i3o4u5y6",
            branch_src: "vowels-and-numbers",
            branch_dest: "master",
        } as PullRequest;

        wrapper.setProps({
            pull_request_info,
        });

        await wrapper.vm.$nextTick();

        const source_reference = wrapper.find("[data-test=pullrequest-source-reference]");
        const source_destination = wrapper.find("[data-test=pull-request-source-destination]");

        expect(wrapper.findAll("[data-test=pullrequest-property-skeleton]")).toHaveLength(0);
        expect(source_reference.exists()).toBe(true);
        expect(source_reference.text()).toBe("a1e2i3o4u5y6");
        expect(source_destination.exists()).toBe(true);
        expect(source_destination.element.textContent ?? "").toContain(
            pull_request_info.branch_src,
        );
        expect(source_destination.element.textContent ?? "").toContain(
            pull_request_info.branch_dest,
        );
    });
});

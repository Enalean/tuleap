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

import { describe, it, expect } from "vitest";
import { shallowMount } from "@vue/test-utils";
import EmptyStateNoGitlabGroupLinked from "./EmptyStateNoGitlabGroupLinked.vue";
import { getGlobalTestOptions } from "../tests/helpers/global-options-for-tests";

describe("EmptyStateNoGitlabGroupLinked", () => {
    it("should include the current project's public name in the empty-state's title", () => {
        const public_name = "Guinea Pig";
        const wrapper = shallowMount(EmptyStateNoGitlabGroupLinked, {
            global: {
                stubs: ["router-link"],
                ...getGlobalTestOptions({
                    root: {
                        current_project: {
                            public_name,
                        },
                    },
                }),
            },
        });

        expect(
            wrapper.get("[data-test=gitlab-no-group-linked-empty-state-title]").element.textContent,
        ).toContain(public_name);
    });
});

/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
import CommitShortId from "./CommitShortId.vue";
import { CommitStub } from "../../../tests/stubs/CommitStub";

describe("CommitShortId", () => {
    it("should display the 10 first characters of the commit id", () => {
        const commit_id = "d8fb8fc8e9d384402eec582fe504eae109f6fc9a";
        const wrapper = shallowMount(CommitShortId, {
            propsData: {
                commit: CommitStub.withDefaults(commit_id),
            },
        });

        expect(wrapper.find("[data-test=short-id]").text()).toBe("d8fb8fc8e9");
    });
});

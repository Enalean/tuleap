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
import CommitDate from "./CommitDate.vue";
import { CommitStub } from "../../../tests/stubs/CommitStub";
import CommitRelativeDate from "./CommitRelativeDate.vue";

describe("CommitDate", () => {
    it("should display the authored date as a relative date", () => {
        const authored_date = "2025-10-13T16:30:00+01:00";
        const wrapper = shallowMount(CommitDate, {
            propsData: {
                commit: CommitStub.withAuthoredDate(
                    "a359c79436ffd903420d2b1bfcce411dd7b397d6",
                    authored_date,
                ),
            },
        });

        expect(wrapper.findComponent(CommitRelativeDate).attributes("date")).toBe(authored_date);
    });
});

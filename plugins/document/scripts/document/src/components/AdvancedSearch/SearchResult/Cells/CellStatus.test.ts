/**
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

import { shallowMount } from "@vue/test-utils";
import CellStatus from "./CellStatus.vue";
import type { ItemSearchResult } from "../../../../type";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("CellStatus", () => {
    it.each([
        [null, ""],
        ["draft", "Draft"],
        ["approved", "Approved"],
        ["rejected", "Rejected"],
        ["none", "None"],
    ])(
        "when status is %s, displayed value should be '%s'",
        (status: string | null, expected: string) => {
            const wrapper = shallowMount(CellStatus, {
                props: {
                    item: {
                        status,
                    } as ItemSearchResult,
                },
                global: { ...getGlobalTestOptions({}) },
            });

            expect(wrapper.vm.status).toBe(expected);
        },
    );
});

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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import CellObsolescenceDate from "./CellObsolescenceDate.vue";

describe("CellObsolescenceDate", () => {
    it("should display the obsolescence date", () => {
        const wrapper = shallowMount(CellObsolescenceDate, {
            props: {
                item: {
                    obsolescence_date: "2022-01-30",
                },
            },
        });

        expect(wrapper.element).toMatchInlineSnapshot(`
            <cell-date-stub
              date="2022-01-30"
            />
        `);
    });

    it("should display a dash when the obsolescence date is null", () => {
        const wrapper = shallowMount(CellObsolescenceDate, {
            props: {
                item: {
                    obsolescence_date: null,
                },
            },
        });

        expect(wrapper.element).toMatchInlineSnapshot(`<cell-string-stub />`);
    });
});

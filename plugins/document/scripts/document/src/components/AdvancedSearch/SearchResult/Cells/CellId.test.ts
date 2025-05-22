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
import type { ItemSearchResult } from "../../../../type";
import { shallowMount } from "@vue/test-utils";
import CellId from "./CellId.vue";

describe("CellId", () => {
    it("should display the item id", () => {
        const wrapper = shallowMount(CellId, {
            props: {
                item: {
                    id: 123,
                } as ItemSearchResult,
            },
        });

        expect(wrapper.text()).toContain("123");
        expect(wrapper.classes("tlp-table-cell-numeric")).toBe(true);
        expect(wrapper.classes("document-search-result-id")).toBe(true);
    });
});

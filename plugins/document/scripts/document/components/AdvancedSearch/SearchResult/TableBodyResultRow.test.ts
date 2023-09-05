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
import TableBodyResultRow from "./TableBodyResultRow.vue";
import type { ItemSearchResult } from "../../../type";
import CellId from "./Cells/CellId.vue";
import CellTitle from "./Cells/CellTitle.vue";
import CellDescription from "./Cells/CellDescription.vue";
import CellOwner from "./Cells/CellOwner.vue";
import CellUpdateDate from "./Cells/CellUpdateDate.vue";
import CellCreateDate from "./Cells/CellCreateDate.vue";
import CellObsolescenceDate from "./Cells/CellObsolescenceDate.vue";
import CellLocation from "./Cells/CellLocation.vue";
import CellStatus from "./Cells/CellStatus.vue";
import CellCustomProperty from "./Cells/CellCustomProperty.vue";

describe("TableBodyResultRow", () => {
    it.each([
        ["id", CellId],
        ["title", CellTitle],
        ["description", CellDescription],
        ["owner", CellOwner],
        ["update_date", CellUpdateDate],
        ["create_date", CellCreateDate],
        ["obsolescence_date", CellObsolescenceDate],
        ["location", CellLocation],
        ["status", CellStatus],
        ["field_123", CellCustomProperty],
    ])(
        "when wanted column is %s then matching component should be %s",
        (name, expected_component) => {
            const wrapper = shallowMount(TableBodyResultRow, {
                props: {
                    item: {
                        id: 123,
                    } as ItemSearchResult,
                    columns: [{ name, label: "Whatever" }],
                },
            });

            expect(wrapper.findComponent(expected_component).exists()).toBe(true);
            expect(wrapper.element.children).toHaveLength(1);
        },
    );
});

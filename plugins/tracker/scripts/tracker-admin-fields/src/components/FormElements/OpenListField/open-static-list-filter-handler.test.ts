/*
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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
import { handleStaticListValueFilter } from "./open-static-list-filter-handler";
import type { GroupOfItems, LazyboxItem } from "@tuleap/lazybox";

describe("open-static-list-filter-handler", () => {
    describe("handleStaticListValueFilter", () => {
        const values: LazyboxItem[] = [
            {
                value: {
                    label: "DB5",
                    value_color: "firemist-silver",
                },
                is_disabled: false,
            },
            {
                value: {
                    label: "V8 Vantage",
                    value_color: "",
                },
                is_disabled: false,
            },
            {
                value: {
                    label: "Lagonda",
                    value_color: "",
                },
                is_disabled: false,
            },
        ];
        const group_of_items: GroupOfItems[] = [
            {
                label: "Static values",
                empty_message: "No value",
                is_loading: false,
                items: values,
                footer_message: "",
            },
        ];
        it("returns all items when the given query is empty", () => {
            const filtered_values = handleStaticListValueFilter("", values, {
                $gettext: (s: string) => s,
            });

            expect(filtered_values).toStrictEqual(group_of_items);
        });
        it("returns filtered items when the given query is empty", () => {
            const filtered_values = handleStaticListValueFilter("DB", values, {
                $gettext: (s: string) => s,
            });

            const expected_value_result = [
                {
                    value: {
                        label: "DB5",
                        value_color: "firemist-silver",
                    },
                    is_disabled: false,
                },
            ];
            const expected_group_of_value_result = [
                {
                    label: "Static values",
                    empty_message: "No value",
                    is_loading: false,
                    items: expected_value_result,
                    footer_message: "",
                },
            ];

            expect(filtered_values).toStrictEqual(expected_group_of_value_result);
        });
    });
});

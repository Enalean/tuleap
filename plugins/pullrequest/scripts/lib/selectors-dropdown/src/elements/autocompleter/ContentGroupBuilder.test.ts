/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
import { ContentGroupBuilder } from "./ContentGroupBuilder";
import type { AutocompleterConfig } from "../SelectorsDropdown";

const disabled_message = "You can select anything";
const config = {
    label: "Select some stuff",
    empty_message: "Nothing to see here",
    getDisabledMessage: () => disabled_message,
} as AutocompleterConfig;

describe("ContentGroupBuilder", () => {
    it("buildLoading() should return a new GroupOfItems with is_loading being true", () => {
        const group = ContentGroupBuilder(config).buildLoading();

        expect(group).toStrictEqual({
            items: [],
            empty_message: config.empty_message,
            label: config.label,
            footer_message: "",
            is_loading: true,
        });
    });

    it("buildWithItems() should return a new GroupOfItems containing the provided LazyboxItems", () => {
        const items = [
            { is_disabled: false, value: "Value 1" },
            { is_disabled: false, value: "Value 2" },
            { is_disabled: false, value: "Value 3" },
        ];
        const group = ContentGroupBuilder(config).buildWithItems(items);

        expect(group).toStrictEqual({
            items,
            empty_message: config.empty_message,
            label: config.label,
            footer_message: "",
            is_loading: false,
        });
    });

    it("buildEmptyAndDisabled() should return a new GroupOfItems, empty and containing the disabled_message set in the config", () => {
        const group = ContentGroupBuilder(config).buildEmptyAndDisabled();

        expect(group).toStrictEqual({
            items: [],
            empty_message: disabled_message,
            label: config.label,
            footer_message: "",
            is_loading: false,
        });
    });
});

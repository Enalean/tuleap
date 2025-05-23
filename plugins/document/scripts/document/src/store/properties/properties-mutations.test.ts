/*
 * Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { describe, expect, it } from "vitest";
import { saveProjectProperties } from "./properties-mutations";
import type { PropertiesState } from "./module";
import type { Property } from "../../type";

describe("Properties mutations", () => {
    it("loads properties and set the loaded information to true", () => {
        const properties = [
            {
                short_name: "status",
                name: "status",
                list_value: [100],
            } as unknown as Property,
        ];

        const original_properties: Array<Property> = [];

        const state: PropertiesState = {
            project_properties: original_properties,
            has_loaded_properties: false,
        };
        saveProjectProperties(state, properties);
        expect(state.project_properties).toBe(properties);
        expect(state.has_loaded_properties).toBe(true);
    });
});

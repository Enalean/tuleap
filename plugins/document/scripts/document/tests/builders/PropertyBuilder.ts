/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { ListValue, Property } from "../../src/type";

export class PropertyBuilder {
    private short_name: string = "";
    private list_value: Array<ListValue> = [];
    private type: string = "";
    private allowed_list_values: Array<ListValue> | null = null;

    constructor() {}

    public withShortName(short_name: string): this {
        this.short_name = short_name;
        return this;
    }

    public withListValue(list_value: Array<ListValue>): this {
        this.list_value = list_value;
        return this;
    }

    public withType(type: string): this {
        this.type = type;
        return this;
    }

    public withAllowedListValues(allowed_list_values: Array<ListValue>): this {
        this.allowed_list_values = allowed_list_values;
        return this;
    }

    public build(): Property {
        return {
            allowed_list_values: this.allowed_list_values,
            description: "",
            is_multiple_value_allowed: false,
            is_required: false,
            is_used: false,
            list_value: this.list_value,
            name: "",
            short_name: this.short_name,
            type: this.type,
            value: null,
        };
    }
}

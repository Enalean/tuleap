/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

import type { StaticListItem } from "@tuleap/plugin-tracker-rest-api-types";
import type { ColorName } from "@tuleap/core-constants";

export class StaticListItemTestBuilder {
    private readonly id;
    private label = "Value A";
    private value_color: ColorName | "" = "";
    private is_hidden: boolean = false;

    private constructor(id: number) {
        this.id = id;
    }

    public static aStaticListItem(id: number): StaticListItemTestBuilder {
        return new StaticListItemTestBuilder(id);
    }

    public isHidden(): StaticListItemTestBuilder {
        this.is_hidden = true;
        return this;
    }

    public withLabel(label: string): StaticListItemTestBuilder {
        this.label = label;
        return this;
    }

    public withColor(color: ColorName): StaticListItemTestBuilder {
        this.value_color = color;
        return this;
    }

    public build(): StaticListItem {
        return {
            id: this.id,
            label: this.label,
            value_color: this.value_color,
            is_hidden: this.is_hidden,
        };
    }
}

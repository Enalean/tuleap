/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";
import { ColumnDefinition } from "../../../type";

@Component
export default class HeaderCellMixin extends Vue {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    get classes(): string {
        return this.classes_as_array.join(" ");
    }

    get classes_as_array(): string[] {
        const classes = [];

        if (!this.is_rgb_color && this.column.color) {
            classes.push("tlp-swatch-" + this.column.color);
        }

        return classes;
    }

    get is_rgb_color(): boolean {
        return this.column.color.charAt(0) === "#";
    }
}

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

import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { ColumnDefinition } from "../../../../../type";

@Component
export default class ClassesForCollapsedColumnMixin extends Vue {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    get classes(): string {
        if (!this.column.is_collapsed) {
            return "";
        }

        const classes = ["taskboard-cell-collapsed"];

        if (this.column.has_hover) {
            classes.push("taskboard-cell-collapsed-hover");
        }

        return classes.join(" ");
    }
}

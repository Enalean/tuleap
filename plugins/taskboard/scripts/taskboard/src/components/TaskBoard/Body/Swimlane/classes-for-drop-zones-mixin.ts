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
import { namespace } from "vuex-class";
import { Component, Prop } from "vue-property-decorator";
import { ColumnDefinition, Swimlane } from "../../../../type";

const swimlane = namespace("swimlane");

@Component
export default class ClassesForCollapsedColumnMixin extends Vue {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @swimlane.Getter
    readonly does_cell_reject_drop!: (swimlane: Swimlane, column: ColumnDefinition) => boolean;

    get dropzone_classes(): string {
        if (this.does_cell_reject_drop(this.swimlane, this.column)) {
            return "taskboard-drop-not-accepted";
        }

        return "";
    }
}

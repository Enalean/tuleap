<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <div class="roadmap-gantt-subtask-header-error-message" v-bind:style="style">
        <i
            class="fas fa-exclamation-circle roadmap-gantt-subtask-header-error-message-icon"
            aria-hidden="true"
        ></i>
        <translate>An error occurred while retrieving the children.</translate>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Styles } from "../../../helpers/styles";
import type { ErrorRow, TaskDimension, TaskDimensionMap } from "../../../type";
import { getDimensions } from "../../../helpers/tasks-dimensions";

@Component
export default class SubtaskMessage extends Vue {
    @Prop({ required: true })
    readonly row!: ErrorRow;

    @Prop({ required: true })
    readonly dimensions_map!: TaskDimensionMap;

    get style(): string {
        const top =
            (this.dimensions.index + 1) * Styles.TASK_HEIGHT_IN_PX +
            2 * Styles.TIME_UNIT_HEIGHT_IN_PX +
            Styles.TODAY_PIN_HEAD_SIZE_IN_PX +
            1;

        return `top: ${top}px`;
    }

    get dimensions(): TaskDimension {
        return getDimensions(this.row.for_task, this.dimensions_map);
    }
}
</script>

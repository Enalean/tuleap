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
    <div v-bind:class="message_class" v-bind:style="style">
        <i
            class="fas roadmap-gantt-subtask-header-message-icon"
            v-bind:class="icon"
            aria-hidden="true"
        ></i>
        {{ message }}
        <button
            type="button"
            class="tlp-button-primary tlp-button-mini roadmap-gantt-subtask-header-message-button"
            v-if="should_button_be_displayed"
            v-on:click="userUndestandsThatThereAreNoSubtasksToBeDisplayed"
            data-test="button"
        >
            <i class="fas fa-check tlp-button-icon" aria-hidden="true"></i>
            <span>{{ $gettext("Ok, got it") }}</span>
        </button>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Styles } from "../../../helpers/styles";
import type {
    EmptySubtasksRow,
    ErrorRow,
    Row,
    Task,
    TaskDimension,
    TaskDimensionMap,
} from "../../../type";
import { getDimensions } from "../../../helpers/tasks-dimensions";
import { namespace } from "vuex-class";

const tasks = namespace("tasks");

@Component
export default class SubtaskMessage extends Vue {
    @Prop({ required: true })
    readonly row!: ErrorRow | EmptySubtasksRow;

    @Prop({ required: true })
    readonly dimensions_map!: TaskDimensionMap;

    @Prop({ required: true })
    readonly nb_iterations_ribbons!: number;

    @tasks.Mutation
    readonly removeSubtasksDisplayForTask!: (task: Task) => void;

    get message_class(): string {
        return this.isErrorRow(this.row)
            ? "roadmap-gantt-subtask-header-error-message"
            : "roadmap-gantt-subtask-header-info-message";
    }

    get style(): string {
        const top =
            (this.dimensions.index + 1) * Styles.TASK_HEIGHT_IN_PX +
            2 * Styles.TIME_UNIT_HEIGHT_IN_PX +
            Styles.TODAY_PIN_HEAD_SIZE_IN_PX +
            this.nb_iterations_ribbons * Styles.ITERATION_HEIGHT_IN_PX +
            1;

        return `top: ${top}px`;
    }

    get dimensions(): TaskDimension {
        return getDimensions(this.row.for_task, this.dimensions_map);
    }

    get icon(): string {
        return this.isErrorRow(this.row) ? "fa-exclamation-circle" : "fa-info-circle";
    }

    get message(): string {
        return this.isErrorRow(this.row)
            ? this.$gettext("An error occurred while retrieving the children.")
            : this.$gettext("Actually there isn't any children you can see here.");
    }

    get should_button_be_displayed(): boolean {
        return !this.isErrorRow(this.row);
    }

    userUndestandsThatThereAreNoSubtasksToBeDisplayed(): void {
        this.removeSubtasksDisplayForTask(this.row.for_task);
    }

    isErrorRow(row: Row): row is ErrorRow {
        return "is_error" in row && row.is_error;
    }
}
</script>

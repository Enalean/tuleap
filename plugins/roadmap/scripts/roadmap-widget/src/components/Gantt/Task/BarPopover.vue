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
    <section class="tlp-popover roadmap-gantt-task-popover" v-bind:class="popover_class" v-once>
        <div class="tlp-popover-arrow roadmap-gantt-task-popover-arrow"></div>
        <div
            class="tlp-popover-header roadmap-gantt-task-popover-header"
            v-bind:class="header_class"
        >
            <h1 class="tlp-popover-title roadmap-gantt-task-popover-title">
                <span class="roadmap-gantt-task-popover-xref">{{ task.xref }}</span>
                <span class="roadmap-gantt-task-popover-task">{{ task.title }}</span>
            </h1>
        </div>
        <div class="tlp-popover-body roadmap-gantt-task-popover-body">
            <table>
                <tbody>
                    <tr>
                        <td>
                            <p class="roadmap-gantt-task-popover-label" v-translate>Start date</p>
                        </td>
                        <td>
                            <p v-if="task.start" class="roadmap-gantt-task-popover-value">
                                {{ start_date }}
                            </p>
                            <p v-else class="roadmap-gantt-task-popover-value-undefined">
                                <translate>Undefined</translate>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p class="roadmap-gantt-task-popover-label" v-translate>End date</p>
                        </td>
                        <td>
                            <p v-if="task.end" class="roadmap-gantt-task-popover-value">
                                {{ end_date }}
                                <template v-if="is_end_date_in_error">
                                    <br />
                                    <translate class="roadmap-gantt-task-popover-value-error">
                                        End date is lesser than start date!
                                    </translate>
                                </template>
                            </p>
                            <p v-else class="roadmap-gantt-task-popover-value-undefined">
                                <translate>Undefined</translate>
                                <template v-if="is_time_period_in_error">
                                    <br />
                                    <span class="roadmap-gantt-task-popover-value-error">
                                        {{ task.time_period_error_message }}
                                    </span>
                                </template>
                            </p>
                        </td>
                    </tr>
                    <tr v-if="is_progress_in_error || percentage" data-test="progress">
                        <td>
                            <p class="roadmap-gantt-task-popover-label" v-translate>Progress</p>
                        </td>
                        <td>
                            <p
                                class="roadmap-gantt-task-popover-value roadmap-gantt-task-popover-value-error"
                                v-if="is_progress_in_error"
                            >
                                {{ task.progress_error_message }}
                            </p>
                            <p class="roadmap-gantt-task-popover-value" v-else>
                                {{ percentage }}
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Task } from "../../../type";
import { State } from "vuex-class";
import { doesTaskHaveEndDateGreaterOrEqualToStartDate } from "../../../helpers/task-has-valid-dates";

@Component
export default class BarPopover extends Vue {
    @Prop({ required: true })
    readonly task!: Task;

    @State
    private readonly locale_bcp47!: string;

    private formatter!: Intl.DateTimeFormat;

    created(): void {
        this.formatter = new Intl.DateTimeFormat(this.locale_bcp47, {
            dateStyle: "long",
        });
    }

    get start_date(): string {
        return this.formatDate(this.task.start);
    }

    get end_date(): string {
        return this.formatDate(this.task.end);
    }

    get popover_class(): string {
        return this.task.is_milestone ? "roadmap-gantt-task-milestone-popover" : "";
    }

    get header_class(): string {
        return "roadmap-gantt-task-popover-header-" + this.task.color_name;
    }

    formatDate(date: Date | null): string {
        if (!date) {
            return "";
        }

        return this.formatter.format(date);
    }

    get is_end_date_in_error(): boolean {
        return !doesTaskHaveEndDateGreaterOrEqualToStartDate(this.task);
    }

    get is_progress_in_error(): boolean {
        return this.task.progress_error_message.length > 0;
    }

    get is_time_period_in_error(): boolean {
        return this.task.time_period_error_message.length > 0;
    }

    get percentage(): string | null {
        if (this.task.progress === null) {
            return null;
        }

        return Math.round(Math.max(0, Math.min(100, this.task.progress * 100))) + "%";
    }
}
</script>

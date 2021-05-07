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
    <div class="roadmap-gantt-task-header roadmap-gantt-subtask-header" v-bind:class="classes">
        <div class="roadmap-gantt-task-header-link roadmap-gantt-task-header-link-skeleton">
            <span class="roadmap-gantt-task-header-xref">
                <span class="tlp-skeleton-text"></span>
            </span>
            <span class="roadmap-gantt-task-header-title">
                <span class="tlp-skeleton-text"></span>
            </span>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { SkeletonRow } from "../../../type";

@Component
export default class SubtaskSkeletonHeader extends Vue {
    @Prop({ required: true })
    readonly skeleton!: SkeletonRow;

    get classes(): string[] {
        const classes = ["roadmap-gantt-task-header-" + this.skeleton.for_task.color_name];

        if (this.skeleton.is_last_one) {
            classes.push("roadmap-gantt-subtask-header-last-one");
        }

        return classes;
    }
}
</script>

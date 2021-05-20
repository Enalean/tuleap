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
    <a v-bind:href="task.html_url" class="roadmap-gantt-task-header-link" v-bind:class="link_class">
        <span class="roadmap-gantt-task-header-xref">
            <span v-if="should_display_project" class="roadmap-gantt-task-header-xref-project">
                {{ task.project.label }}
            </span>
            <i
                v-if="should_display_project"
                class="fas fa-arrow-right roadmap-gantt-task-header-xref-separator"
                aria-hidden="true"
            ></i>
            <span class="roadmap-gantt-task-header-xref-reference">{{ task.xref }}</span>
        </span>
        <span class="roadmap-gantt-task-header-title">{{ task.title }}</span>
    </a>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Task } from "../../../type";

@Component
export default class HeaderLink extends Vue {
    @Prop({ required: true })
    readonly task!: Task;

    @Prop({ required: true })
    readonly should_display_project!: boolean;

    get link_class(): string {
        return "roadmap-gantt-task-header-link-" + this.task.color_name;
    }
}
</script>

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
    <a v-bind:href="artifact_url" class="roadmap-gantt-task-header-link" v-bind:class="link_class">
        <span class="roadmap-gantt-task-header-xref">
            <span
                v-if="should_display_project"
                class="roadmap-gantt-task-header-xref-project"
                data-test="project-icon-and-label"
            >
                <span aria-hidden="true">{{ task.project.icon }}</span>
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

<script setup lang="ts">
import { computed } from "vue";
import type { Task } from "../../../type";
import { DASHBOARD_ID } from "../../../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";

const dashboard_id = strictInject(DASHBOARD_ID);

const props = defineProps<{
    task: Task;
    should_display_project: boolean;
}>();

const link_class = computed((): string => {
    return "roadmap-gantt-task-header-link-" + props.task.color_name;
});

const artifact_url = computed((): string => {
    return `${props.task.html_url}&project-dashboard-id=${dashboard_id}`;
});
</script>

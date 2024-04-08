<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <h1 data-test="title">
        {{ title }}
    </h1>
    <empty-state />
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { getProject } from "./helpers/rest-querier";
import type { ProjectReference } from "@tuleap/core-rest-api-types";
import { useGettext } from "vue3-gettext";
import EmptyState from "@/views/EmptyState.vue";

const props = defineProps<{ project_id: number }>();

const { $gettext, interpolate } = useGettext();

const project_name = ref("...");

const title = computed(() =>
    interpolate($gettext("Artifacts as Documents for %{name}"), { name: project_name.value }),
);

onMounted(() => {
    getProject(props.project_id).match(
        (project: ProjectReference) => {
            project_name.value = project.label;
        },
        () => {
            // do nothing
        },
    );
});
</script>

<!--
  - Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
    <div class="tlp-form-element">
        <label class="tlp-label">
            {{ $gettext("Project") }}
            <select v-model="project_id" class="tlp-select" v-bind:disabled="is_processing">
                <option
                    v-for="project in projects"
                    v-bind:key="project.id"
                    v-bind:value="project.id"
                >
                    {{ project.label }}
                </option>
            </select>
        </label>
    </div>
</template>
<script lang="ts" setup>
import { computed } from "vue";
import { getProjects } from "../rest-querier";
import { usePromise } from "../Helpers/use-promise";

const props = defineProps<{ project_id: number | null }>();
const emit = defineEmits<{
    (e: "update:project_id", value: number | null): void;
}>();

const { is_processing, data: projects } = usePromise([], getProjects());

const project_id = computed({
    get(): number | null {
        return props.project_id;
    },
    set(value: number | null) {
        emit("update:project_id", value);
    },
});
</script>

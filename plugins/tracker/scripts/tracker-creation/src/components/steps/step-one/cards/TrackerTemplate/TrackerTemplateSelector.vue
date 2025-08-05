<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
  -->

<template>
    <div class="tlp-form-element card-content card-tracker-template-selector">
        <label class="tlp-label card-title" for="tracker-creation-template-selector">
            {{ $gettext("Trackers from template projects") }}
        </label>
        <select
            class="tlp-select"
            id="tracker-creation-template-selector"
            data-test="template-selector"
            name="area"
            v-model="model"
            v-on:change="setSelectedTrackerTemplate(model)"
        >
            <option value="" disabled>
                {{ $gettext("Choose a tracker...") }}
            </option>
            <optgroup
                v-for="(project, index) in store.state.project_templates"
                v-bind:label="project.project_name"
                v-bind:key="index"
            >
                <option
                    v-for="tracker in project.tracker_list"
                    v-bind:value="tracker.id"
                    v-bind:key="tracker.id"
                    v-bind:selected="tracker.id === model"
                >
                    {{ tracker.name }}
                </option>
            </optgroup>
        </select>
    </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useMutations, useStore } from "vuex-composition-helpers";

const { setSelectedTrackerTemplate } = useMutations(["setSelectedTrackerTemplate"]);

const store = useStore();

const model = ref("");

onMounted(() => {
    if (store.state.selected_tracker_template) {
        model.value = store.state.selected_tracker_template.id;
    }
});
</script>

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
        <label class="tlp-label card-title" for="tracker-creation-template-selector" v-translate>
            Trackers from template projects
        </label>
        <select
            class="tlp-select"
            id="tracker-creation-template-selector"
            data-test="template-selector"
            name="area"
            v-model="model"
            v-on:change="setSelectedTrackerTemplate(model)"
        >
            <option value="" disabled v-translate>Choose a tracker...</option>
            <optgroup
                v-for="(project, index) in project_templates"
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
<script lang="ts">
import Vue from "vue";
import { State, Mutation } from "vuex-class";
import { Component } from "vue-property-decorator";
import type { ProjectTemplate, Tracker } from "../../../../../store/type";

@Component
export default class TrackerTemplateSelector extends Vue {
    @State
    readonly project_templates!: ProjectTemplate[];

    @State
    readonly selected_tracker_template!: Tracker | null;

    @Mutation
    readonly setSelectedTrackerTemplate!: (tracker_id: string) => void;

    model = "";

    mounted(): void {
        if (this.selected_tracker_template) {
            this.model = this.selected_tracker_template.id;
        }
    }
}
</script>

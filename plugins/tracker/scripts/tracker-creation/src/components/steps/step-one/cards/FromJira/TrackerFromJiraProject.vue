<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div>
        <div class="tlp-form-element">
            <label class="tlp-label" for="project" v-translate>Project</label>
            <select class="tlp-select" id="project" name="project">
                <option value="" v-translate>Choose a project...</option>
                <option
                    v-for="project in project_list"
                    v-bind:value="project.id"
                    v-bind:key="project.id"
                    v-bind:data-test="`project-${project.id}`"
                >
                    {{ project.label }}
                </option>
            </select>
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { ProjectList } from "../../../../../store/type";
import { Component, Prop } from "vue-property-decorator";

@Component
export default class TrackerFromJiraProject extends Vue {
    @Prop({ required: true })
    readonly project_list!: ProjectList[];
}
</script>

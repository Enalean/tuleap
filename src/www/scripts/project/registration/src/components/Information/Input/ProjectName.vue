<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="tlp-form-element project-information-name">
        <label class="tlp-label" for="project-name" v-translate>Name <i class="fa fa-asterisk"></i></label>
        <input id="project-name"
               type="text"
               class="tlp-input"
               data-test="new-project-name"
               v-bind:placeholder="$gettext('My new project')"
               v-bind:minlength="min_project_length"
               v-bind:maxlength="max_project_length"
               ref="name"
               v-on:input="slugifiedProjectName()"
               required
        >
        <p class="tlp-text-info">
            <i class="fa fa-life-saver register-new-project-icon"></i>
            <translate v-bind:translate-params="{min: min_project_length, max: max_project_length}">
                Between %{ min } and %{ max } characters length
            </translate>
        </p>
        <p class="tlp-text-danger" v-if="has_error" data-test="project-name-is-invalid">
            <translate v-bind:translate-params="{min: min_project_length, max: max_project_length}">
                Project name must be between %{ min } and %{ max } characters length.
            </translate>
        </p>

        <project-short-name/>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import ProjectShortName from "./ProjectShortName.vue";
import EventBus from "../../../helpers/event-bus";

@Component({
    components: { ProjectShortName }
})
export default class ProjectName extends Vue {
    $refs!: {
        name: HTMLFormElement;
    };

    has_error = false;
    min_project_length = 3;
    max_project_length = 40;

    slugifiedProjectName(): void {
        const project_name = this.$refs.name.value;
        this.has_error =
            project_name.length < this.min_project_length ||
            project_name.length > this.max_project_length;

        EventBus.$emit("slugify-project-name", project_name);
    }
}
</script>

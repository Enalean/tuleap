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
    <div
        class="tlp-form-element project-information-name"
        v-bind:class="{ 'tlp-form-element-error': has_error }"
    >
        <label class="tlp-label" for="project-name">
            <span>{{ $gettext("Name") }}</span>
            <i class="fa fa-asterisk"></i>
        </label>
        <input
            id="project-name"
            type="text"
            class="tlp-input tlp-input-large"
            data-test="new-project-name"
            v-bind:placeholder="$gettext('My new project')"
            v-bind:minlength="min_project_length"
            v-bind:maxlength="max_project_length"
            ref="name"
            v-on:input="slugifiedProjectName()"
            required
        />
        <p class="tlp-text-info">
            <i class="far fa-fw fa-life-ring"></i> {{ info_project_name_size }}
        </p>
        <p class="tlp-text-danger" v-if="has_error" data-test="project-name-is-invalid">
            <i class="fa fa-fw fa-exclamation-circle"></i> {{ error_project_name_size }}
        </p>

        <project-short-name />
    </div>
</template>
<script setup lang="ts">
import { ref } from "vue";
import ProjectShortName from "./ProjectShortName.vue";
import { useGettext } from "vue3-gettext";
import emitter from "../../../helpers/emitter";

const written_chars = ref(0);
const has_error = ref(false);
const min_project_length = 3;
const max_project_length = 40;

const { $gettext } = useGettext();

const info_project_name_size = $gettext("Between %{ min } and %{ max } characters length", {
    min: String(min_project_length),
    max: String(max_project_length),
});
const error_project_name_size = $gettext(
    "Project name must be between %{ min } and %{ max } characters length.",
    {
        min: String(min_project_length),
        max: String(max_project_length),
    },
);

const name = ref<InstanceType<typeof HTMLInputElement>>();

function slugifiedProjectName(): void {
    written_chars.value++;
    const project_name = name.value !== undefined ? String(name.value.value) : "";
    has_error.value =
        written_chars.value > 3 &&
        (project_name.length < min_project_length || project_name.length > max_project_length);

    emitter.emit("slugify-project-name", project_name);
}
</script>

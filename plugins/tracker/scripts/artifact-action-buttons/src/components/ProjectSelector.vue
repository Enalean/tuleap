<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div class="move-artifact-project-selector-section">
        <label for="move-artifact-project-selector">
            <translate>Destination project</translate>
            <span class="highlight">*</span>
        </label>

        <select
            id="move-artifact-project-selector"
            name="move-artifact-project-selector"
            data-test="move-artifact-project-selector"
            v-model="selected_project_id"
            ref="move_artifact_project_selector"
        >
            <option
                v-for="project in sorted_projects"
                v-bind:key="project.id"
                v-bind:value="project.id"
            >
                {{ project.label }}
            </option>
        </select>
    </div>
</template>
<script>
import { mapGetters } from "vuex";
import { getProjectId } from "../from-tracker-presenter.js";
import { createListPicker } from "@tuleap/list-picker";

export default {
    name: "ProjectSelector",
    data() {
        return {
            list_picker: null,
        };
    },
    computed: {
        ...mapGetters(["sorted_projects"]),
        selected_project_id: {
            get() {
                return this.$store.state.selected_project_id;
            },
            set(project_id) {
                this.$store.dispatch("loadTrackerList", project_id);
            },
        },
    },
    created() {
        this.$store.commit("saveSelectedProjectId", getProjectId());
        this.$store.dispatch("loadTrackerList", this.selected_project_id);
    },
    mounted() {
        this.list_picker = createListPicker(this.$refs.move_artifact_project_selector, {
            locale: document.body.dataset.userLocale,
            is_filterable: true,
            placeholder: this.$gettext("Choose project..."),
        });
    },
    beforeDestroy() {
        this.list_picker.destroy();
    },
};
</script>

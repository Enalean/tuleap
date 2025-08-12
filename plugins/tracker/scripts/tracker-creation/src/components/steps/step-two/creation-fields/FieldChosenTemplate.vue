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
    <div class="tracker-information-selected-template">
        <div class="tlp-property" v-if="selected_template_project_name.length > 0">
            <label class="tlp-label">{{ $gettext("Source project") }}</label>
            <p
                class="tracker-information-selected-template-info"
                data-test="project-of-chosen-template"
            >
                {{ selected_template_project_name }}
            </p>
        </div>
        <div class="tlp-property">
            <label class="tlp-label">{{ $gettext("Chosen template") }}</label>
            <p class="tracker-information-selected-template-info" data-test="chosen-template">
                {{ selected_template_name }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useState, useGetters } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import type { State } from "../../../../store/type";

const { $gettext } = useGettext();

const { from_jira_data } = useState<Pick<State, "from_jira_data">>(["from_jira_data"]);
const { tracker_to_be_created } = useState<Pick<State, "tracker_to_be_created">>([
    "tracker_to_be_created",
]);
const { selected_tracker_template } = useState<Pick<State, "selected_tracker_template">>([
    "selected_tracker_template",
]);
const { selected_project_tracker_template } = useState<
    Pick<State, "selected_project_tracker_template">
>(["selected_project_tracker_template"]);
const { selected_project } = useState<Pick<State, "selected_project">>(["selected_project"]);

const {
    project_of_selected_tracker_template,
    is_created_from_empty,
    is_a_duplication,
    is_a_xml_import,
    is_created_from_default_template,
    is_created_from_jira,
    is_a_duplication_of_a_tracker_from_another_project,
} = useGetters([
    "project_of_selected_tracker_template",
    "is_created_from_empty",
    "is_a_duplication",
    "is_a_xml_import",
    "is_created_from_default_template",
    "is_created_from_jira",
    "is_a_duplication_of_a_tracker_from_another_project",
]);

const selected_template_name = ref("");
const selected_template_project_name = ref("");

onMounted((): void => {
    if (is_created_from_default_template.value && selected_tracker_template.value) {
        selected_template_name.value = selected_tracker_template.value.name;
    } else if (is_a_duplication.value && selected_tracker_template.value) {
        selected_template_name.value = selected_tracker_template.value.name;
        selected_template_project_name.value =
            project_of_selected_tracker_template.value.project_name;
    } else if (is_created_from_empty.value) {
        selected_template_name.value = $gettext("Empty");
    } else if (is_a_xml_import.value) {
        selected_template_name.value = tracker_to_be_created.value.name;
    } else if (
        is_a_duplication_of_a_tracker_from_another_project.value &&
        selected_project.value &&
        selected_project_tracker_template.value
    ) {
        selected_template_name.value = selected_project_tracker_template.value.name;
        selected_template_project_name.value = selected_project.value.name;
    } else if (is_created_from_jira.value) {
        if (!from_jira_data.value.tracker || !from_jira_data.value.project) {
            throw new Error("Jira project or tracker not found in store!");
        }
        selected_template_name.value = from_jira_data.value.tracker.name;
        selected_template_project_name.value = from_jira_data.value.project.label;
    }
});
</script>

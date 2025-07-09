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
        <div
            class="tlp-alert-danger"
            data-test="jira-fail-load-trackers"
            v-if="error_message.length > 0"
        >
            {{ error_message }}
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" for="project">{{ $gettext("Project") }}</label>
            <select
                class="tlp-select"
                id="project"
                name="project"
                v-on:change="selectProject($event)"
                data-test="project-list"
            >
                <option value="">
                    {{ $gettext("Choose a project...") }}
                </option>
                <option
                    v-for="project in project_list"
                    v-bind:value="JSON.stringify(project)"
                    v-bind:key="project.id"
                    v-bind:data-test="`project-${project.id}`"
                    v-bind:selected="
                        from_jira_data.project !== null && project.id === from_jira_data.project.id
                    "
                >
                    {{ project.label }}
                </option>
            </select>
        </div>
        <div
            class="tlp-form-element"
            v-if="is_a_project_selected || from_jira_data.tracker_list !== null"
        >
            <label class="tlp-label" for="tracker_name">
                <i class="fa fa-spin fa-spinner" v-if="is_loading" />
                {{ $gettext("Issue type") }}
            </label>
            <select
                class="tlp-select"
                id="tracker_name"
                name="tracker_name"
                data-test="tracker-name"
                v-if="!is_loading"
                v-on:change="selectTracker($event)"
            >
                <option value="" selected>
                    {{ $gettext("Choose an issue type...") }}
                </option>
                <option
                    v-for="tracker in from_jira_data.tracker_list"
                    v-bind:value="JSON.stringify(tracker)"
                    v-bind:key="tracker.id"
                    v-bind:selected="
                        from_jira_data.tracker_list !== null &&
                        from_jira_data.tracker !== null &&
                        tracker.id === from_jira_data.tracker.id
                    "
                >
                    {{ tracker.name }}
                </option>
            </select>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref } from "vue";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { ProjectList } from "../../../../../store/type";
import { useState, useMutations, useActions } from "vuex-composition-helpers";

defineProps<{
    project_list: ProjectList[];
}>();

const { from_jira_data } = useState(["from_jira_data"]);

const { setTrackerList, setProject, setTrackerName, setTracker } = useMutations([
    "setTrackerList",
    "setProject",
    "setTrackerName",
    "setTracker",
]);

const { getJiraTrackerList } = useActions(["getJiraTrackerList"]);

const is_a_project_selected = ref(false);
const is_loading = ref(false);
const error_message = ref("");

async function selectProject(event: Event): Promise<void> {
    if (!(event.target instanceof HTMLSelectElement)) {
        return;
    }
    error_message.value = "";

    if (!event.target.value) {
        return;
    }

    const project = JSON.parse(event.target.value);
    try {
        is_a_project_selected.value = true;
        is_loading.value = true;
        const tracker_list = await getJiraTrackerList({
            credentials: from_jira_data.value.credentials,
            project_key: project.id,
        });

        setTrackerList(tracker_list);
        setProject(project);
    } catch (e) {
        if (!(e instanceof FetchWrapperError)) {
            throw e;
        }
        const { error } = await e.response.json();
        error_message.value = error;
    } finally {
        is_loading.value = false;
    }
}

function selectTracker(event: Event): void {
    if (!(event.target instanceof HTMLSelectElement)) {
        return;
    }
    if (!event.target.value) {
        return;
    }

    const tracker = JSON.parse(event.target.value);
    setTrackerName(tracker.name);
    setTracker(tracker);
}
</script>

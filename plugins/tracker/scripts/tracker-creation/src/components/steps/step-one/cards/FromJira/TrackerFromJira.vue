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
    <form class="card-content" v-on:submit.stop.prevent="checkConnexionIsReady">
        <div
            class="tlp-alert-danger"
            data-test="jira-fail-load-project"
            v-if="error_message.length > 0"
        >
            {{ error_message }}
        </div>
        <tracker-from-jira-server v-if="should_display_connection" v-bind:value="credentials" />
        <button
            type="submit"
            class="tlp-button-primary create-from-jira-button"
            v-bind:disabled="should_be_disabled"
            v-if="should_display_connection"
            data-test="should-display-connexion"
        >
            <span>{{ $gettext("Connect") }}</span>
            <i class="fa tlp-button-icon-right" v-bind:class="icon_class"></i>
        </button>
        <tracker-from-jira-project v-else-if="project_list" v-bind:project_list="project_list" />
    </form>
</template>

<script setup lang="ts">
import { ref, computed, onBeforeMount } from "vue";
import type { Credentials, ProjectList } from "../../../../../store/type";
import TrackerFromJiraProject from "./TrackerFromJiraProject.vue";
import TrackerFromJiraServer from "./TrackerFromJiraServer.vue";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { useActions, useMutations, useState } from "vuex-composition-helpers";

const { from_jira_data } = useState(["from_jira_data"]);
const { setJiraCredentials, setProjectList } = useMutations([
    "setJiraCredentials",
    "setProjectList",
]);
const { getJiraProjectList } = useActions(["getJiraProjectList"]);

const is_connection_valid = ref(false);
const is_loading = ref(false);
const error_message = ref("");
const project_list = ref<ProjectList[] | null>(null);

const credentials = ref<Credentials>({
    server_url: "",
    user_email: "",
    token: "",
});

onBeforeMount(() => {
    if (!from_jira_data.value.project_list) {
        return;
    }
    project_list.value = from_jira_data.value.project_list;
});

const checkConnexionIsReady = async (): Promise<void> => {
    error_message.value = "";

    try {
        is_loading.value = true;
        project_list.value = await getJiraProjectList(credentials.value);

        setJiraCredentials(credentials.value);
        setProjectList(project_list.value);

        is_connection_valid.value = true;
    } catch (e) {
        if (!(e instanceof FetchWrapperError)) {
            throw e;
        }
        const { error } = await e.response.json();
        error_message.value = error;
    } finally {
        is_loading.value = false;
    }
};

const should_display_connection = computed((): boolean => {
    return !is_connection_valid.value && !project_list.value;
});

const icon_class = computed((): string => {
    if (is_loading.value) {
        return "fa-circle-o-notch fa-spinner";
    }
    return "";
});

const should_be_disabled = computed((): boolean => {
    return (
        credentials.value.server_url === "" ||
        credentials.value.user_email === "" ||
        credentials.value.token === "" ||
        is_loading.value
    );
});
</script>

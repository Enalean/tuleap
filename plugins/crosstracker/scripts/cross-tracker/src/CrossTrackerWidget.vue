<!--
  - Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
    <feedback-message />
    <read-query
        v-if="widget_pane === 'query-active'"
        v-on:switch-to-create-query-pane="handleCreateNewQuery"
        v-bind:selected_query="selected_query"
    />
    <create-new-query
        v-else-if="widget_pane === 'query-creation' && is_user_admin"
        v-on:return-to-active-query-pane="displayActiveQuery"
    />
    <edit-query
        v-else-if="widget_pane === 'query-edition'"
        v-bind:query="query_to_edit"
        v-on:return-to-active-query-pane="displayActiveQuery"
    />
</template>
<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { EMITTER, IS_USER_ADMIN, WIDGET_TITLE_UPDATER } from "./injection-symbols";
import type {
    CreatedQueryEvent,
    EditedQueryEvent,
    EditQueryEvent,
    InitializedWithQueryEvent,
    SwitchQueryEvent,
} from "./helpers/widget-events";
import {
    NEW_QUERY_CREATED_EVENT,
    QUERY_EDITED_EVENT,
    INITIALIZED_WITH_QUERY_EVENT,
    SWITCH_QUERY_EVENT,
    CREATE_NEW_QUERY_EVENT,
    EDIT_QUERY_EVENT,
} from "./helpers/widget-events";
import CreateNewQuery from "./components/query/creation/CreateNewQuery.vue";
import {
    QUERY_ACTIVE_PANE,
    QUERY_CREATION_PANE,
    QUERY_EDITION_PANE,
} from "./domain/WidgetPaneDisplay";
import ReadQuery from "./components/ReadQuery.vue";
import FeedbackMessage from "./components/feedback/FeedbackMessage.vue";
import EditQuery from "./components/query/edition/EditQuery.vue";
import type { Query } from "./type";

const is_user_admin = strictInject(IS_USER_ADMIN);
const emitter = strictInject(EMITTER);
const widget_title_updater = strictInject(WIDGET_TITLE_UPDATER);

const widget_pane = ref(QUERY_ACTIVE_PANE);
const selected_query = ref<Query>();
const query_to_edit = ref<Query>({
    description: "",
    id: "",
    is_default: false,
    title: "",
    tql_query: "",
});

function displayActiveQuery(): void {
    widget_pane.value = QUERY_ACTIVE_PANE;
}

onMounted(() => {
    emitter.on(CREATE_NEW_QUERY_EVENT, handleCreateNewQuery);
    emitter.on(EDIT_QUERY_EVENT, handleEditQuery);
    emitter.on(SWITCH_QUERY_EVENT, setCurrentlySelectedQuery);
    emitter.on(INITIALIZED_WITH_QUERY_EVENT, setCurrentlySelectedQuery);
    emitter.on(NEW_QUERY_CREATED_EVENT, setCurrentlySelectedQuery);
    emitter.on(QUERY_EDITED_EVENT, setCurrentlySelectedQuery);
    widget_title_updater.listenToUpdateTitle();
});

onBeforeUnmount(() => {
    emitter.off(CREATE_NEW_QUERY_EVENT, handleCreateNewQuery);
    emitter.off(EDIT_QUERY_EVENT, handleEditQuery);
    emitter.off(SWITCH_QUERY_EVENT, setCurrentlySelectedQuery);
    emitter.off(INITIALIZED_WITH_QUERY_EVENT, setCurrentlySelectedQuery);
    emitter.off(NEW_QUERY_CREATED_EVENT, setCurrentlySelectedQuery);
    emitter.off(QUERY_EDITED_EVENT, setCurrentlySelectedQuery);
    emitter.all.clear();
    widget_title_updater.removeListener();
});

function setCurrentlySelectedQuery(
    event: SwitchQueryEvent | InitializedWithQueryEvent | CreatedQueryEvent | EditedQueryEvent,
): void {
    selected_query.value = event.query;
}

function handleCreateNewQuery(): void {
    widget_pane.value = QUERY_CREATION_PANE;
}

function handleEditQuery(query_to_edit_event: EditQueryEvent): void {
    query_to_edit.value = query_to_edit_event.query;
    widget_pane.value = QUERY_EDITION_PANE;
}
</script>

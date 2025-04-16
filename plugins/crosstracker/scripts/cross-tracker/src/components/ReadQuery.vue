<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <section class="tlp-pane-section" v-bind:class="{ 'reading-mode-shown': !is_loading }">
        <action-buttons
            v-bind:backend_query="backend_query"
            v-bind:queries="queries"
            v-bind:are_query_details_toggled="are_query_details_toggled"
        />
        <div class="cross-tracker-loader" v-if="is_loading"></div>
        <reading-mode
            v-if="!is_loading && are_query_details_toggled"
            v-bind:reading_query="backend_query"
            v-bind:has_error="has_error"
            v-on:switch-to-writing-mode="handleSwitchWriting"
        />
    </section>
    <section class="tlp-pane-section" v-if="!is_loading">
        <selectable-table v-bind:query="backend_query" />
    </section>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, provide, ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import ReadingMode from "../components/reading-mode/ReadingMode.vue";
import { getQueries } from "../api/rest-querier";
import type { Query } from "../type";
import SelectableTable from "../components/selectable-table/SelectableTable.vue";
import { EMITTER, IS_EXPORT_ALLOWED, IS_USER_ADMIN, WIDGET_ID } from "../injection-symbols";
import { QueryRetrievalFault } from "../domain/QueryRetrievalFault";
import ActionButtons from "../components/actions/ActionButtons.vue";
import type {
    DeletedQueryEvent,
    SwitchQueryEvent,
    ToggleQueryDetailsEvent,
} from "../helpers/widget-events";
import {
    INITIALIZED_WITH_QUERY_EVENT,
    EDIT_QUERY_EVENT,
    REFRESH_ARTIFACTS_EVENT,
    QUERY_DELETED_EVENT,
    TOGGLE_QUERY_DETAILS_EVENT,
    NOTIFY_FAULT_EVENT,
    SWITCH_QUERY_EVENT,
} from "../helpers/widget-events";

const emit = defineEmits<{
    (e: "switch-to-create-query-pane"): void;
}>();

const props = defineProps<{
    selected_query: Query | undefined;
}>();

const widget_id = strictInject(WIDGET_ID);
const is_user_admin = strictInject(IS_USER_ADMIN);
const emitter = strictInject(EMITTER);

const empty_query: Query = { id: "", tql_query: "", title: "", description: "", is_default: false };
const backend_query = ref<Query>(empty_query);

const is_loading = ref(true);
const queries = ref<ReadonlyArray<Query>>([]);

const are_query_details_toggled = ref<boolean>(false);

const has_error = ref<boolean>(false);

const is_export_allowed = computed<boolean>(() => {
    if (has_error.value) {
        return false;
    }
    if (!is_user_admin) {
        return true;
    }
    return !has_error.value;
});

provide(IS_EXPORT_ALLOWED, is_export_allowed);

function loadBackendQueries(): void {
    is_loading.value = true;
    getQueries(widget_id)
        .match(
            (widget_queries: ReadonlyArray<Query>) => {
                queries.value = widget_queries;
                if (widget_queries.length === 0) {
                    if (is_user_admin) {
                        emit("switch-to-create-query-pane");
                    }
                    return;
                }

                backend_query.value =
                    props.selected_query ??
                    widget_queries.find((query) => query.is_default) ??
                    widget_queries[0];
                emitter.emit(INITIALIZED_WITH_QUERY_EVENT, { query: backend_query.value });
                has_error.value = false;
            },
            (fault) => {
                emitter.emit(NOTIFY_FAULT_EVENT, { fault: QueryRetrievalFault(fault) });
                has_error.value = true;
            },
        )
        .then(() => {
            is_loading.value = false;
        });
}

onMounted(() => {
    emitter.on(SWITCH_QUERY_EVENT, handleSwitchQuery);
    emitter.on(QUERY_DELETED_EVENT, handleDeleteQuery);
    emitter.on(TOGGLE_QUERY_DETAILS_EVENT, handleToggleQueryDetails);
    loadBackendQueries();
});

onBeforeUnmount(() => {
    emitter.off(SWITCH_QUERY_EVENT, handleSwitchQuery);
    emitter.off(TOGGLE_QUERY_DETAILS_EVENT, handleToggleQueryDetails);
    emitter.off(QUERY_DELETED_EVENT, handleDeleteQuery);
});

function handleToggleQueryDetails(toggle: ToggleQueryDetailsEvent): void {
    are_query_details_toggled.value = toggle.display_query_details;
}

function handleDeleteQuery(event: DeletedQueryEvent): void {
    queries.value = queries.value.filter((query) => query.id !== event.deleted_query.id);
    if (queries.value.length === 0) {
        emit("switch-to-create-query-pane");
        return;
    }
    const query = queries.value[0];
    emitter.emit(REFRESH_ARTIFACTS_EVENT, { query });
    emitter.emit(SWITCH_QUERY_EVENT, { query });
}

function handleSwitchWriting(): void {
    if (!is_user_admin) {
        return;
    }
    emitter.emit(EDIT_QUERY_EVENT, { query: backend_query.value });
}

function handleSwitchQuery(event: SwitchQueryEvent): void {
    backend_query.value = event.query;
}

defineExpose({
    is_export_allowed,
});
</script>

<style scoped lang="scss">
.reading-mode-shown {
    border: 0;
}
</style>

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
    <section class="tlp-pane-section">
        <query-suggested
            v-on:query-chosen="handleChosenQuery"
            v-bind:is_modal_should_be_displayed="is_modal_should_be_displayed"
        />
    </section>
    <section class="tlp-pane-section edit-query-section">
        <div class="edit-query-title-description-container">
            <title-input v-model:title="title" />
            <description-text-area v-model:description="description" />
        </div>
        <div class="tlp-form-element">
            <query-editor
                v-model:tql_query="tql_query"
                v-on:trigger-search="handleSearch"
                ref="query_editor"
            />
        </div>
        <query-displayed-by-default-switch v-model:is_default_query="is_default_query" />
        <div class="query-edition-action-buttons">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline"
                v-on:click="handleCancelButton"
                data-test="query-edition-cancel-button"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary"
                v-on:click="handleSearchButton"
                v-bind:disabled="is_search_button_disabled"
                data-test="query-edition-search-button"
            >
                <i
                    v-if="!is_search_loading"
                    aria-hidden="true"
                    class="fa-solid fa-search tlp-button-icon"
                    data-test="query-edition-search-button-search-icon"
                ></i>
                <i
                    v-if="is_search_loading"
                    aria-hidden="true"
                    class="tlp-button-icon fas fa-spin fa-circle-notch"
                    data-test="query-edition-search-button-spin-icon"
                ></i>
                {{ $gettext("Search") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary"
                v-on:click="handleSaveButton"
                v-bind:disabled="is_save_button_disabled"
                data-test="query-edition-save-button"
            >
                <i
                    v-if="!is_save_loading"
                    aria-hidden="true"
                    class="tlp-button-icon fa-solid fa-save"
                ></i>
                <i
                    v-if="is_save_loading"
                    aria-hidden="true"
                    class="tlp-button-icon fas fa-spin fa-circle-notch"
                ></i>
                {{ $gettext("Save") }}
            </button>
        </div>
        <selectable-table
            v-if="is_selectable_table_displayed"
            v-on:search-finished="is_search_loading = false"
            v-on:search-started="is_search_loading = true"
            v-bind:tql_query="tql_query"
        />
    </section>
</template>

<script setup lang="ts">
import TitleInput from "../TitleInput.vue";
import DescriptionTextArea from "../DescriptionTextArea.vue";
import { computed, ref } from "vue";
import type { QuerySuggestion } from "../../../domain/SuggestedQueriesGetter";
import QuerySuggested from "../QuerySuggested.vue";

import { strictInject } from "@tuleap/vue-strict-inject";
import { EMITTER, QUERY_UPDATER, WIDGET_ID } from "../../../injection-symbols";
import {
    NOTIFY_FAULT_EVENT,
    NOTIFY_SUCCESS_EVENT,
    QUERY_EDITED_EVENT,
    SEARCH_ARTIFACTS_EVENT,
} from "../../../helpers/widget-events";
import type { PutQueryRepresentation } from "../../../api/cross-tracker-rest-api-types";
import { useGettext } from "vue3-gettext";
import QueryDisplayedByDefaultSwitch from "../QueryDisplayedByDefaultSwitch.vue";
import type { Query } from "../../../type";
import QueryEditor from "../QueryEditor.vue";
import SelectableTable from "../../selectable-table/SelectableTable.vue";

const { $gettext } = useGettext();

const props = defineProps<{
    query: Query;
}>();

const emit = defineEmits<{
    (e: "return-to-active-query-pane"): void;
}>();
const query_editor = ref<InstanceType<typeof QueryEditor>>();

const emitter = strictInject(EMITTER);
const widget_id = strictInject(WIDGET_ID);

const query_updater = strictInject(QUERY_UPDATER);

const title = ref(props.query.title);
const description = ref(props.query.description);
const tql_query = ref(props.query.tql_query);
const is_default_query = ref(props.query.is_default);

const is_modal_should_be_displayed = computed((): boolean => {
    return description.value !== "" || title.value !== "" || tql_query.value !== "";
});

const searched_tql_query = ref("");

const is_save_loading = ref(false);
const is_search_loading = ref(false);
const is_selectable_table_displayed = ref(false);

const is_search_button_disabled = computed((): boolean => {
    return tql_query.value === searched_tql_query.value || is_search_loading.value;
});

const is_save_button_disabled = computed((): boolean => {
    return tql_query.value === "" || title.value === "" || is_save_loading.value;
});

function handleCancelButton(): void {
    emit("return-to-active-query-pane");
}

function handleSearch(tql_query: string): void {
    searched_tql_query.value = tql_query;
    search();
}

function handleSaveButton(): void {
    is_save_loading.value = true;
    const updated_query: PutQueryRepresentation = {
        tql_query: tql_query.value,
        description: description.value,
        title: title.value,
        widget_id,
        is_default: is_default_query.value,
    };
    query_updater
        .updateQuery(props.query, updated_query)
        .match(
            (edited_query) => {
                emitter.emit(NOTIFY_SUCCESS_EVENT, {
                    message: $gettext("Query updated with success!"),
                });
                emitter.emit(QUERY_EDITED_EVENT, { query: edited_query });
                emit("return-to-active-query-pane");
            },
            (fault) => {
                emitter.emit(NOTIFY_FAULT_EVENT, { fault, tql_query: tql_query.value });
            },
        )
        .then(() => {
            is_save_loading.value = false;
        });
}

function handleSearchButton(): void {
    searched_tql_query.value = tql_query.value;
    search();
}

function search(): void {
    is_selectable_table_displayed.value = true;
    emitter.emit(SEARCH_ARTIFACTS_EVENT);
}

function handleChosenQuery(query: QuerySuggestion): void {
    title.value = query.title;
    description.value = query.description;
    tql_query.value = query.tql_query;
    query_editor.value?.updateEditor(query.tql_query);
}
</script>

<style scoped lang="scss">
.edit-query-title-description-container {
    display: flex;
    justify-content: space-between;
    gap: var(--tlp-medium-spacing);
    margin: 0 0 var(--tlp-small-spacing);
}

.edit-query-section {
    border: 0;
}

.query-edition-action-buttons {
    display: flex;
    gap: var(--tlp-medium-spacing);
}
</style>

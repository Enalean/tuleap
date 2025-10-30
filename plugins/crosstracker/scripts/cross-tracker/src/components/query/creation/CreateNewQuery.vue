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
    <section class="tlp-pane-section query-suggestion-container">
        <ask-a-i v-if="doesAiPluginIsEnabled()" />
        <query-suggested
            v-on:query-chosen="handleChosenQuery"
            v-bind:is_modal_should_be_displayed="is_modal_should_be_displayed"
        />
    </section>
    <section class="tlp-pane-section create-new-query-section">
        <div class="create-new-query-title-description-container">
            <title-input v-model:title="title" />
            <description-text-area v-model:description="description" />
        </div>
        <div class="tlp-form-element">
            <query-editor
                v-model:tql_query="tql_query"
                v-on:trigger-search="ctrlEnterSearchFromEditor"
                ref="query_editor"
            />
        </div>
        <query-displayed-by-default-switch v-model:is_default_query="is_default_query" />
        <div class="query-creation-action-buttons">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline"
                v-on:click="handleCancelButton"
                data-test="query-creation-cancel-button"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary"
                v-on:click="handleSearchButton"
                v-bind:disabled="is_search_button_disabled"
                data-test="query-creation-search-button"
            >
                <i
                    aria-hidden="true"
                    class="fa-solid fa-search tlp-button-icon"
                    data-test="query-creation-search-button-search-icon"
                ></i>
                {{ $gettext("Search") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary"
                v-on:click="handleSaveButton"
                v-bind:disabled="is_save_button_disabled"
                data-test="query-creation-save-button"
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
        <table-wrapper v-if="is_selectable_table_displayed" v-bind:tql_query="searched_tql_query" />
    </section>
</template>

<script setup lang="ts">
import TitleInput from "../TitleInput.vue";
import DescriptionTextArea from "../DescriptionTextArea.vue";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { QuerySuggestion } from "../../../domain/SuggestedQueriesGetter";
import QuerySuggested from "../QuerySuggested.vue";

import { strictInject } from "@tuleap/vue-strict-inject";
import {
    EMITTER,
    EXTERNAL_PLUGINS,
    NEW_QUERY_CREATOR,
    WIDGET_ID,
} from "../../../injection-symbols";
import type { TQLQueryEvent } from "../../../helpers/widget-events";
import {
    NEW_QUERY_CREATED_EVENT,
    NOTIFY_FAULT_EVENT,
    NOTIFY_SUCCESS_EVENT,
    SEND_TQL_QUERY_FROM_CHATBOT_EVENT,
} from "../../../helpers/widget-events";
import type { PostQueryRepresentation } from "../../../api/cross-tracker-rest-api-types";
import { useGettext } from "vue3-gettext";
import QueryDisplayedByDefaultSwitch from "../QueryDisplayedByDefaultSwitch.vue";
import QueryEditor from "../QueryEditor.vue";
import type { PostNewQuery } from "../../../domain/PostNewQuery";
import AskAI from "../../AskAI.vue";
import { AI_CROSSTRACKER_PLUGIN } from "../../../helpers/external-plugins";
import TableWrapper from "../../TableWrapper.vue";

const { $gettext } = useGettext();

const emit = defineEmits<{
    (e: "return-to-active-query-pane"): void;
}>();
const query_editor = ref<InstanceType<typeof QueryEditor>>();

const emitter = strictInject(EMITTER);
const widget_id = strictInject(WIDGET_ID);

const new_query_creator: PostNewQuery = strictInject(NEW_QUERY_CREATOR);
const external_plugins: string[] = strictInject(EXTERNAL_PLUGINS);

const title = ref("");
const description = ref("");
const tql_query = ref("");
const is_default_query = ref(false);

const searched_tql_query = ref("");

const is_save_loading = ref(false);
const is_selectable_table_displayed = ref(false);

const is_search_button_disabled = computed((): boolean => {
    return tql_query.value === searched_tql_query.value;
});

const is_save_button_disabled = computed((): boolean => {
    return tql_query.value === "" || title.value === "" || is_save_loading.value;
});

const is_modal_should_be_displayed = computed((): boolean => {
    return description.value !== "" || title.value !== "" || tql_query.value !== "";
});

function doesAiPluginIsEnabled(): boolean {
    return external_plugins.includes(AI_CROSSTRACKER_PLUGIN);
}

function handleCancelButton(): void {
    emit("return-to-active-query-pane");
}

function ctrlEnterSearchFromEditor(tql_query: string): void {
    searched_tql_query.value = tql_query;
    is_selectable_table_displayed.value = true;
}

function handleSearchFromAi(event: TQLQueryEvent): void {
    tql_query.value = event.tql_query;
    searched_tql_query.value = tql_query.value;
    query_editor.value?.updateEditor(event.tql_query);
    is_selectable_table_displayed.value = true;
}

onMounted((): void => {
    emitter.on(SEND_TQL_QUERY_FROM_CHATBOT_EVENT, handleSearchFromAi);
});

onBeforeUnmount(() => {
    emitter.off(SEND_TQL_QUERY_FROM_CHATBOT_EVENT);
});

function handleSaveButton(): void {
    is_save_loading.value = true;
    const new_query: PostQueryRepresentation = {
        tql_query: tql_query.value,
        description: description.value,
        title: title.value,
        widget_id,
        is_default: is_default_query.value,
    };
    new_query_creator
        .postNewQuery(new_query)
        .match(
            (created_query) => {
                emitter.emit(NOTIFY_SUCCESS_EVENT, {
                    message: $gettext("Query created with success!"),
                });
                emitter.emit(NEW_QUERY_CREATED_EVENT, { query: created_query });
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
    is_selectable_table_displayed.value = true;
}

function handleChosenQuery(query: QuerySuggestion): void {
    title.value = query.title;
    description.value = query.description;
    tql_query.value = query.tql_query;
    query_editor.value?.updateEditor(query.tql_query);
}
</script>

<style scoped lang="scss">
.query-suggestion-container {
    position: relative;
}

.create-new-query-title-description-container {
    display: flex;
    justify-content: space-between;
    gap: var(--tlp-medium-spacing);
    margin: 0 0 var(--tlp-small-spacing);
}

.create-new-query-section {
    border: 0;
}

.query-creation-action-buttons {
    display: flex;
    gap: var(--tlp-medium-spacing);
}
</style>

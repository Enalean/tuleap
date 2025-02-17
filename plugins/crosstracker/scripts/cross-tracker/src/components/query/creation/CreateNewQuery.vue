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
    <section class="tlp-pane-section create-new-query-section">
        <div class="create-new-query-title-description-container">
            <title-input v-model:title="title" />
            <description-text-area v-model:description="description" />
        </div>
        <div class="tlp-form-element">
            <query-editor-for-creation
                v-model:tql_query="tql_query"
                v-on:trigger-search="handleSearch"
            />
        </div>
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
                <i aria-hidden="true" class="fa-solid fa-search tlp-button-icon"></i>
                {{ $gettext("Search") }}
            </button>
            <button
                v-if="is_save_button_displayed"
                type="button"
                class="tlp-button-primary"
                v-on:click="handleSaveButton"
                v-bind:disabled="is_save_button_disabled"
                data-test="query-creation-save-button"
            >
                <i aria-hidden="true" class="tlp-button-icon fa-solid fa-save"></i>
                {{ $gettext("Save") }}
            </button>
        </div>
    </section>
</template>

<script setup lang="ts">
import QueryEditorForCreation from "./QueryEditorForCreation.vue";
import TitleInput from "../TitleInput.vue";
import DescriptionTextArea from "../DescriptionTextArea.vue";
import { computed, ref } from "vue";
const emit = defineEmits<{
    (e: "return-to-active-query-pane"): void;
}>();

const title = ref("");
const description = ref("");
const tql_query = ref("");

const searched_tql_query = ref("");

const is_search_button_disabled = computed((): boolean => {
    return tql_query.value === searched_tql_query.value;
});

const is_save_button_displayed = computed((): boolean => {
    return tql_query.value !== "" && title.value !== "";
});

const is_save_button_disabled = computed((): boolean => {
    return tql_query.value !== searched_tql_query.value;
});

function handleCancelButton(): void {
    emit("return-to-active-query-pane");
}

function handleSearch(tql_query: string): void {
    // eslint-disable-next-line no-console
    console.log("Trigger search shortcut with tql_query: " + tql_query);
    searched_tql_query.value = tql_query;
}

function handleSaveButton(): void {
    // eslint-disable-next-line no-console
    console.log("Trigger save button with tql_query: " + tql_query.value);
    // eslint-disable-next-line no-console
    console.log("Trigger save with title:" + title.value + " description: " + description.value);
    emit("return-to-active-query-pane");
}

function handleSearchButton(): void {
    // eslint-disable-next-line no-console
    console.log("Trigger search button with tql_query: " + tql_query.value);
    searched_tql_query.value = tql_query.value;
}
</script>

<style scoped lang="scss">
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

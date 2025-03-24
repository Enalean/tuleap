<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
    <div
        ref="modal_element"
        role="dialog"
        aria-labelledby="modal-label"
        class="tlp-modal tlp-modal-medium-sized"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="modal-label">
                {{ $gettext('"%{title}" query preview', { title: query.title }) }}
            </h1>
            <button class="tlp-modal-close" type="button" data-dismiss="modal" aria-label="Close">
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <h2 class="tlp-modal-subtitle">{{ $gettext("Query title") }}</h2>
            <p data-test="modal-query-title">{{ query.title }}</p>
            <template v-if="query.description !== ''">
                <h2 class="tlp-modal-subtitle">{{ $gettext("Description") }}</h2>
                <p data-test="modal-query-description">{{ query.description }}</p>
            </template>
            <h2 class="tlp-modal-subtitle">{{ $gettext("Query") }}</h2>
            <tlp-syntax-highlighting>
                <code data-test="modal-query-tql" class="language-tql query-code-block">{{
                    query.tql_query
                }}</code>
            </tlp-syntax-highlighting>
        </div>
        <div class="tlp-modal-footer suggestion-modal-footer">
            <p class="tlp-text-muted overwrite-info">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                {{
                    $gettext(
                        "If you validate, this query will replace your content. You can't undo it.",
                    )
                }}
            </p>
            <button
                id="button-close"
                type="button"
                data-dismiss="modal"
                data-test="modal-cancel-button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary tlp-modal-action"
                data-test="modal-action-button"
                v-on:click="handleActionClick"
            >
                {{ $gettext("Overwrite current query") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { onMounted, onUnmounted, ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { EMITTER } from "../../injection-symbols";
import type { DisplayQueryPreviewEvent } from "../../helpers/widget-events";
import { DISPLAY_QUERY_PREVIEW_EVENT } from "../../helpers/widget-events";
import type { QuerySuggestion } from "../../domain/SuggestedQueriesGetter";

const modal_element = ref<HTMLDivElement>();
const modal = ref<Modal | null>(null);
const query = ref<QuerySuggestion>({
    title: "",
    description: "",
    tql_query: "",
});

const emit = defineEmits<{
    (e: "query-chosen", query: QuerySuggestion): void;
}>();

const emitter = strictInject(EMITTER);

onMounted(() => {
    if (modal_element.value === undefined) {
        throw Error("Cannot find the modal html element");
    }
    modal.value = createModal(modal_element.value, {
        keyboard: false,
        dismiss_on_backdrop_click: true,
    });

    emitter.on(DISPLAY_QUERY_PREVIEW_EVENT, display);
});

onUnmounted(() => {
    modal.value?.destroy();
    emitter.off(DISPLAY_QUERY_PREVIEW_EVENT, display);
});

function display(event: DisplayQueryPreviewEvent): void {
    query.value = event.query;
    modal.value?.show();
}

function handleActionClick(): void {
    emit("query-chosen", query.value);
    modal.value?.hide();
}
</script>

<style scoped>
.query-code-block {
    padding: 3px 0;
    background: transparent;
}

.suggestion-modal-footer {
    align-items: baseline;
}

.overwrite-info {
    margin: 0;
    padding-right: var(--tlp-medium-spacing);
}
</style>

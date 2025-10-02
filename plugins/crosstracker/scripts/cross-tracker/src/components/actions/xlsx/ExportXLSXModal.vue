<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
        id="modal"
        role="dialog"
        aria-labelledby="modal-label"
        class="tlp-modal"
        ref="modal_element"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="modal-label">{{ $gettext("Export XLSX") }}</h1>
            <button class="tlp-modal-close" type="button" data-dismiss="modal" aria-label="Close">
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>

        <feedback-message />
        <div class="tlp-modal-body">
            <h2 class="tlp-modal-subtitle">{{ subtitle_text }}</h2>
            <p v-dompurify-html="textContent()"></p>
        </div>
        <div class="tlp-modal-footer">
            <button
                id="button-close"
                type="button"
                data-dismiss="modal"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
            >
                {{ $gettext("Cancel") }}
            </button>
            <export-top-level-x-l-s-x-button
                v-bind:current_query="current_query"
                v-on:hide-modal="hide()"
            />
            <export-with-links-x-l-s-x-button
                v-bind:current_query="current_query"
                v-on:hide-modal="hide()"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { EMITTER } from "../../../injection-symbols";
import { DISPLAY_XLSX_MODAL_EVENT } from "../../../helpers/widget-events";
import { strictInject } from "@tuleap/vue-strict-inject";
import ExportTopLevelXLSXButton from "./ExportTopLevelXLSXButton.vue";
import type { Query } from "../../../type";
import FeedbackMessage from "../../feedback/FeedbackMessage.vue";
import { useGettext } from "vue3-gettext";
import ExportWithLinksXLSXButton from "./ExportWithLinksXLSXButton.vue";

const emitter = strictInject(EMITTER);

const { $gettext, interpolate } = useGettext();

const modal_element = ref<HTMLDivElement>();
const modal = ref<Modal | null>(null);

const props = defineProps<{
    current_query: Query;
}>();
onMounted(() => {
    if (!modal_element.value) {
        return;
    }
    modal.value = createModal(modal_element.value);
    emitter.on(DISPLAY_XLSX_MODAL_EVENT, show);
});

onBeforeUnmount(() => {
    modal.value?.destroy();
    emitter.off(DISPLAY_XLSX_MODAL_EVENT, show);
});

function textContent(): string {
    return $gettext(
        `Your TQL results span multiple pages. Choose how you’d like to export:<br/>
         <strong>All</strong> artifacts (top-level only).<br/>
         Export the artifacts in the current page <strong>with their visible links</strong>.`,
    );
}

const subtitle_text = computed((): string => {
    return interpolate($gettext("Export of %{query_title}"), {
        query_title: props.current_query.title,
    });
});
function show(): void {
    modal.value?.show();
}

function hide(): void {
    modal.value?.hide();
}
</script>

<!--
  - Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
  -
  -->

<template>
    <Teleport to="#main-content">
        <div class="tracker-cross-report-document-modal">
            <div
                ref="modal_element"
                class="tlp-modal"
                role="dialog"
                aria-labelledby="cross-report-document-export-modal-title"
            >
                <div class="tlp-modal-header">
                    <h1 id="cross-report-document-export-modal-title" class="tlp-modal-title">
                        Export a cross tracker report document as .xlsx
                    </h1>
                    <button
                        class="tlp-modal-close"
                        type="button"
                        data-dismiss="modal"
                        aria-label="Close"
                    >
                        <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="tlp-modal-body">
                    <h2 class="tlp-modal-subtitle">Subtitle</h2>
                    <p>One fine body…</p>
                </div>
                <div class="tlp-modal-footer">
                    <button
                        type="button"
                        class="tlp-button-primary tlp-button-outline tlp-modal-action"
                        data-dismiss="modal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="tlp-button-primary tlp-modal-action"
                        v-bind:disabled="export_is_ongoing"
                        data-test="download-button"
                        v-on:click.prevent="startExport"
                    >
                        <i
                            aria-hidden="true"
                            class="tlp-button-icon fas"
                            v-bind:class="{
                                'fa-spin fa-circle-notch': export_is_ongoing,
                                'fa-download': !export_is_ongoing,
                            }"
                        ></i>
                        Export
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp/src/js/modal";
import { createModal } from "@tuleap/tlp/src/js/modal";
import type { GlobalExportProperties } from "../type";

const modal_element = ref<InstanceType<typeof HTMLElement>>();
let modal: Modal | null = null;

onMounted(() => {
    if (modal_element.value === undefined) {
        throw new Error("Cannot find modal root element");
    }
    modal = createModal(document, modal_element.value);
    modal.show();
});

onBeforeUnmount(() => {
    modal?.destroy();
});

const props = defineProps<{ properties: GlobalExportProperties }>();

const export_is_ongoing = ref(false);
async function startExport(): Promise<void> {
    export_is_ongoing.value = true;
    const export_document_module = import("../export-document");
    const download_xlsx_module = import("../Exporter/XLSX/download-xlsx");

    const { downloadXLSXDocument } = await export_document_module;
    const { downloadXLSX } = await download_xlsx_module;
    downloadXLSXDocument(props.properties, downloadXLSX);

    modal?.hide();
}
</script>
<style lang="scss" scoped>
/* stylelint-disable-next-line selector-pseudo-class-no-unknown -- Stylelint does not know about the Vue :deep() selector */
.tracker-cross-report-document-modal :deep() {
    @import "@tuleap/tlp/src/scss/components/typography";
    @import "@tuleap/tlp/src/scss/components/modal";

    .tlp-modal-footer {
        min-height: unset;
    }
}
</style>

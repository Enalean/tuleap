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
    <div
        id="config-tracker-cross-report-export-modal"
        ref="modal_element"
        class="tlp-modal"
        role="dialog"
        aria-labelledby="cross-report-document-export-modal-title"
    >
        <div class="tlp-modal-header">
            <h1 id="cross-report-document-export-modal-title" class="tlp-modal-title">
                {{ $gettext("Export a cross tracker report document as .xlsx") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div id="config-tracker-cross-report-body-export-modal" class="tlp-modal-body">
            <explanations-export />
            <fake-worksheet
                v-bind:tracker_name_level_1="props.properties.current_tracker_name"
                v-bind:tracker_name_level_2="selected_tracker_level_2?.label ?? null"
                v-bind:tracker_name_level_3="selected_tracker_level_3?.label ?? null"
            />
            <div class="level-selectors">
                <first-level-selector
                    v-model:report="selected_report_level_1"
                    v-model:artifact_link_types="artifact_link_types_level_1"
                    v-bind:tracker_id="properties.current_tracker_id"
                />
                <second-level-selector
                    v-bind:current_project_id="props.properties.current_project_id"
                    v-model:tracker="selected_tracker_level_2"
                    v-model:report="selected_report_level_2"
                    v-model:artifact_link_types="artifact_link_types_level_2"
                />
                <third-level-selector
                    v-bind:current_project_id="props.properties.current_project_id"
                    v-model:tracker="selected_tracker_level_3"
                    v-model:report="selected_report_level_3"
                />
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
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
                {{ $gettext("Export") }}
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import type { GlobalExportProperties, SelectedReport, SelectedTracker } from "../type";
import FirstLevelSelector from "./FirstLevelSelector.vue";
import ExplanationsExport from "./ExplanationsExport.vue";
import SecondLevelSelector from "./SecondLevelSelector.vue";
import ThirdLevelSelector from "./ThirdLevelSelector.vue";
import type { ExportSettings } from "../export-document";
import FakeWorksheet from "./FakeWorksheet.vue";

const modal_element = ref<InstanceType<typeof HTMLElement>>();
let modal: Modal | null = null;

onMounted(() => {
    if (modal_element.value === undefined) {
        throw new Error("Cannot find modal root element");
    }
    modal = createModal(modal_element.value);
    modal.show();
});

onBeforeUnmount(() => {
    modal?.destroy();
});

const props = defineProps<{ properties: GlobalExportProperties }>();
const selected_report_level_1 = ref<SelectedReport>({
    id: props.properties.current_report_id,
    label: "",
});
const artifact_link_types_level_1 = ref([]);

const selected_tracker_level_2 = ref<SelectedTracker | null>(null);
const selected_report_level_2 = ref<SelectedReport | null>(null);
const artifact_link_types_level_2 = ref([]);

const selected_tracker_level_3 = ref<SelectedTracker | null>(null);
const selected_report_level_3 = ref<SelectedReport | null>(null);

const export_is_ongoing = ref(false);
async function startExport(): Promise<void> {
    export_is_ongoing.value = true;
    const export_document_module = import("../export-document");
    const download_xlsx_module = import("../Exporter/XLSX/download-xlsx");

    const { downloadXLSXDocument } = await export_document_module;
    const { downloadXLSX } = await download_xlsx_module;
    let export_settings: ExportSettings = {
        first_level: {
            tracker_name: props.properties.current_tracker_name,
            report_id: selected_report_level_1.value.id,
            report_name: selected_report_level_1.value.label,
            artifact_link_types: artifact_link_types_level_1.value,
        },
    };
    if (selected_tracker_level_2.value !== null && selected_report_level_2.value !== null) {
        export_settings = {
            ...export_settings,
            second_level: {
                tracker_name: selected_tracker_level_2.value.label,
                report_id: selected_report_level_2.value.id,
                report_name: selected_report_level_2.value.label,
                artifact_link_types: artifact_link_types_level_2.value,
            },
        };
    }
    if (selected_tracker_level_3.value !== null && selected_report_level_3.value !== null) {
        export_settings = {
            ...export_settings,
            third_level: {
                tracker_name: selected_tracker_level_3.value.label,
                report_id: selected_report_level_3.value.id,
                report_name: selected_report_level_3.value.label,
            },
        };
    }
    await downloadXLSXDocument(export_settings, downloadXLSX);

    modal?.hide();
}
</script>
<style lang="scss" scoped>
#config-tracker-cross-report-export-modal {
    width: 850px;
}

#config-tracker-cross-report-body-export-modal {
    padding: 0;
}

.level-selectors {
    display: grid;
    grid-auto-columns: 1fr;
    grid-auto-flow: column;
    grid-gap: 1px;
    border-top: 1px solid var(--tlp-neutral-light-color);
    background-color: var(--tlp-neutral-light-color);
}
</style>

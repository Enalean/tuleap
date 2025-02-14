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
    <button
        type="button"
        class="tlp-button-primary tlp-button-small tlp-button-outline tlp-table-actions-element"
        v-bind:disabled="is_loading"
        v-on:click="exportXSLX()"
        data-test="export-xlsx-button"
    >
        <i
            aria-hidden="true"
            class="tlp-button-icon fa-solid"
            v-bind:class="{ 'fa-spin fa-circle-notch': is_loading, 'fa-download': !is_loading }"
            data-test="export-xlsx-button-icon"
        ></i>
        {{ $gettext("Export XSLX") }}
    </button>
</template>
<script setup lang="ts">
import { ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { useGettext } from "vue3-gettext";
import {
    CLEAR_FEEDBACKS,
    GET_COLUMN_NAME,
    NOTIFY_FAULT,
    REPORT_ID,
    RETRIEVE_ARTIFACTS_TABLE,
} from "../injection-symbols";
import { XLSXExportFault } from "../domain/XLSXExportFault";

const report_id = strictInject(REPORT_ID);
const artifact_table_retriever = strictInject(RETRIEVE_ARTIFACTS_TABLE);
const column_name_getter = strictInject(GET_COLUMN_NAME);

const clearFeedbacks = strictInject(CLEAR_FEEDBACKS);
const notifyFault = strictInject(NOTIFY_FAULT);

const props = defineProps<{
    query_id: string | null;
}>();

const is_loading = ref(false);
const { $gettext } = useGettext();

async function exportXSLX(): Promise<void> {
    if (props.query_id === null) {
        return;
    }
    is_loading.value = true;
    clearFeedbacks();
    const export_document_module = import("../helpers/exporter/export-document");
    const download_xlsx_module = import("../helpers/exporter/xlsx/download-xlsx");

    const { downloadXLSXDocument } = await export_document_module;
    const { downloadXLSX } = await download_xlsx_module;
    await downloadXLSXDocument(
        artifact_table_retriever,
        report_id,
        props.query_id,
        column_name_getter,
        downloadXLSX,
    ).mapErr((fault) => {
        notifyFault(XLSXExportFault(fault));
    });
    is_loading.value = false;
}
</script>

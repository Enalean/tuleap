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
        class="tlp-button-primary tlp-modal-action"
        v-bind:disabled="is_loading"
        v-on:click="exportXLSX()"
        data-test="export-xlsx-button"
    >
        <i
            aria-hidden="true"
            class="tlp-button-icon fa-solid"
            v-bind:class="{ 'fa-spin fa-circle-notch': is_loading, 'fa-download': !is_loading }"
            data-test="export-xlsx-button-icon"
        ></i>
        {{ $gettext("Export with links") }}
    </button>
</template>
<script setup lang="ts">
import { ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { useGettext } from "vue3-gettext";
import { EMITTER, GET_COLUMN_NAME, TABLE_DATA_STORE } from "../../../injection-symbols";
import { XLSXExportFault } from "../../../domain/XLSXExportFault";
import {
    type Events,
    NOTIFY_FAULT_EVENT,
    STARTING_XLSX_EXPORT_EVENT,
} from "../../../helpers/widget-events";
import type { Query } from "../../../type";
import type { GetColumnName } from "../../../domain/ColumnNameGetter";
import type { Emitter } from "mitt";
import type { TableDataStore } from "../../../domain/TableDataStore";

const column_name_getter: GetColumnName = strictInject(GET_COLUMN_NAME);
const emitter: Emitter<Events> = strictInject(EMITTER);
const table_collection: TableDataStore = strictInject(TABLE_DATA_STORE);

const props = defineProps<{
    current_query: Query;
}>();

const emit = defineEmits<{
    (e: "hide-modal"): void;
}>();

const is_loading = ref(false);
const { $gettext } = useGettext();

async function exportXLSX(): Promise<void> {
    is_loading.value = true;
    emitter.emit(STARTING_XLSX_EXPORT_EVENT);
    const export_document_module = import("../../../helpers/exporter/export-document");
    const download_xlsx_module = import("../../../helpers/exporter/xlsx/download-xlsx");

    const { downloadXLSXWithLinkDocument } = await export_document_module;
    const { downloadXLSX } = await download_xlsx_module;
    await downloadXLSXWithLinkDocument(
        table_collection,
        column_name_getter,
        props.current_query,
        downloadXLSX,
    ).match(
        () => emit("hide-modal"),
        (fault) => {
            emitter.emit(NOTIFY_FAULT_EVENT, { fault: XLSXExportFault(fault) });
        },
    );
    is_loading.value = false;
}
</script>

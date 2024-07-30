<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <button
        type="button"
        class="tlp-button-primary tlp-button-small tlp-button-outline tlp-table-actions-element"
        v-bind:disabled="is_loading"
        v-on:click="exportCSV()"
        data-test="export-csv-button"
    >
        <i
            aria-hidden="true"
            class="tlp-button-icon fa-solid"
            v-bind:class="{ 'fa-spin fa-circle-notch': is_loading, 'fa-download': !is_loading }"
        ></i>
        {{ $gettext("Export CSV") }}
    </button>
</template>
<script setup lang="ts">
import { ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { useGettext } from "vue3-gettext";
import { download } from "../helpers/download-helper";
import { addBOM } from "../helpers/bom-helper";
import { getCSVReport } from "../api/rest-querier";
import { CLEAR_FEEDBACKS, NOTIFY_FAULT, REPORT_ID } from "../injection-symbols";
import { CSVExportFault } from "../domain/CSVExportFault";

const notifyFault = strictInject(NOTIFY_FAULT);
const clearFeedbacks = strictInject(CLEAR_FEEDBACKS);
const report_id = strictInject(REPORT_ID);

const is_loading = ref(false);
const { $gettext } = useGettext();

function exportCSV(): void {
    is_loading.value = true;
    clearFeedbacks();
    getCSVReport(report_id)
        .match(
            (report) => {
                const report_with_bom = addBOM(report);
                download(report_with_bom, `export-${report_id}.csv`, "text/csv;encoding:utf-8");
            },
            (fault) => {
                notifyFault(CSVExportFault(fault));
            },
        )
        .then(() => {
            is_loading.value = false;
        });
}
</script>

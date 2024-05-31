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
        v-if="should_display_export_button"
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
import { useGetters, useMutations, useState } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import { download } from "../helpers/download-helper";
import { addBOM } from "../helpers/bom-helper";
import { getCSVReport } from "../api/rest-querier";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

const is_loading = ref(false);
const { report_id } = useState(["report_id"]);
const { should_display_export_button } = useGetters(["should_display_export_button"]);
const { resetFeedbacks, setErrorMessage } = useMutations(["resetFeedbacks", "setErrorMessage"]);
const gettext_provider = useGettext();

async function exportCSV(): Promise<void> {
    is_loading.value = true;
    resetFeedbacks();
    try {
        const report = await getCSVReport(report_id.value);
        const report_with_bom = addBOM(report);
        download(report_with_bom, `export-${report_id.value}.csv`, "text/csv;encoding:utf-8");
    } catch (error) {
        if (!(error instanceof FetchWrapperError)) {
            throw error;
        }
        if (error.response.status.toString().substring(0, 2) === "50") {
            setErrorMessage(gettext_provider.$gettext("An error occurred"));
            return;
        }
        const message = await error.response.text();
        setErrorMessage(message);
    } finally {
        is_loading.value = false;
    }
}
</script>

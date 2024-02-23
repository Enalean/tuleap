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
        data-test="export-cvs-button"
    >
        <i
            aria-hidden="true"
            class="tlp-button-icon fa-solid"
            v-bind:class="{ 'fa-spin fa-circle-notch': is_loading, 'fa-download': !is_loading }"
        ></i>
        <translate>Export CSV</translate>
    </button>
</template>
<script lang="ts">
import { download } from "../helpers/download-helper";
import { addBOM } from "../helpers/bom-helper";
import { getCSVReport } from "../api/rest-querier";
import Vue from "vue";
import Component from "vue-class-component";
import { State, Getter } from "vuex-class";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

@Component
export default class ExportCSVButton extends Vue {
    @State
    private readonly report_id!: number;

    @Getter
    readonly should_display_export_button!: boolean;

    is_loading = false;

    async exportCSV(): Promise<void> {
        this.is_loading = true;
        this.$store.commit("resetFeedbacks");
        try {
            const report = await getCSVReport(this.report_id);
            const report_with_bom = addBOM(report);
            download(report_with_bom, `export-${this.report_id}.csv`, "text/csv;encoding:utf-8");
        } catch (error) {
            if (!(error instanceof FetchWrapperError)) {
                throw error;
            }
            if (error.response.status.toString().substring(0, 2) === "50") {
                this.$store.commit("setErrorMessage", this.$gettext("An error occurred"));
                return;
            }
            const message = await error.response.text();
            this.$store.commit("setErrorMessage", message);
        } finally {
            this.is_loading = false;
        }
    }
}
</script>

<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    >
        <i
            class="tlp-button-icon fa fa-download"
            v-bind:class="{ 'fa-spin fa-circle-o-notch': is_loading }"
        ></i>
        <translate>Export CSV</translate>
    </button>
</template>
<script>
import { mapState, mapGetters } from "vuex";
import { download } from "../helpers/download-helper.js";
import { addBOM } from "../helpers/bom-helper.js";
import { getCSVReport } from "../api/rest-querier.js";

export default {
    name: "ExportCSVButton",
    data() {
        return {
            is_loading: false,
        };
    },
    computed: {
        ...mapState(["report_id"]),
        ...mapGetters(["should_display_export_button"]),
    },
    methods: {
        exportCSV: async function () {
            this.is_loading = true;
            this.$store.commit("resetFeedbacks");
            try {
                const report = await getCSVReport(this.report_id);
                const report_with_bom = addBOM(report);
                download(
                    report_with_bom,
                    `export-${this.report_id}.csv`,
                    "text/csv;encoding:utf-8"
                );
            } catch (error) {
                if (error.response.status.toString().substring(0, 2) === "50") {
                    this.$store.commit("setErrorMessage", this.$gettext("An error occurred"));
                    return;
                }
                const message = await error.response.text();
                this.$store.commit("setErrorMessage", message);
            } finally {
                this.is_loading = false;
            }
        },
    },
};
</script>

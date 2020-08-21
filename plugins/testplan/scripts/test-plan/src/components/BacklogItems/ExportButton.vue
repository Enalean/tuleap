<!--
  - Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
    <div>
        <button
            class="tlp-button tlp-button-primary tlp-button-outline tlp-button-small test-plan-export-button"
            v-bind:disabled="!can_export"
            v-on:click="exportTestPlan"
            data-test="testplan-export-button"
        >
            <i
                class="fa tlp-button-icon"
                v-bind:class="icon_classes"
                aria-hidden="true"
                data-test="download-export-button-icon"
            ></i>
            <translate>Export</translate>
        </button>
        <export-error v-if="has_encountered_error_during_the_export" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { namespace, State } from "vuex-class";
import { BacklogItem, Campaign } from "../../type";
import ExportError from "./ExportError.vue";

const backlog_item = namespace("backlog_item");
const campaign = namespace("campaign");
@Component({
    components: {
        ExportError,
    },
})
export default class ExportButton extends Vue {
    @backlog_item.State("is_loading")
    readonly backlog_items_is_loading!: boolean;

    @backlog_item.State("has_loading_error")
    readonly backlog_items_has_loading_error!: boolean;

    @backlog_item.State
    readonly backlog_items!: ReadonlyArray<BacklogItem>;

    @campaign.State("is_loading")
    readonly campains_is_loading!: boolean;

    @campaign.State("has_loading_error")
    readonly campains_has_loading_error!: boolean;

    @campaign.State
    readonly campaigns!: ReadonlyArray<Campaign>;

    @State
    readonly project_name!: string;

    @State
    readonly milestone_title!: string;

    @State
    readonly user_display_name!: string;

    private is_preparing_the_download = false;

    private has_encountered_error_during_the_export = false;

    get can_export(): boolean {
        return (
            !this.backlog_items_is_loading &&
            !this.backlog_items_has_loading_error &&
            !this.campains_is_loading &&
            !this.campains_has_loading_error
        );
    }

    get icon_classes(): string {
        if (this.is_preparing_the_download) {
            return "fa-spin fa-circle-o-notch";
        }

        return "fa-download";
    }

    async exportTestPlan(): Promise<void> {
        if (this.is_preparing_the_download) {
            return;
        }
        this.is_preparing_the_download = true;
        this.has_encountered_error_during_the_export = false;

        try {
            const { downloadExportDocument } = await import(
                /* webpackChunkName: "testplan-download-export-sheet" */ "../../helpers/Export/download-export-document"
            );
            const { downloadXLSX } = await import(
                /* webpackChunkName: "testplan-download-xlsx-export-sheet" */ "../../helpers/Export/Exporter/XLSX/download-xlsx"
            );
            await downloadExportDocument(
                this,
                downloadXLSX,
                this.project_name,
                this.milestone_title,
                this.user_display_name,
                this.backlog_items,
                this.campaigns
            );
        } catch (e) {
            this.has_encountered_error_during_the_export = true;
            throw e;
        } finally {
            this.is_preparing_the_download = false;
        }
    }
}
</script>

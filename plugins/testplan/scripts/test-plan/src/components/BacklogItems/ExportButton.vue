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
        <div class="tlp-dropdown test-plan-export-button">
            <button
                type="button"
                v-bind:disabled="!can_export"
                class="tlp-button-primary tlp-button-outline tlp-button-small"
                data-test="testplan-export-button"
                ref="trigger"
            >
                <i
                    class="fa tlp-button-icon"
                    v-bind:class="icon_classes"
                    aria-hidden="true"
                    data-test="download-export-button-icon"
                ></i>
                <translate>Export</translate>
                <i class="fas fa-caret-down tlp-button-icon-right" aria-hidden="true"></i>
            </button>
            <div class="tlp-dropdown-menu" role="menu">
                <a
                    href="#"
                    v-on:click.prevent="exportTestPlanAsDocx"
                    v-if="is_docx_allowed"
                    class="tlp-dropdown-menu-item"
                    role="menuitem"
                    data-test="testplan-export-docx-button"
                >
                    <translate>Export as document</translate>
                </a>
                <a
                    href="#"
                    v-on:click.prevent="exportTestPlanAsXlsx"
                    class="tlp-dropdown-menu-item"
                    role="menuitem"
                    data-test="testplan-export-xlsx-button"
                >
                    <translate>Export as spreadsheet</translate>
                </a>
            </div>
        </div>
        <export-error v-if="has_encountered_error_during_the_export" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { namespace, State } from "vuex-class";
import type { BacklogItem, Campaign } from "../../type";
import ExportError from "./ExportError.vue";
import { createDropdown } from "tlp";
import { isFeatureFlagDocxEnabled } from "../../helpers/ExportAsDocument/feature-flag-docx";
import type { ArtifactLinkType } from "@tuleap/plugin-docgen-docx";

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
    readonly parent_milestone_title!: string;

    @State
    readonly user_display_name!: string;

    @State
    readonly platform_name!: string;

    @State
    readonly platform_logo_url!: string;

    @State
    readonly user_timezone!: string;

    @State
    readonly user_locale!: string;

    @State
    readonly milestone_url!: string;

    @State
    readonly base_url!: string;

    @State
    readonly artifact_links_types!: ReadonlyArray<ArtifactLinkType>;

    private is_preparing_the_download = false;

    private has_encountered_error_during_the_export = false;

    override $refs!: {
        trigger: HTMLElement;
    };

    mounted(): void {
        createDropdown(this.$refs.trigger);
    }

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

    get is_docx_allowed(): boolean {
        return isFeatureFlagDocxEnabled(document);
    }

    async exportTestPlanAsXlsx(): Promise<void> {
        if (this.is_preparing_the_download) {
            return;
        }
        this.is_preparing_the_download = true;
        this.has_encountered_error_during_the_export = false;

        try {
            const { downloadExportDocument } = await import(
                /* webpackChunkName: "testplan-download-export-sheet" */ "../../helpers/ExportAsSpreadsheet/download-export-document"
            );
            const { downloadXLSX } = await import(
                /* webpackChunkName: "testplan-download-xlsx-export-sheet" */ "../../helpers/ExportAsSpreadsheet/Exporter/XLSX/download-xlsx"
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

    async exportTestPlanAsDocx(): Promise<void> {
        if (this.is_preparing_the_download) {
            return;
        }
        this.is_preparing_the_download = true;
        this.has_encountered_error_during_the_export = false;

        try {
            const { downloadExportDocument } = await import(
                /* webpackChunkName: "testplan-download-export-doc" */ "../../helpers/ExportAsDocument/download-export-document"
            );
            const { downloadDocx } = await import(
                /* webpackChunkName: "testplan-download-docx-export-doc" */ "../../helpers/ExportAsDocument/Exporter/DOCX/download-docx"
            );
            await downloadExportDocument(
                {
                    platform_name: this.platform_name,
                    platform_logo_url: this.platform_logo_url,
                    project_name: this.project_name,
                    user_display_name: this.user_display_name,
                    user_timezone: this.user_timezone,
                    user_locale: this.user_locale,
                    milestone_name: this.milestone_title,
                    parent_milestone_name: this.parent_milestone_title,
                    milestone_url: this.milestone_url,
                    base_url: this.base_url,
                    artifact_links_types: this.artifact_links_types,
                },
                this,
                downloadDocx,
                this.backlog_items
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

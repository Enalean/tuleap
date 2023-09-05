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
                ref="trigger"
                type="button"
                v-bind:disabled="!can_export"
                class="tlp-button-primary tlp-button-outline tlp-button-small"
                data-test="testplan-export-button"
            >
                <i
                    class="fa tlp-button-icon"
                    v-bind:class="icon_classes"
                    aria-hidden="true"
                    data-test="download-export-button-icon"
                ></i>
                {{ $gettext("Export") }}
                <i class="fas fa-caret-down tlp-button-icon-right" aria-hidden="true"></i>
            </button>
            <div class="tlp-dropdown-menu" role="menu">
                <a
                    href="#"
                    class="tlp-dropdown-menu-item"
                    role="menuitem"
                    data-test="testplan-export-docx-button"
                    v-on:click.prevent="exportTestPlanAsDocx"
                >
                    {{ $gettext("Export as document") }}
                </a>
                <a
                    href="#"
                    class="tlp-dropdown-menu-item"
                    role="menuitem"
                    data-test="testplan-export-xlsx-button"
                    v-on:click.prevent="exportTestPlanAsXlsx"
                >
                    {{ $gettext("Export as spreadsheet") }}
                </a>
            </div>
        </div>
        <export-error v-if="has_encountered_error_during_the_export" />
    </div>
</template>
<script setup lang="ts">
import ExportError from "./ExportError.vue";
import { useNamespacedState, useState } from "vuex-composition-helpers";
import type { State } from "../../store/type";
import type { BacklogItemState } from "../../store/backlog-item/type";
import type { CampaignState } from "../../store/campaign/type";
import { computed, onMounted, ref } from "vue";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { useGettext } from "vue3-gettext";

const {
    is_loading: backlog_items_is_loading,
    has_loading_error: backlog_items_has_loading_error,
    backlog_items,
} = useNamespacedState<
    Pick<BacklogItemState, "is_loading" | "has_loading_error" | "backlog_items">
>("backlog_item", ["is_loading", "has_loading_error", "backlog_items"]);
const {
    is_loading: campains_is_loading,
    has_loading_error: campains_has_loading_error,
    campaigns,
} = useNamespacedState<Pick<CampaignState, "is_loading" | "has_loading_error" | "campaigns">>(
    "campaign",
    ["is_loading", "has_loading_error", "campaigns"],
);
const {
    project_name,
    milestone_title,
    parent_milestone_title,
    user_display_name,
    platform_name,
    platform_logo_url,
    user_timezone,
    user_locale,
    milestone_url,
    base_url,
    artifact_links_types,
    testdefinition_tracker_id,
} = useState<
    Pick<
        State,
        | "project_name"
        | "milestone_title"
        | "parent_milestone_title"
        | "user_display_name"
        | "platform_name"
        | "platform_logo_url"
        | "user_timezone"
        | "user_locale"
        | "milestone_url"
        | "base_url"
        | "artifact_links_types"
        | "testdefinition_tracker_id"
    >
>([
    "project_name",
    "milestone_title",
    "parent_milestone_title",
    "user_display_name",
    "platform_name",
    "platform_logo_url",
    "user_timezone",
    "user_locale",
    "milestone_url",
    "base_url",
    "artifact_links_types",
    "testdefinition_tracker_id",
]);

const trigger = ref<InstanceType<typeof HTMLElement>>();

onMounted((): void => {
    if (trigger.value) {
        createDropdown(trigger.value);
    }
});

const can_export = computed((): boolean => {
    return (
        !backlog_items_is_loading.value &&
        !backlog_items_has_loading_error.value &&
        !campains_is_loading.value &&
        !campains_has_loading_error.value
    );
});

const is_preparing_the_download = ref(false);
const has_encountered_error_during_the_export = ref(false);

const icon_classes = computed((): string => {
    if (is_preparing_the_download.value) {
        return "fa-spin fa-circle-o-notch";
    }

    return "fa-download";
});

const gettext_provider = useGettext();

async function exportTestPlanAsXlsx(): Promise<void> {
    if (is_preparing_the_download.value) {
        return;
    }
    is_preparing_the_download.value = true;
    has_encountered_error_during_the_export.value = false;

    try {
        const { downloadExportDocument } = await import(
            /* webpackChunkName: "testplan-download-export-sheet" */ "../../helpers/ExportAsSpreadsheet/download-export-document"
        );
        const { downloadXLSX } = await import(
            /* webpackChunkName: "testplan-download-xlsx-export-sheet" */ "../../helpers/ExportAsSpreadsheet/Exporter/XLSX/download-xlsx"
        );
        await downloadExportDocument(
            gettext_provider,
            downloadXLSX,
            project_name.value,
            milestone_title.value,
            user_display_name.value,
            backlog_items.value,
            campaigns.value,
        );
    } catch (e) {
        has_encountered_error_during_the_export.value = true;
        throw e;
    } finally {
        is_preparing_the_download.value = false;
    }
}

async function exportTestPlanAsDocx(): Promise<void> {
    if (is_preparing_the_download.value) {
        return;
    }
    is_preparing_the_download.value = true;
    has_encountered_error_during_the_export.value = false;

    try {
        const { downloadExportDocument } = await import(
            /* webpackChunkName: "testplan-download-export-doc" */ "../../helpers/ExportAsDocument/download-export-document"
        );
        const { downloadDocx } = await import(
            /* webpackChunkName: "testplan-download-docx-export-doc" */ "../../../../../../testmanagement/scripts/testmanagement/src/helpers/ExportAsDocument/Exporter/DOCX/download-docx"
        );
        await downloadExportDocument(
            {
                platform_name: platform_name.value,
                platform_logo_url: platform_logo_url.value,
                project_name: project_name.value,
                user_display_name: user_display_name.value,
                user_timezone: user_timezone.value,
                user_locale: user_locale.value,
                title: milestone_title.value,
                milestone_name: milestone_title.value,
                parent_milestone_name: parent_milestone_title.value,
                milestone_url: milestone_url.value,
                base_url: base_url.value,
                artifact_links_types: artifact_links_types.value,
                testdefinition_tracker_id: testdefinition_tracker_id.value,
            },
            gettext_provider,
            downloadDocx,
            backlog_items.value,
            campaigns.value,
        );
    } catch (e) {
        has_encountered_error_during_the_export.value = true;
        throw e;
    } finally {
        is_preparing_the_download.value = false;
    }
}
</script>

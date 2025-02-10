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
    <div class="tlp-dropdown">
        <button
            type="button"
            class="tlp-button-primary tlp-button-outline tlp-button-mini"
            ref="dropdown_trigger"
        >
            {{ $gettext("Choose another query") }}
            <i class="fa-solid fa-caret-down tlp-button-icon-right" aria-hidden="true"></i>
        </button>
        <div class="tlp-dropdown-menu dropdown-menu-filter" role="menu" ref="dropdown_menu">
            <div
                v-for="query in getQueries()"
                v-bind:key="report_id + query.expert_query"
                class="tlp-dropdown-menu-item"
                role="menuitem"
                v-on:click.prevent="updateDisplayedQuery(query)"
                data-test="query"
            >
                {{ query.title }}
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { EMITTER, REPORT_ID } from "../../injection-symbols";
import type { ReadingCrossTrackerReport } from "../../domain/ReadingCrossTrackerReport";
import type { WritingCrossTrackerReport } from "../../domain/WritingCrossTrackerReport";
import type { Report } from "../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { ALL_OPEN_ARTIFACT_IN_PROJECT_EXAMPLE } from "../../domain/Query";

const dropdown_trigger = ref<HTMLElement>();
const dropdown_menu = ref<HTMLElement>();
let dropdown: Dropdown | null = null;

const report_id = strictInject(REPORT_ID);
const emitter = strictInject(EMITTER);

const props = defineProps<{
    writing_cross_tracker_report: WritingCrossTrackerReport;
    reading_cross_tracker_report: ReadingCrossTrackerReport;
    queries: ReadonlyArray<Report>;
}>();

onMounted((): void => {
    if (dropdown_trigger.value && dropdown_menu.value) {
        dropdown = createDropdown(dropdown_trigger.value, {
            trigger: "click",
            dropdown_menu: dropdown_menu.value,
            keyboard: true,
        });
    }
});

function getQueries(): ReadonlyArray<Report> {
    return [...props.queries, ALL_OPEN_ARTIFACT_IN_PROJECT_EXAMPLE];
}

function updateDisplayedQuery(query: Report): void {
    if (report_id !== undefined) {
        props.writing_cross_tracker_report.setExpertQuery(query.expert_query);
        props.reading_cross_tracker_report.setNewQuery(query.expert_query);
        emitter.emit("refresh-artifacts", { query });
        emitter.emit("update-chosen-query-display");
        dropdown?.hide();
    }
}
onBeforeUnmount(() => {
    dropdown?.destroy();
});
</script>

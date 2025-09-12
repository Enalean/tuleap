<!--
  - Copyright Enalean (c) 2019 - Present. All rights reserved.
  -
  -  Tuleap and Enalean names and logos are registrated trademarks owned by
  -  Enalean SAS. All other trademarks or names are properties of their respective
  -  owners.
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
  -
  -->

<template>
    <div>
        <div
            class="timetracking-reading-mode"
            data-test="overview-toggle-reading-mode"
            v-on:click="overview_store.toggleReadingMode()"
        >
            <time-tracking-overview-reading-dates />
            <time-tracking-overview-tracker-list />
        </div>
        <div
            class="reading-mode-actions"
            v-if="!overview_store.is_report_saved"
            data-test="reading-mode-actions"
        >
            <button
                class="tlp-button-primary tlp-button-outline reading-mode-actions-cancel"
                v-on:click="discardReport()"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                class="tlp-button-primary"
                v-on:click="saveReport()"
                data-test="save-overview-report"
            >
                <i
                    v-if="overview_store.is_loading"
                    class="tlp-button-icon fa fa-spinner fa-spin"
                    data-test="icon-spinner"
                ></i>
                {{ $gettext("Save report") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { REPORT_ID } from "../../injection-symbols";
import { useOverviewWidgetStore } from "../../store";
import TimeTrackingOverviewTrackerList from "./TimeTrackingOverviewTrackerList.vue";
import TimeTrackingOverviewReadingDates from "./TimeTrackingOverviewReadingDates.vue";

const { $gettext } = useGettext();
const overview_store = useOverviewWidgetStore(strictInject(REPORT_ID))();

function saveReport(): void {
    overview_store.saveReport($gettext("Report has been successfully saved"));
}

function discardReport(): void {
    overview_store.initWidgetWithReport();
    overview_store.setIsReportSave(true);
}
</script>

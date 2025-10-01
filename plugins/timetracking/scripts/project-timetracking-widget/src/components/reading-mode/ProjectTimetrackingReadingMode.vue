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
            data-test="project-timetracking-toggle-reading-mode"
            v-on:click="project_timetracking_store.toggleReadingMode()"
        >
            <project-timetracking-reading-dates />
            <project-timetracking-tracker-list />
        </div>
        <div
            class="reading-mode-actions"
            v-if="!project_timetracking_store.is_report_saved"
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
                data-test="save-project-timetracking-report"
            >
                <i
                    v-if="project_timetracking_store.is_loading"
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
import { useProjectTimetrackingWidgetStore } from "../../store";
import ProjectTimetrackingTrackerList from "./ProjectTimetrackingTrackerList.vue";
import ProjectTimetrackingReadingDates from "./ProjectTimetrackingReadingDates.vue";

const { $gettext } = useGettext();
const project_timetracking_store = useProjectTimetrackingWidgetStore(strictInject(REPORT_ID))();

function saveReport(): void {
    project_timetracking_store.saveReport($gettext("Report has been successfully saved"));
}

function discardReport(): void {
    project_timetracking_store.initWidgetWithReport();
    project_timetracking_store.setIsReportSave(true);
}
</script>

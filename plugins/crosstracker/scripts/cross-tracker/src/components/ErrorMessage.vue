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
    <div class="tlp-alert-danger cross-tracker-report-error" v-if="error_message.isValue()">
        {{ error_message.unwrapOr("") }}
    </div>
</template>
<script setup lang="ts">
import { computed } from "vue";
import type { Option } from "@tuleap/option";
import type { Fault } from "@tuleap/fault";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const props = defineProps<{ fault: Option<Fault> }>();

const error_message = computed<Option<string>>(() =>
    props.fault.map((fault) => {
        if ("isProjectsRetrieval" in fault && fault.isProjectsRetrieval() === true) {
            return $gettext(
                "Error while fetching the list of projects you are member of: %{error}",
                { error: String(fault) },
                true,
            );
        }
        if ("isTrackersRetrieval" in fault && fault.isTrackersRetrieval() === true) {
            return $gettext(
                "Error while fetching the list of trackers of this project: %{error}",
                { error: String(fault) },
                true,
            );
        }
        if ("isArtifactsRetrieval" in fault && fault.isArtifactsRetrieval() === true) {
            return $gettext(
                "Error while fetching the artifacts matching the query: %{error}",
                { error: String(fault) },
                true,
            );
        }
        if ("isReportRetrieval" in fault && fault.isReportRetrieval() === true) {
            return $gettext(
                "Error while fetching the report: %{error}",
                { error: String(fault) },
                true,
            );
        }
        if ("isSaveReport" in fault && fault.isSaveReport() === true) {
            return $gettext(
                "Error while saving the report: %{error}",
                { error: String(fault) },
                true,
            );
        }
        if ("isCSVExport" in fault && fault.isCSVExport() === true) {
            return $gettext(
                "Error while exporting the report to CSV: %{error}",
                { error: String(fault) },
                true,
            );
        }
        if ("isMaxTrackersSelected" in fault && fault.isMaxTrackersSelected() === true) {
            return $gettext("Tracker selection is limited to 25 trackers");
        }
        return $gettext("An error occurred: %{error}", { error: String(fault) }, true);
    }),
);
</script>

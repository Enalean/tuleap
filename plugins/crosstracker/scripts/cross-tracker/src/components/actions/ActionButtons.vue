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
    <choose-query-button
        v-bind:writing_cross_tracker_report="writing_cross_tracker_report"
        v-bind:reading_cross_tracker_report="reading_cross_tracker_report"
        v-bind:queries="queries"
        v-bind:selected_query="selected_query"
    />
</template>

<script setup lang="ts">
import ChooseQueryButton from "./ChooseQueryButton.vue";
import type { WritingCrossTrackerReport } from "../../domain/WritingCrossTrackerReport";
import type { ReadingCrossTrackerReport } from "../../domain/ReadingCrossTrackerReport";
import type { Report } from "../../type";

defineProps<{
    writing_cross_tracker_report: WritingCrossTrackerReport;
    reading_cross_tracker_report: ReadingCrossTrackerReport;
    queries: ReadonlyArray<Report>;
    selected_query: Report | null;
}>();
</script>

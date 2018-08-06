/**
* Copyright (c) Enalean, 2018. All Rights Reserved.
*
* This file is a part of Tuleap.
*
* Tuleap is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* Tuleap is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
*/

(
<template>
    <div class="timetracking-widget">
        <widget-reading-mode v-if="reading_mode"
             v-on:switchToWritingMode="switchToWritingMode"
             v-bind:startDate="start_date"
             v-bind:endDate="end_date"
        />
        <widget-writing-mode v-else
             v-on:switchToReadingMode="switchToReadingMode"
             v-bind:readingStartDate="start_date"
             v-bind:readingEndDate="end_date"
        />
        <widget-artifact-table
            v-bind:is-in-reading-mode="reading_mode"
            v-bind:has-query-changed="query_has_changed"
            v-bind:startDate="start_date"
            v-bind:endDate="end_date"
        />
    </div>
</template>
)
(
<script>
import { DateTime } from "luxon";
import WidgetReadingMode from "./WidgetReadingMode.vue";
import WidgetWritingMode from "./WidgetWritingMode.vue";
import WidgetArtifactTable from "./WidgetArtifactTable.vue";

export default {
    name: "Widget",
    components: {
        WidgetReadingMode,
        WidgetWritingMode,
        WidgetArtifactTable
    },
    data() {
        const start_date = DateTime.local()
            .minus({ weeks: 1 })
            .toISODate();
        const end_date = DateTime.local().toISODate();

        return {
            reading_mode: true,
            query_has_changed: false,
            start_date,
            end_date
        };
    },
    methods: {
        switchToWritingMode() {
            this.reading_mode = false;
        },

        switchToReadingMode(data) {
            if (data) {
                const { start_date, end_date } = data;

                this.start_date = start_date;
                this.end_date = end_date;
                this.reading_mode = true;
                this.query_has_changed = true;

                return;
            }

            this.reading_mode = true;
            this.query_has_changed = false;
        }
    }
};
</script>
)

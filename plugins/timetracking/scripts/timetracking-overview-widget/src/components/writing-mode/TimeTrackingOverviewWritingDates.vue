<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <div class="timetracking-overview-writing-mode-selected-dates">
        <div class="tlp-form-element timetracking-overview-writing-mode-selected-date">
            <label for="timetracking-start-date" class="tlp-label">
                {{ $gettext("From") }}
                <i class="fa fa-asterisk"></i>
            </label>
            <div class="tlp-form-element tlp-form-element-prepend">
                <span class="tlp-prepend">
                    <i class="fas fa-calendar-alt"></i>
                </span>
                <input
                    type="text"
                    class="tlp-input tlp-input-date"
                    id="timetracking-start-date"
                    ref="start_date"
                    v-bind:value="overview_store.start_date"
                    size="11"
                />
            </div>
        </div>
        <div class="tlp-form-element timetracking-overview-writing-mode-selected-date">
            <label for="timetracking-end-date" class="tlp-label">
                {{ $gettext("To") }}
                <i class="fa fa-asterisk"></i>
            </label>
            <div class="tlp-form-element tlp-form-element-prepend">
                <span class="tlp-prepend">
                    <i class="fas fa-calendar-alt"></i>
                </span>
                <input
                    type="text"
                    class="tlp-input tlp-input-date"
                    id="timetracking-end-date"
                    ref="end_date"
                    v-bind:value="overview_store.end_date"
                    size="11"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { datePicker } from "@tuleap/tlp-date-picker";
import { REPORT_ID } from "../../injection-symbols";
import { useOverviewWidgetStore } from "../../store";

const { $gettext } = useGettext();

const overview_store = useOverviewWidgetStore(strictInject(REPORT_ID))();

const start_date = ref<HTMLInputElement>();
const end_date = ref<HTMLInputElement>();

onMounted(() => {
    if (start_date.value) {
        datePicker(start_date.value, {
            onClose(long_date, short_date) {
                overview_store.setStartDate(short_date);
            },
        });
    }

    if (end_date.value) {
        datePicker(end_date.value, {
            onClose(long_date, short_date) {
                overview_store.setEndDate(short_date);
            },
        });
    }
});
</script>

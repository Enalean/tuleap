<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
    <tbody>
        <tr v-for="(entry, index) in log_entries" v-bind:key="'log-' + index">
            <td>
                <tlp-relative-date
                    v-bind:date="entry.when"
                    v-bind:absolute-date="formatted_full_date(entry.when)"
                    v-bind:placement="relative_date_placement"
                    v-bind:preference="relative_date_preference"
                    v-bind:locale="user_locale"
                >
                    {{ formatted_full_date }}
                </tlp-relative-date>
            </td>
            <td>
                <user-badge v-bind:user="entry.who" />
            </td>
            <td>{{ entry.what }}</td>
            <template v-if="entry.old_value !== null">
                <td
                    v-bind:colspan="entry.new_value === null ? 2 : 1"
                    v-bind:class="{
                        'document-history-merged-values': entry.new_value === null,
                    }"
                >
                    {{ entry.old_value }}
                </td>
            </template>
            <template v-if="entry.new_value !== null">
                <td
                    v-bind:colspan="entry.old_value === null ? 2 : 1"
                    v-bind:class="{
                        'document-history-merged-values': entry.old_value === null,
                    }"
                >
                    {{ entry.new_value }}
                </td>
            </template>
            <template v-if="entry.old_value === null && entry.new_value === null">
                <td colspan="2" class="document-history-merged-values">
                    <a v-if="entry.diff_link" v-bind:href="entry.diff_link">{{
                        $gettext("diff")
                    }}</a>
                </td>
            </template>
        </tr>
    </tbody>
</template>

<script setup lang="ts">
import UserBadge from "../User/UserBadge.vue";
import type { LogEntry } from "../../api/log-rest-querier";
import { useState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../store/configuration";
import { formatDateUsingPreferredUserFormat } from "../../helpers/date-formatter";
import { computed } from "vue";
import { relativeDatePlacement, relativeDatePreference } from "@tuleap/tlp-relative-date";

defineProps<{ log_entries: readonly LogEntry[] }>();

const { date_time_format, relative_dates_display, user_locale } = useState<
    Pick<ConfigurationState, "date_time_format" | "relative_dates_display" | "user_locale">
>("configuration", ["date_time_format", "relative_dates_display", "user_locale"]);

const formatted_full_date = (date: string): string => {
    return formatDateUsingPreferredUserFormat(date, date_time_format.value);
};

const relative_date_preference = computed((): string => {
    return relativeDatePreference(relative_dates_display.value);
});

const relative_date_placement = computed((): string => {
    return relativeDatePlacement(relative_dates_display.value, "top");
});
</script>

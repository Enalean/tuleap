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
        <tr v-for="version in versions" v-bind:key="version.id">
            <td class="tlp-table-cell-numeric">
                <a v-bind:href="version.download_href">{{ version.number }}</a>
            </td>
            <td>
                <tlp-relative-date
                    v-bind:date="version.date"
                    v-bind:absolute-date="formatted_full_date(version.date)"
                    v-bind:placement="relative_date_placement"
                    v-bind:preference="relative_date_preference"
                    v-bind:locale="user_locale"
                >
                    {{ formatted_full_date }}
                </tlp-relative-date>
            </td>
            <td>
                <user-badge v-bind:user="version.author" />
            </td>
            <td>{{ version.name }}</td>
            <td>{{ version.changelog }}</td>
            <td>
                <a
                    v-if="version.approval_href"
                    v-bind:href="version.approval_href"
                    data-test="approval-link"
                    >{{ $gettext("Show") }}</a
                >
            </td>
            <td class="tlp-table-cell-actions"></td>
        </tr>
    </tbody>
</template>

<script setup lang="ts">
import UserBadge from "../User/UserBadge.vue";
import { useState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../store/configuration";
import { formatDateUsingPreferredUserFormat } from "../../helpers/date-formatter";
import { computed } from "vue";
import { relativeDatePlacement, relativeDatePreference } from "@tuleap/tlp-relative-date";
import type { FileHistory } from "../../type";

defineProps<{ versions: readonly FileHistory[] }>();

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

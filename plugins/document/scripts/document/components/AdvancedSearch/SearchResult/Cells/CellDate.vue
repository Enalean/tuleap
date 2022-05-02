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
    <td>
        <tlp-relative-date
            v-bind:date="date"
            v-bind:absolute-date="formatted_full_date"
            v-bind:placement="relative_date_placement"
            v-bind:preference="relative_date_preference"
            v-bind:locale="user_locale"
        >
            {{ formatted_full_date }}
        </tlp-relative-date>
    </td>
</template>

<script setup lang="ts">
import { useState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../store/configuration";
import { computed } from "@vue/composition-api";
import { formatDateUsingPreferredUserFormat } from "../../../../helpers/date-formatter";
import { relativeDatePlacement, relativeDatePreference } from "@tuleap/tlp-relative-date";

const props = defineProps<{ date: string }>();

const { date_time_format, relative_dates_display, user_locale } = useState<
    Pick<ConfigurationState, "date_time_format" | "relative_dates_display" | "user_locale">
>("configuration", ["date_time_format", "relative_dates_display", "user_locale"]);

const formatted_full_date = computed((): string => {
    return formatDateUsingPreferredUserFormat(props.date, date_time_format.value);
});

const relative_date_preference = computed((): string => {
    return relativeDatePreference(relative_dates_display.value);
});

const relative_date_placement = computed((): string => {
    return relativeDatePlacement(relative_dates_display.value, "top");
});
</script>

<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>

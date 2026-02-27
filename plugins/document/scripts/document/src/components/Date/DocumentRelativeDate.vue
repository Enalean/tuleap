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
    <tlp-relative-date
        v-bind:date="date"
        v-bind:absolute-date="formatted_full_date"
        v-bind:placement="relative_date_placement"
        v-bind:preference="relative_date_preference"
        v-bind:locale="bcp47_locale"
    >
        {{ formatted_full_date }}
    </tlp-relative-date>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { relativeDatePlacement, relativeDatePreference } from "@tuleap/tlp-relative-date";
import { strictInject } from "@tuleap/vue-strict-inject";
import { toBCP47 } from "@tuleap/locale";
import { DATE_TIME_FORMATTER, RELATIVE_DATES_DISPLAY, USER_LOCALE } from "../../configuration-keys";

const props = withDefaults(defineProps<{ date: string; relative_placement?: "top" | "right" }>(), {
    relative_placement: "top",
});

const date_time_formatter = strictInject(DATE_TIME_FORMATTER);
const user_locale = strictInject(USER_LOCALE);
const relative_dates_display = strictInject(RELATIVE_DATES_DISPLAY);

const formatted_full_date = computed((): string => {
    return date_time_formatter.format(props.date);
});

const relative_date_preference = computed((): string => {
    return relativeDatePreference(relative_dates_display);
});

const relative_date_placement = computed((): string => {
    return relativeDatePlacement(relative_dates_display, props.relative_placement);
});

const bcp47_locale = toBCP47(user_locale);
</script>

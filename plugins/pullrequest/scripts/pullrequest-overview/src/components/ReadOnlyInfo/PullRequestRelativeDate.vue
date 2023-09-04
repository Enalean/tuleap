<!--
  - Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
    <tlp-relative-date
        v-bind:date="props.date"
        v-bind:absolute-date="formatted_full_date"
        v-bind:placement="relative_date_placement"
        v-bind:preference="relative_date_preference"
        v-bind:locale="user_locale"
        class="pullrequest-relative-date"
        data-test="pullrequest-relative-date"
    >
        {{ formatted_full_date }}
    </tlp-relative-date>
</template>

<script setup lang="ts">
import moment from "moment";
import { computed } from "vue";
import { formatFromPhpToMoment } from "@tuleap/date-helper";
import { relativeDatePlacement, relativeDatePreference } from "@tuleap/tlp-relative-date";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import { strictInject } from "@tuleap/vue-strict-inject";
import {
    USER_DATE_TIME_FORMAT_KEY,
    USER_LOCALE_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
} from "../../constants";

const props = defineProps<{
    date: string;
}>();

const date_time_format: string = strictInject(USER_DATE_TIME_FORMAT_KEY);
const relative_date_display: RelativeDatesDisplayPreference = strictInject(
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
);
const user_locale: string = strictInject(USER_LOCALE_KEY);

const formatted_full_date = computed((): string => {
    return moment(props.date).format(formatFromPhpToMoment(date_time_format));
});

const relative_date_preference = computed((): string => {
    return relativeDatePreference(relative_date_display);
});

const relative_date_placement = computed((): string => {
    return relativeDatePlacement(relative_date_display, "right");
});
</script>

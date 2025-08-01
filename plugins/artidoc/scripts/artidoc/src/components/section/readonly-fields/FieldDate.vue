<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <label class="tlp-label document-label">{{ field.label }}</label>
    <tlp-relative-date
        v-if="field.value !== null"
        v-bind:date="field.value"
        v-bind:absolute-date="formatted_date"
        v-bind:placement="relative_date_placement"
        v-bind:preference="relative_date_preference"
        v-bind:locale="user_preferences.locale"
    >
        {{ formatted_date }}
    </tlp-relative-date>
    <p v-else class="tlp-property-empty" data-test="empty-state">{{ $gettext("Empty") }}</p>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { ReadonlyFieldDate } from "@/sections/readonly-fields/ReadonlyFields";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { UserPreferences } from "@/user-preferences-injection-key";
import { USER_PREFERENCES } from "@/user-preferences-injection-key";
import { IntlFormatter } from "@tuleap/date-helper";
import { relativeDatePlacement, relativeDatePreference } from "@tuleap/tlp-relative-date";

const { $gettext } = useGettext();

const user_preferences = strictInject<UserPreferences>(USER_PREFERENCES);

const props = defineProps<{
    field: ReadonlyFieldDate;
}>();

const formatter = IntlFormatter(
    user_preferences.locale,
    user_preferences.timezone,
    props.field.with_time ? "date-with-time" : "date",
);

const relative_date_preference = relativeDatePreference(user_preferences.relative_date_display);
const relative_date_placement = relativeDatePlacement(
    user_preferences.relative_date_display,
    "right",
);
const formatted_date = computed((): string => formatter.format(props.field.value));
</script>

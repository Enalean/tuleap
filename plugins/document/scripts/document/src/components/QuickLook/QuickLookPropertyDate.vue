<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div>
        <div
            class="tlp-tooltip tlp-tooltip-left"
            v-bind:data-tlp-tooltip="getFormattedDate"
            v-if="is_obsolescence_date_today"
            data-test="property-date-today"
        >
            {{ $gettext("Today") }}
        </div>
        <tlp-relative-date
            v-else-if="is_date_valid"
            data-test="property-date-formatted-display"
            v-bind:date="property.value"
            v-bind:absolute-date="getFormattedDate"
            v-bind:placement="relative_date_placement"
            v-bind:preference="relative_date_preference"
            v-bind:locale="user_locale"
        >
            {{ getFormattedDate }}
        </tlp-relative-date>
        <span
            class="document-quick-look-property-empty"
            data-test="property-date-permanent"
            v-else-if="has_obsolescence_date_property_unlimited_validity"
        >
            {{ $gettext("Permanent") }}
        </span>
        <span class="document-quick-look-property-empty" data-test="property-date-empty" v-else>
            {{ $gettext("Empty") }}
        </span>
    </div>
</template>

<script setup lang="ts">
import {
    formatDateUsingPreferredUserFormat,
    isDateValid,
    isToday,
} from "../../helpers/date-formatter";
import { PROPERTY_OBSOLESCENCE_DATE_SHORT_NAME } from "../../constants";
import { relativeDatePlacement, relativeDatePreference } from "@tuleap/tlp-relative-date";
import { useNamespacedState } from "vuex-composition-helpers";
import { computed } from "vue";
import type { ConfigurationState } from "../../store/configuration";
import type { Property } from "../../type";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const props = defineProps<{ property: Property }>();

const { date_time_format, relative_dates_display, user_locale } =
    useNamespacedState<ConfigurationState>("configuration", [
        "date_time_format",
        "relative_dates_display",
        "user_locale",
    ]);

const is_date_valid = computed((): boolean => {
    if (!isValueString(props.property.value)) {
        return false;
    }

    return isDateValid(props.property.value);
});

const has_obsolescence_date_property_unlimited_validity = computed((): boolean => {
    return isPropertyObsolescenceDate() && props.property.value === null;
});

const is_obsolescence_date_today = computed((): boolean => {
    if (!isValueString(props.property.value)) {
        return false;
    }

    return isPropertyObsolescenceDate() && is_date_valid.value && isToday(props.property.value);
});

const getFormattedDate = computed((): string => {
    if (!isValueString(props.property.value)) {
        return "";
    }
    return formatDateUsingPreferredUserFormat(props.property.value, date_time_format.value);
});

const relative_date_preference = computed((): string => {
    return relativeDatePreference(relative_dates_display.value);
});

const relative_date_placement = computed((): string => {
    return relativeDatePlacement(relative_dates_display.value, "right");
});

function isPropertyObsolescenceDate(): boolean {
    return props.property.short_name === PROPERTY_OBSOLESCENCE_DATE_SHORT_NAME;
}

function isValueString(value: number | string | null): value is string {
    return typeof value === "string";
}
</script>

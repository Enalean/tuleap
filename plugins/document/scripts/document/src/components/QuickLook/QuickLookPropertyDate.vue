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
    <div
        class="tlp-tooltip tlp-tooltip-left"
        v-if="is_obsolescence_date_today"
        data-test="property-date-today"
    >
        {{ $gettext("Today") }}
    </div>
    <date-without-time
        v-else-if="is_date_valid && isPropertyObsolescenceDate()"
        v-bind:date="getDate"
        data-test="property-date-without-time-display"
    />
    <document-relative-date
        v-else-if="is_date_valid"
        v-bind:date="getDate"
        data-test="property-date-formatted-display"
    />
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
</template>

<script setup lang="ts">
import { isDateValid, isToday } from "../../helpers/date-formatter";
import { PROPERTY_OBSOLESCENCE_DATE_SHORT_NAME } from "../../constants";
import { computed } from "vue";
import type { Property } from "../../type";
import { useGettext } from "vue3-gettext";
import DateWithoutTime from "../Date/DateWithoutTime.vue";
import DocumentRelativeDate from "../Date/DocumentRelativeDate.vue";

const { $gettext } = useGettext();

const props = defineProps<{ property: Property; isObsolescenceDate: boolean }>();

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

const getDate = computed((): string => {
    if (!isValueString(props.property.value)) {
        return "";
    }

    return props.property.value;
});

function isPropertyObsolescenceDate(): boolean {
    return props.property.short_name === PROPERTY_OBSOLESCENCE_DATE_SHORT_NAME;
}

function isValueString(value: number | string | null): value is string {
    return typeof value === "string";
}
</script>

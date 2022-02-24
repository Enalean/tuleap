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
            v-translate
        >
            Today
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
            v-translate
        >
            Permanent
        </span>
        <span
            class="document-quick-look-property-empty"
            data-test="property-date-empty"
            v-else
            v-translate
        >
            Empty
        </span>
    </div>
</template>
<script>
import { mapState } from "vuex";
import {
    formatDateUsingPreferredUserFormat,
    isDateValid,
    isToday,
} from "../../../helpers/date-formatter";
import { PROPERTY_OBSOLESCENCE_DATE_SHORT_NAME } from "../../../constants";
import {
    relativeDatePlacement,
    relativeDatePreference,
} from "@tuleap/core/scripts/tuleap/custom-elements/relative-date/relative-date-helper";

export default {
    name: "QuickLookPropertyDate",
    props: {
        property: Object,
    },
    computed: {
        ...mapState("configuration", ["date_time_format", "relative_dates_display", "user_locale"]),
        is_date_valid() {
            return isDateValid(this.property.value);
        },
        has_obsolescence_date_property_unlimited_validity() {
            return this.isPropertyObsolescenceDate() && this.property.value === null;
        },
        is_obsolescence_date_today() {
            return (
                this.isPropertyObsolescenceDate() &&
                this.is_date_valid &&
                isToday(this.property.value)
            );
        },
        getFormattedDate() {
            return formatDateUsingPreferredUserFormat(this.property.value, this.date_time_format);
        },
        relative_date_preference() {
            return relativeDatePreference(this.relative_dates_display);
        },
        relative_date_placement() {
            return relativeDatePlacement(this.relative_dates_display, "right");
        },
    },
    methods: {
        isPropertyObsolescenceDate() {
            return this.property.short_name === PROPERTY_OBSOLESCENCE_DATE_SHORT_NAME;
        },
    },
};
</script>

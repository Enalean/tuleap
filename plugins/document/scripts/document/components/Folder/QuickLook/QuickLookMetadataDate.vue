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
            data-test="metadata-date-today"
            v-translate
        >
            Today
        </div>
        <div
            class="tlp-tooltip tlp-tooltip-left"
            v-bind:data-tlp-tooltip="getFormattedDate"
            v-else-if="is_date_valid"
            data-test="metadata-date-formatted-display"
        >
            {{ getFormattedDateForDisplay(metadata.value) }}
        </div>
        <span
            class="document-quick-look-property-empty"
            data-test="metadata-date-permanent"
            v-else-if="has_obsolescence_date_metadata_unlimited_validity"
            v-translate
        >
            Permanent
        </span>
        <span
            class="document-quick-look-property-empty"
            data-test="metadata-date-empty"
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
    getElapsedTimeFromNow,
    isDateValid,
    isToday,
} from "../../../helpers/date-formatter.js";
import { METADATA_OBSOLESCENCE_DATE_SHORT_NAME } from "../../../constants.js";
export default {
    name: "QuickLookMetadataDate",
    props: {
        metadata: Object,
    },
    computed: {
        ...mapState(["date_time_format"]),
        is_date_valid() {
            return isDateValid(this.metadata.value);
        },
        has_obsolescence_date_metadata_unlimited_validity() {
            return this.isMetadataObsolescenceDate() && this.metadata.value === null;
        },
        is_obsolescence_date_today() {
            return (
                this.isMetadataObsolescenceDate() &&
                this.is_date_valid &&
                isToday(this.metadata.value)
            );
        },
        getFormattedDate() {
            return formatDateUsingPreferredUserFormat(this.metadata.value, this.date_time_format);
        },
    },
    methods: {
        getFormattedDateForDisplay(date) {
            return getElapsedTimeFromNow(date);
        },
        isMetadataObsolescenceDate() {
            return this.metadata.short_name === METADATA_OBSOLESCENCE_DATE_SHORT_NAME;
        },
    },
};
</script>

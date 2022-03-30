<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <span class="tlp-tooltip tlp-tooltip-right" v-bind:data-tlp-tooltip="formatted_date">
        {{ humanized_date }}
    </span>
</template>

<script>
import DateUtils from "../../support/date-utils";

export default {
    name: "HumanizedDate",

    props: {
        date: { required: true, type: String },
        start_with_capital: { type: Boolean, default: false },
    },

    computed: {
        formatted_date() {
            return DateUtils.format(this.date);
        },
        interval_from_now() {
            return DateUtils.getFromNow(this.date);
        },
        humanized_date() {
            if (this.start_with_capital) {
                return this.capitalizeFirstLetter(this.interval_from_now);
            }
            return this.interval_from_now;
        },
    },
    methods: {
        capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        },
    },
};
</script>

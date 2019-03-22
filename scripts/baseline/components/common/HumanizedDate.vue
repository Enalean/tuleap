<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <span>{{ is_humanized }}</span>
</template>

<script>
import { sprintf } from "sprintf-js";

export default {
    name: "HumanizedDate",

    props: {
        date: { required: true, type: String }
    },

    computed: {
        is_humanized() {
            const date = new Date(this.date);

            const date_properties = {
                month: this.getMonthName(date.getMonth()),
                day: date.getDate(),
                year: date.getFullYear(),
                hours: this.padHour(date.getHours()),
                minutes: this.padHour(date.getMinutes()),
                seconds: this.padHour(date.getSeconds())
            };

            return sprintf(
                this.$gettext(
                    "%(month)s %(day)s, %(year)s at %(hours)sh %(minutes)smn %(seconds)ss"
                ),
                date_properties
            );
        }
    },

    methods: {
        padHour(hour) {
            return hour > 9 ? hour : `0${hour}`;
        },

        getMonthName(month) {
            switch (month) {
                case 0:
                    return this.$gettext("January");
                case 1:
                    return this.$gettext("February");
                case 2:
                    return this.$gettext("March");
                case 3:
                    return this.$gettext("April");
                case 4:
                    return this.$gettext("May");
                case 5:
                    return this.$gettext("Jun");
                case 6:
                    return this.$gettext("July");
                case 7:
                    return this.$gettext("August");
                case 8:
                    return this.$gettext("September");
                case 9:
                    return this.$gettext("October");
                case 10:
                    return this.$gettext("November");
                case 11:
                    return this.$gettext("December");
                default:
                    return "";
            }
        }
    }
};
</script>

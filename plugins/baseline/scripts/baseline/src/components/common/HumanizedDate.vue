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

<script setup lang="ts">
import DateUtils from "../../support/date-utils";
import { computed } from "vue";

const props = withDefaults(
    defineProps<{
        date: string;
        start_with_capital?: boolean;
    }>(),
    {
        start_with_capital: false,
    },
);

const formatted_date = computed(() => {
    return DateUtils.format(props.date);
});

const interval_from_now = computed(() => {
    return DateUtils.getFromNow(props.date);
});

const humanized_date = computed((): string => {
    if (props.start_with_capital) {
        return capitalizeFirstLetter(interval_from_now.value);
    }
    return interval_from_now.value;
});

function capitalizeFirstLetter(string: string): string {
    return string.charAt(0).toUpperCase() + string.slice(1);
}
</script>

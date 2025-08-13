<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <div class="roadmap-gantt-today" v-bind:style="style" v-bind:title="title"></div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useState, useNamespacedGetters } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import { getLeftForDate } from "../../helpers/left-position";

const { interpolate, $gettext } = useGettext();

const { time_period } = useNamespacedGetters("timeperiod", ["time_period"]);
const { now, locale_bcp47 } = useState(["now", "locale_bcp47"]);

const style = computed(() => {
    const left = getLeftForDate(now.value, time_period.value);
    return `left: ${left}px;`;
});

const title = computed(() => {
    return interpolate($gettext("Today: %{ date }"), {
        date: now.value.setLocale(locale_bcp47.value).toLocaleString({
            day: "numeric",
            month: "long",
            year: "numeric",
        }),
    });
});
</script>

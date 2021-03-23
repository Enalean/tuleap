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
    <div class="roadmap-gantt-timeperiod-months">
        <div
            class="roadmap-gantt-timeperiod-month tlp-tooltip tlp-tooltip-bottom"
            v-for="month in months"
            v-bind:key="month.toISOString()"
            v-bind:data-tlp-tooltip="month_with_year_format.format(month)"
        >
            {{ month_format.format(month) }}
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";

@Component
export default class TimePeriodMonth extends Vue {
    @Prop({ required: true })
    private readonly months!: Date[];

    @Prop({ required: true })
    private readonly locale!: string;

    get standard_locale(): string {
        return this.locale.replace("_", "-");
    }

    get month_format(): Intl.DateTimeFormat {
        return new Intl.DateTimeFormat(this.standard_locale, {
            month: "short",
        });
    }

    get month_with_year_format(): Intl.DateTimeFormat {
        return new Intl.DateTimeFormat(this.standard_locale, {
            month: "long",
            year: "numeric",
        });
    }
}
</script>

<!---
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
    <div class="tlp-pane-container planned-iteration-display">
        <div
            class="tlp-pane-header planned-iteration-header"
            data-test="iteration-card-header"
            v-bind:data-test-iteration-id="iteration.id"
        >
            <span
                class="tlp-pane-title planned-iteration-header-label"
                data-test="iteration-header-label"
            >
                <i class="tlp-pane-title-icon fas fa-fw fa-caret-right" aria-hidden="true" />
                {{ iteration.title }}
            </span>
            <div>
                <span
                    v-if="iteration.start_date !== null"
                    class="planned-iteration-header-dates"
                    data-test="iteration-header-dates"
                >
                    {{ formatDate(iteration.start_date) }}
                    <i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i>
                    {{ formatDate(iteration.end_date) }}
                </span>
                <span
                    v-if="iteration.status.length > 0"
                    class="tlp-badge-outline tlp-badge-primary"
                    data-test="iteration-header-status"
                >
                    {{ iteration.status }}
                </span>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { State } from "vuex-class";
import { Component, Prop } from "vue-property-decorator";
import { formatDateYearMonthDay } from "@tuleap/date-helper";

import type { Iteration } from "../type";

@Component
export default class IterationCard extends Vue {
    @Prop({ required: true })
    readonly iteration!: Iteration;

    @State
    readonly user_locale!: string;

    formatDate(date: string): string {
        return formatDateYearMonthDay(this.user_locale, date);
    }
}
</script>

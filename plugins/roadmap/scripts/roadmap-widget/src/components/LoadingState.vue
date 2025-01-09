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
    <div>
        <div class="roadmap-gantt-controls">
            <div class="tlp-form-element roadmap-gantt-control">
                <span class="tlp-skeleton-text"></span>
            </div>
            <div class="tlp-form-element roadmap-gantt-control">
                <span class="tlp-skeleton-text"></span>
            </div>
            <div class="tlp-form-element roadmap-gantt-control">
                <span class="tlp-skeleton-text"></span>
            </div>
        </div>
        <div class="roadmap-gantt">
            <div class="roadmap-gantt-header">
                <span class="roadmap-gantt-task-header" v-for="index in 5" v-bind:key="index">
                    <div class="roadmap-gantt-task-header-link">
                        <span class="roadmap-gantt-task-header-xref">
                            <span class="tlp-skeleton-text"></span>
                        </span>
                        <span class="roadmap-gantt-task-header-title">
                            <span class="tlp-skeleton-text"></span>
                        </span>
                    </div>
                </span>
            </div>
            <div class="roadmap-gantt-scrolling-area">
                <div>
                    <div class="roadmap-gantt-timeperiod">
                        <div
                            class="roadmap-gantt-timeperiod-year"
                            v-bind:class="'roadmap-gantt-timeperiod-year-span-' + time_units.length"
                        >
                            <span class="tlp-skeleton-text"></span>
                        </div>
                    </div>
                    <div class="roadmap-gantt-timeperiod" ref="time_period">
                        <div
                            class="roadmap-gantt-timeperiod-unit"
                            v-for="unit in time_units"
                            v-bind:key="String(unit.toISO())"
                        >
                            <span class="tlp-skeleton-text"></span>
                        </div>
                    </div>
                </div>
                <div class="roadmap-gantt-task" v-for="index in 5" v-bind:key="index">
                    <div class="roadmap-gantt-task-background-grid">
                        <div
                            class="roadmap-gantt-task-background-grid-unit"
                            v-for="unit in time_units"
                            v-bind:key="'grid-month-' + unit.toISO()"
                        />
                    </div>
                    <div class="roadmap-gantt-task-bar-container" v-bind:style="randomStyleLeft()">
                        <div
                            class="roadmap-gantt-task-bar tlp-skeleton-text"
                            v-bind:style="randomStyleWidth()"
                        >
                            <div class="roadmap-gantt-task-bar-progress"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { Styles } from "../helpers/styles";
import { TimePeriodMonth } from "../helpers/time-period-month";
import { DateTime } from "luxon";

const time_period = ref<Element | undefined>();

let time_units: DateTime[] = [];

let observer: ResizeObserver | null = null;

onMounted(() => {
    observer = new ResizeObserver(adjustTimePeriod);
    if (time_period.value) {
        observer.observe(time_period.value);
    }
});

function adjustTimePeriod(entries: ResizeObserverEntry[]): void {
    for (const entry of entries) {
        if (entry.target !== time_period.value) {
            continue;
        }

        const nb = Math.ceil(entry.contentRect.width / Styles.TIME_UNIT_WIDTH_IN_PX) - 1;

        const dummy_time_period = TimePeriodMonth.getDummyTimePeriod(DateTime.now());
        time_units = dummy_time_period.additionalUnits(nb);
    }
}

function randomStyleLeft(): string {
    const left = getRandomInt(40, (time_units.length - 3) * Styles.TIME_UNIT_WIDTH_IN_PX);

    return `left: ${left}px;`;
}

function randomStyleWidth(): string {
    const width = getRandomInt(30, (time_units.length * Styles.TIME_UNIT_WIDTH_IN_PX) / 3);

    return `width: ${width}px;`;
}

function getRandomInt(min: number, max: number): number {
    return Math.floor(Math.random() * (max - min) + min);
}

onBeforeUnmount(() => {
    if (observer) {
        observer.disconnect();
    }
});
</script>

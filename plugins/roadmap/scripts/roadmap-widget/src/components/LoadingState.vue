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
                            v-bind:key="unit.toISOString()"
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
                            v-bind:key="'grid-month-' + unit.toISOString()"
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

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { Styles } from "../helpers/styles";
import { TimePeriodMonth } from "../helpers/time-period-month";

@Component
export default class LoadingState extends Vue {
    override $refs!: {
        time_period: HTMLDivElement;
    };

    time_units: Date[] = [];

    private observer: ResizeObserver | null = null;

    mounted(): void {
        this.observer = new ResizeObserver(this.adjustTimePeriod);
        this.observer.observe(this.$refs.time_period);
    }

    beforeDestroy(): void {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    adjustTimePeriod(entries: ResizeObserverEntry[]): void {
        for (const entry of entries) {
            if (entry.target !== this.$refs.time_period) {
                continue;
            }

            const nb = Math.ceil(entry.contentRect.width / Styles.TIME_UNIT_WIDTH_IN_PX) - 1;

            const time_period = TimePeriodMonth.getDummyTimePeriod(new Date());
            this.time_units = time_period.additionalUnits(nb);
        }
    }

    randomStyleLeft(): string {
        const left = this.getRandomInt(
            40,
            (this.time_units.length - 3) * Styles.TIME_UNIT_WIDTH_IN_PX,
        );

        return `left: ${left}px;`;
    }

    randomStyleWidth(): string {
        const width = this.getRandomInt(
            30,
            (this.time_units.length * Styles.TIME_UNIT_WIDTH_IN_PX) / 3,
        );

        return `width: ${width}px;`;
    }

    getRandomInt(min: number, max: number): number {
        return Math.floor(Math.random() * (max - min) + min);
    }
}
</script>

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
    <div class="roadmap-gantt">
        <div class="roadmap-gantt-timeperiod-months" ref="time_period">
            <div
                class="roadmap-gantt-timeperiod-month"
                v-for="unit in time_units"
                v-bind:key="unit.toISOString()"
            >
                <span class="tlp-skeleton-text"></span>
            </div>
        </div>
        <div>
            <div class="roadmap-gantt-task" v-for="index in 5" v-bind:key="index">
                <span class="roadmap-gantt-task-header">
                    <span class="roadmap-gantt-task-header-xref">
                        <span class="tlp-skeleton-text"></span>
                    </span>
                    <span class="roadmap-gantt-task-header-title">
                        <span class="tlp-skeleton-text"></span>
                    </span>
                </span>
                <div class="roadmap-gantt-task-background-grid">
                    <div
                        class="roadmap-gantt-task-background-grid-unit"
                        v-for="unit in time_units"
                        v-bind:key="'grid-month-' + unit.toISOString()"
                    />
                </div>
                <div class="roadmap-gantt-task-bar tlp-skeleton-text" v-bind:style="randomStyle()">
                    <div class="roadmap-gantt-task-bar-progress"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { Styles } from "../helpers/styles";
import { getAdditionalMonths } from "../helpers/additional-months";

@Component
export default class LoadingState extends Vue {
    $refs!: {
        time_period: HTMLDivElement;
    };

    private time_units: Date[] = [];

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

            const nb_missing_units = Math.ceil(
                entry.contentRect.width / Styles.TIME_UNIT_WIDTH_IN_PX
            );

            this.time_units = getAdditionalMonths(new Date(), nb_missing_units - 1);
        }
    }

    randomStyle(): string {
        const left = this.getRandomInt(
            40,
            (this.time_units.length - 3) * Styles.TIME_UNIT_WIDTH_IN_PX
        );
        const width = this.getRandomInt(
            30,
            (this.time_units.length * Styles.TIME_UNIT_WIDTH_IN_PX) / 3
        );

        return `left: ${left}px; width: ${width}px`;
    }

    getRandomInt(min: number, max: number): number {
        return Math.floor(Math.random() * (max - min) + min);
    }
}
</script>

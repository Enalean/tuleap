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
    <div class="roadmap-gantt-scrolling-area">
        <div class="roadmap-gantt-scrolling-area-empty-pixel" ref="empty_pixel" />
        <today-indicator v-bind:time_period="time_period" v-bind:now="now" ref="today" />
        <slot></slot>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import TodayIndicator from "./TodayIndicator.vue";
import type { TimePeriod } from "../../type";

@Component({
    components: { TodayIndicator },
})
export default class ScrollingArea extends Vue {
    $refs!: {
        today: TodayIndicator;
        empty_pixel: HTMLElement;
    };

    @Prop({ required: true })
    readonly time_period!: TimePeriod;

    @Prop({ required: true })
    readonly now!: Date;

    private observer: IntersectionObserver | null = null;

    mounted(): void {
        if (this.$el.scrollTo && this.$refs.today.$el instanceof HTMLElement) {
            this.$el.scrollTo({
                top: 0,
                left: this.$refs.today.$el.offsetLeft,
                behavior: "smooth",
            });
        }

        this.observer = new IntersectionObserver(this.detectScrolling, {
            root: this.$el,
        });
        this.observer.observe(this.$refs.empty_pixel);
    }

    detectScrolling(entries: IntersectionObserverEntry[]): void {
        const entry = entries.find((entry) => entry.target === this.$refs.empty_pixel);
        if (!entry) {
            return;
        }

        const is_scrolling = entry.isIntersecting === false;

        this.$emit("is_scrolling", is_scrolling);
    }
}
</script>

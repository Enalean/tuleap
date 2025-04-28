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
    <div class="roadmap-gantt-scrolling-area" ref="scroll">
        <div class="roadmap-gantt-scrolling-area-empty-pixel" ref="empty_pixel" />
        <today-indicator ref="today" />
        <slot></slot>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from "vue";
import TodayIndicator from "./TodayIndicator.vue";
import type { TimeScale } from "../../type";

const props = defineProps<{
    timescale: TimeScale;
}>();

const emit = defineEmits<{
    (e: "is_scrolling", is_scrolling: boolean): void;
}>();

const today = ref<InstanceType<typeof TodayIndicator> | null>(null);
const empty_pixel = ref<HTMLElement | null>(null);
const scroll = ref<HTMLElement | null>(null);

const observer = ref<IntersectionObserver | null>(null);

onMounted(() => {
    autoscrollToToday();

    observer.value = new IntersectionObserver(detectScrolling, {
        root: scroll.value,
    });

    if (!empty_pixel.value) {
        return;
    }
    observer.value.observe(empty_pixel.value);
});

watch(
    () => props.timescale,
    () => {
        autoscrollToToday();
    },
);

async function autoscrollToToday(): Promise<void> {
    await nextTick();
    const scroll_area = scroll.value;
    if (scroll_area && today.value && today.value.$el instanceof HTMLElement) {
        scroll_area.scrollTo({
            top: 0,
            left: Math.max(0, today.value.$el.offsetLeft - scroll_area.clientWidth / 2),
            behavior: "smooth",
        });
    }
}

function detectScrolling(entries: IntersectionObserverEntry[]): void {
    const entry = entries.find((entry) => entry.target === empty_pixel.value);
    if (!entry) {
        return;
    }

    const is_scrolling = !entry.isIntersecting;
    emit("is_scrolling", is_scrolling);
}

defineExpose({
    empty_pixel,
});
</script>

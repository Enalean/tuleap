<!--
  - Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
    <section class="tlp-pane" ref="sidebar-container">
        <slot></slot>
    </section>
</template>

<script setup lang="ts">
import { useTemplateRef, onMounted, onBeforeUnmount } from "vue";

const sidebar = useTemplateRef("sidebar-container");
const y_to_viewport_offset_in_px = 16;
const sticky_top = `${y_to_viewport_offset_in_px}px`;

function isSidebarStickingToViewportTopEdge(top: number): boolean {
    return top === y_to_viewport_offset_in_px;
}

function setSidebarMaxHeight(): void {
    if (!sidebar.value) {
        return;
    }

    const { top } = sidebar.value.getBoundingClientRect();
    const max_height = isSidebarStickingToViewportTopEdge(top)
        ? window.innerHeight - y_to_viewport_offset_in_px * 2
        : window.innerHeight - top - y_to_viewport_offset_in_px;

    sidebar.value.style.maxHeight = `${max_height}px`;
}

onMounted(() => {
    document.addEventListener("scroll", setSidebarMaxHeight, { passive: true });
    setSidebarMaxHeight();
});

onBeforeUnmount(() => {
    document.removeEventListener("scroll", setSidebarMaxHeight);
});
</script>

<style scoped lang="scss">
.tlp-pane {
    position: sticky;
    top: v-bind("sticky_top");
    align-self: start;
    margin: 0;
}
</style>

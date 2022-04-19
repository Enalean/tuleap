<!--
  - Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
    <div class="fake-worksheet-section">
        <div class="fake-worksheet-shadow">
            <div class="fake-worksheet">
                <fake-worksheet-column v-bind:tracker_name="tracker_name_level_1" />
                <fake-worksheet-column v-bind:tracker_name="tracker_name_level_2" />
                <fake-worksheet-column v-bind:tracker_name="tracker_name_level_3" />
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import FakeWorksheetColumn from "./FakeWorksheetColumn.vue";

defineProps<{
    tracker_name_level_1: string;
    tracker_name_level_2: string | null;
    tracker_name_level_3: string | null;
}>();
</script>
<style lang="scss" scoped>
.fake-worksheet-section {
    border-top: 1px solid var(--tlp-neutral-light-color);
    background: var(--tlp-background-color);
}

.fake-worksheet-shadow {
    filter: drop-shadow(0 0 1px var(--tlp-neutral-light-color));
}

.fake-worksheet {
    display: grid;
    grid-auto-columns: 1fr;
    grid-auto-flow: column;
    grid-gap: 1px;
    height: 130px;
    background-color: var(--tlp-success-dark-color);
    clip-path: inset(var(--tlp-medium-spacing) round 4px);
    shape-rendering: crispedges;
}

.fake-worksheet :first-child,
.fake-worksheet :last-child {
    width: calc(100% - var(--tlp-medium-spacing));
}

.fake-worksheet :first-child {
    margin-left: var(--tlp-medium-spacing);
}

.fake-worksheet :last-child {
    margin-right: var(--tlp-medium-spacing);
}
</style>

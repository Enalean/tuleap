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
    <svg xmlns="http://www.w3.org/2000/svg" class="worksheet-column">
        <g v-bind:class="{ 'worksheet-column-disabled': tracker_name === null }">
            <text x="3%" y="15%" class="worksheet-header-title">{{ tracker_name }}</text>

            <rect x="0" y="0" width="100%" height="20%" class="worksheet-first-row" />
            <rect x="0" y="20%" width="100%" height="20%" class="worksheet-second-row" />
            <rect x="0" y="40%" width="100%" height="60%" class="worksheet-remaining-rows" />

            <line x1="15%" y1="20%" x2="15%" y2="100%" class="worksheet-cell-separator" />
            <line x1="53%" y1="20%" x2="53%" y2="100%" class="worksheet-cell-separator" />
            <line x1="78%" y1="20%" x2="78%" y2="100%" class="worksheet-cell-separator" />

            <line x1="0%" y1="40%" x2="100%" y2="40%" class="worksheet-cell-separator" />
            <line x1="0%" y1="60%" x2="100%" y2="60%" class="worksheet-cell-separator" />
            <line x1="0%" y1="80%" x2="100%" y2="80%" class="worksheet-cell-separator" />
        </g>
    </svg>
</template>
<script setup lang="ts">
defineProps<{ tracker_name: string | null }>();
</script>
<style lang="scss" scoped>
.worksheet-column {
    width: 100%;
    height: calc(100% - var(--tlp-medium-spacing) - var(--tlp-medium-spacing));
    padding-top: var(--tlp-medium-spacing);
    padding-bottom: var(--tlp-medium-spacing);
    background-color: var(--tlp-white-color);
}

.worksheet-column-disabled {
    opacity: 0.6;
}

.worksheet-first-row {
    opacity: 0.4;
    fill: var(--tlp-illustration-green);
}

.worksheet-second-row {
    opacity: 0.2;
    fill: var(--tlp-illustration-green);
}

.worksheet-remaining-rows {
    opacity: 0.2;
    fill: var(--tlp-white-color);
}

.worksheet-cell-separator {
    stroke-width: 1px;
    stroke: var(--tlp-illustration-green);
    opacity: 0.2;
}

.worksheet-header-title {
    fill: var(--tlp-success-dark-color);
    font-family: var(--tlp-font-family);
    font-weight: bold;
    letter-spacing: 0.01em;
    text-transform: uppercase;
}
</style>

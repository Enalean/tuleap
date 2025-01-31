<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <table class="tlp-table" data-test="loading-row">
        <thead>
            <tr>
                <slot />
            </tr>
        </thead>
        <tbody>
            <tr v-for="(row, row_index) in rows" v-bind:key="row_index">
                <td
                    v-for="(cell, cell_index) in row"
                    v-bind:key="cell_index"
                    v-bind:class="getCellClassname(cell_index)"
                >
                    <i
                        class="fa-fw tlp-skeleton-icon"
                        v-bind:class="cell.icon"
                        v-if="cell.icon"
                    ></i>
                    <span class="tlp-skeleton-text" v-bind:style="{ width: cell.width }"></span>
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
    nb_rows: number;
    nb_cols: number;
    icons: Array<string>;
    cell_classes: Array<string>;
}>();

interface Row {
    width: string;
    icon: string;
}

const rows = computed((): Array<Array<Row>> => {
    const rows: Array<Array<Row>> = [];
    for (let i = 0; i < props.nb_rows; i++) {
        const cols: Array<Row> = [];
        for (let j = 0; j < props.nb_cols; j++) {
            const width = j === 0 ? getRandomPixelWidth() : getRandomPercentageWidth();
            let icon = "";
            if (j === 0 && props.icons && typeof props.icons[i] === "string") {
                icon = props.icons[i];
            }
            const row: Row = { width, icon };
            cols.push(row);
        }
        rows.push(cols);
    }

    return rows;
});

function getRandomInt(max: number): number {
    return Math.floor(Math.random() * Math.floor(max));
}

function getRandomPercentageWidth(): string {
    return 100 - getRandomInt(5) * 10 + "%";
}

function getRandomPixelWidth(): string {
    return 200 - getRandomInt(5) * 10 + "px";
}

function getCellClassname(cell_index: number): string {
    if (!props.cell_classes) {
        return "";
    }

    if (cell_index >= props.cell_classes.length) {
        return "";
    }

    return props.cell_classes[cell_index];
}
</script>

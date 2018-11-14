<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <table class="tlp-table">
        <thead>
            <tr>
                <slot/>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(row, row_index) in rows" v-bind:key="row_index">
                <td v-for="(cell, cell_index) in row" v-bind:key="cell_index">
                    <i class="fa tlp-skeleton-text-icon tlp-skeleton-icon" v-bind:class="cell.icon" v-if="cell.icon"></i>
                    <span class="tlp-skeleton-text" v-bind:style="{width: cell.width}"></span>
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script>
export default {
    name: "SkeletonTable",
    props: {
        nbRows: Number,
        nbCols: Number,
        icons: Array
    },
    computed: {
        rows() {
            const rows = [];
            for (let i = 0; i < this.nbRows; i++) {
                const cols = [];
                for (let j = 0; j < this.nbCols; j++) {
                    const width =
                        j === 0 ? this.getRandomPixelWidth() : this.getRandomPercentageWidth();
                    let icon = false;
                    if (j === 0 && this.icons && typeof this.icons[i] === "string") {
                        icon = this.icons[i];
                    }
                    cols.push({ width, icon });
                }
                rows.push(cols);
            }

            return rows;
        }
    },
    methods: {
        getRandomInt(max) {
            return Math.floor(Math.random() * Math.floor(max));
        },
        getRandomPercentageWidth() {
            return 100 - this.getRandomInt(5) * 10 + "%";
        },
        getRandomPixelWidth() {
            return 200 - this.getRandomInt(5) * 10 + "px";
        }
    }
};
</script>

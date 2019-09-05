<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <tr class="taskboard-swimlane">
        <td class="taskboard-cell" v-bind:colspan="colspan">
            <div class="empty-page">
                <div class="empty-page-illustration">
                    <no-content-svg/>
                </div>
                <translate tag="p" class="empty-page-text">
                    This taskboard is empty
                </translate>
            </div>
        </td>
    </tr>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import NoContentSvg from "./NoContentSvg.vue";
import { ColumnDefinition } from "../../type";

@Component({
    components: { NoContentSvg }
})
export default class NoContentEmptyState extends Vue {
    @Prop()
    readonly columns!: Array<ColumnDefinition>;

    get colspan(): number {
        return this.columns.length + 1;
    }
}
</script>

<!--
  - Copyright (c) Enalean 2022 - Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <tr>
        <template v-for="column of columns">
            <cell-custom-property
                v-if="isAdditionalFieldNumber(column.name)"
                v-bind:item="item"
                v-bind:column_name="column.name"
                v-bind:key="'cell-' + item.id + '-' + column.name"
            />

            <cell-id
                v-if="column.name === 'id'"
                v-bind:item="item"
                v-bind:key="'cell-' + item.id + '-' + column.name"
            />

            <cell-title
                v-if="column.name === 'title'"
                v-bind:item="item"
                v-bind:key="'cell-' + item.id + '-' + column.name"
            />

            <cell-description
                v-if="column.name === 'description'"
                v-bind:item="item"
                v-bind:key="'cell-' + item.id + '-' + column.name"
            />

            <cell-owner
                v-if="column.name === 'owner'"
                v-bind:item="item"
                v-bind:key="'cell-' + item.id + '-' + column.name"
            />

            <cell-update-date
                v-if="column.name === 'update_date'"
                v-bind:item="item"
                v-bind:key="'cell-' + item.id + '-' + column.name"
            />

            <cell-create-date
                v-if="column.name === 'create_date'"
                v-bind:item="item"
                v-bind:key="'cell-' + item.id + '-' + column.name"
            />

            <cell-obsolescence-date
                v-if="column.name === 'obsolescence_date'"
                v-bind:item="item"
                v-bind:key="'cell-' + item.id + '-' + column.name"
            />

            <cell-location
                v-if="column.name === 'location'"
                v-bind:item="item"
                v-bind:key="'cell-' + item.id + '-' + column.name"
            />

            <cell-filename
                v-if="column.name === 'filename'"
                v-bind:item="item"
                v-bind:key="'cell-' + item.id + '-' + column.name"
            />

            <cell-status
                v-if="column.name === 'status'"
                v-bind:item="item"
                v-bind:key="'cell-' + item.id + '-' + column.name"
            />
        </template>
    </tr>
</template>

<script setup lang="ts">
import type { ItemSearchResult, ListOfSearchResultColumnDefinition } from "../../../type";
import { isAdditionalFieldNumber } from "../../../helpers/additional-custom-properties";
import CellId from "./Cells/CellId.vue";
import CellTitle from "./Cells/CellTitle.vue";
import CellDescription from "./Cells/CellDescription.vue";
import CellOwner from "./Cells/CellOwner.vue";
import CellUpdateDate from "./Cells/CellUpdateDate.vue";
import CellCreateDate from "./Cells/CellCreateDate.vue";
import CellObsolescenceDate from "./Cells/CellObsolescenceDate.vue";
import CellLocation from "./Cells/CellLocation.vue";
import CellFilename from "./Cells/CellFilename.vue";
import CellStatus from "./Cells/CellStatus.vue";
import CellCustomProperty from "./Cells/CellCustomProperty.vue";

defineProps<{ item: ItemSearchResult; columns: ListOfSearchResultColumnDefinition }>();
</script>

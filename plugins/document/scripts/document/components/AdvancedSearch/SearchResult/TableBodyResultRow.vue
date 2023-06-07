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
        <component
            v-for="column of columns"
            v-bind:key="'cell-' + item.id + '-' + column.name"
            v-bind:is="getComponentName(column)"
            v-bind:item="item"
            v-bind:column_name="column.name"
        />
    </tr>
</template>

<script setup lang="ts">
import type {
    ItemSearchResult,
    ListOfSearchResultColumnDefinition,
    SearchResultColumnDefinition,
} from "../../../type";

defineProps<{ item: ItemSearchResult; columns: ListOfSearchResultColumnDefinition }>();
</script>

<script lang="ts">
import { defineComponent } from "vue";
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
import { isAdditionalFieldNumber } from "../../../helpers/additional-custom-properties";

export default defineComponent({
    components: {
        CellId,
        CellTitle,
        CellDescription,
        CellOwner,
        CellUpdateDate,
        CellCreateDate,
        CellObsolescenceDate,
        CellLocation,
        CellFilename,
        CellStatus,
        CellCustomProperty,
    },
    methods: {
        getComponentName(column: SearchResultColumnDefinition): string {
            if (isAdditionalFieldNumber(column.name)) {
                return "cell-custom-property";
            }

            return "cell-" + column.name.replace("_", "-");
        },
    },
});
</script>

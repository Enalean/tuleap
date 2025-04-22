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
    <button
        class="taskboard-header-collapse-column"
        type="button"
        v-bind:title="title"
        v-on:click="collapseColumn(column)"
        data-test="button"
    >
        <i class="fa fa-minus-square" aria-hidden="true"></i>
    </button>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { ColumnDefinition } from "../../../../type";
import { useNamespacedActions } from "vuex-composition-helpers";

const { $gettext, interpolate } = useGettext();

const props = defineProps<{
    column: ColumnDefinition;
}>();

const { collapseColumn } = useNamespacedActions("column", ["collapseColumn"]);

const title = computed((): string => {
    return interpolate($gettext('Collapse "%{ label }" column'), {
        label: props.column.label,
    });
});
</script>

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
    <div class="taskboard-header" v-bind:class="getClasses()">
        <collapse-button v-bind:column="column" />
        <span class="taskboard-header-label" data-test="label">{{ column.label }}</span>
        <cards-in-column-count v-bind:column="column" />
        <wrong-color-popover v-if="shouldPopoverBeDisplayed" v-bind:color="column.color" />
    </div>
</template>
<script setup lang="ts">
import WrongColorPopover from "./WrongColorPopover.vue";
import CollapseButton from "./CollapseButton.vue";
import CardsInColumnCount from "./CardsInColumnCount.vue";
import type { ColumnDefinition } from "../../../../type";
import { useHeaderCell } from "../header-cell-composable";
import { computed } from "vue";
import { useNamespacedState } from "vuex-composition-helpers";
import type { UserState } from "../../../../store/user/type";

const DEFAULT_COLOR = "#F8F8F8";

const props = defineProps<{
    column: ColumnDefinition;
}>();

const { isRgbColor, getClasses } = useHeaderCell(props.column);

const { user_is_admin } = useNamespacedState<Pick<UserState, "user_is_admin">>("user", [
    "user_is_admin",
]);

const isDefaultColor = computed((): boolean => {
    return props.column.color === DEFAULT_COLOR;
});

const shouldPopoverBeDisplayed = computed((): boolean => {
    return user_is_admin.value && isRgbColor() && !isDefaultColor.value;
});
</script>

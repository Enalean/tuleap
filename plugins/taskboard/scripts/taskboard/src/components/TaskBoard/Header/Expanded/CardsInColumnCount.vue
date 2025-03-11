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
    <span class="taskboard-header-count" v-bind:class="classes">
        {{ NbCardsInColumn }}
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { ColumnDefinition } from "../../../../type";
import { useNamespacedGetters } from "vuex-composition-helpers";

const props = defineProps<{
    column: ColumnDefinition;
}>();
const { is_loading_cards, nb_cards_in_column } = useNamespacedGetters("swimlane", [
    "is_loading_cards",
    "nb_cards_in_column",
]);

const classes = computed((): string => {
    return is_loading_cards.value ? "taskboard-header-count-loading" : "";
});

const NbCardsInColumn = computed((): string => {
    return nb_cards_in_column.value(props.column);
});
</script>

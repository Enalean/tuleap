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
    <base-card
        class="taskboard-card-parent"
        v-bind:card="card"
        v-on:editor-closed="emit('editor-closed')"
    >
        <template v-slot:initial_effort>
            <card-initial-effort v-bind:card="card" />
        </template>
        <template v-slot:remaining_effort>
            <div class="taskboard-card-progress" v-bind:style="{ width: progress_bar_width }"></div>
        </template>
    </base-card>
</template>
<script setup lang="ts">
import { computed } from "vue";
import CardInitialEffort from "./CardInitialEffort.vue";
import { getWidthPercentage } from "../../../../../helpers/progress-bars";
import BaseCard from "./BaseCard.vue";
import type { Card } from "../../../../../type";

const props = defineProps<{ card: Card }>();

const emit = defineEmits<{
    (e: "editor-closed"): void;
}>();

const progress_bar_width = computed((): string => {
    const percentage_width = getWidthPercentage(
        props.card.initial_effort,
        props.card.remaining_effort,
    );
    return `${percentage_width}%`;
});
</script>

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
  -->

<template>
    <div
        ref="cardWithRemainingEffort"
        tabindex="0"
        data-navigation="card"
        data-shortcut="parent-card"
        data-test="card-with-remaining-effort"
    >
        <parent-card v-bind:card="card" v-on:editor-closed="focusCard" />
        <parent-card-remaining-effort v-bind:card="card" v-on:editor-closed="focusCard" />
        <edit-card-buttons v-bind:card="card" v-on:editor-closed="focusCard" />
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import type { Card } from "../../../../../type";
import ParentCard from "./ParentCard.vue";
import ParentCardRemainingEffort from "./ParentCardRemainingEffort.vue";
import EditCardButtons from "./EditMode/EditCardButtons.vue";

defineProps<{
    card: Card;
}>();

const cardWithRemainingEffort = ref();

function focusCard(): void {
    cardWithRemainingEffort.value.focus();
}
</script>

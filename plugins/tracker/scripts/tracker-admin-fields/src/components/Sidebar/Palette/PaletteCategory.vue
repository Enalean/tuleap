<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <div class="category">
        <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
        {{ category.label }}
    </div>
    <div class="field" v-for="field of matching" v-bind:key="field.label">
        <i class="fa-fw" v-bind:class="field.icon" aria-hidden="true"></i>
        {{ field.label }}
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { CategoryOfPaletteFields } from "./type";

const props = defineProps<{ category: CategoryOfPaletteFields; search: string }>();

const matching = computed(() =>
    props.search.trim() === ""
        ? props.category.fields
        : props.category.fields.filter((field) =>
              field.label.toLowerCase().includes(props.search.trim().toLowerCase()),
          ),
);
</script>

<style scoped lang="scss">
.category,
.field {
    display: flex;
    cursor: move;
    gap: var(--tlp-small-spacing);
}

.category {
    margin: 0 0 var(--tlp-medium-spacing);
}

.field {
    margin: 0 0 var(--tlp-medium-spacing) var(--tlp-medium-spacing);

    &:last-child {
        margin-bottom: 0;
    }
}
</style>

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
    <div class="column">
        <display-form-elements v-bind:elements="column.children" />
    </div>
</template>

<script setup lang="ts">
import type { Column } from "../../type";
import DisplayFormElements from "../DisplayFormElements.vue";

defineProps<{
    column: Column;
}>();
</script>

<style lang="scss" scoped>
.column {
    flex: 1 0 auto;
    padding: var(--tlp-medium-spacing);
    border: 1px dashed var(--tlp-neutral-normal-color);
    border-radius: var(--tlp-medium-radius);
}
</style>

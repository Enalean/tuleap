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
  -
  -->

<template>
    <button
        type="button"
        class="tlp-button-primary tlp-button-mini load-all-button"
        v-on:click="loadAll"
    >
        <i class="fa-solid fa-arrow-down"></i>
        {{ $gettext("Load all") }}
    </button>
</template>

<script setup lang="ts">
const emit = defineEmits<{
    click: [];
}>();

function loadAll(): void {
    emit("click");
}
</script>

<style scoped lang="scss">
@use "../../../themes/pretty-title";

.load-all-button {
    display: flex;
    gap: var(--tlp-small-spacing);
    grid-column-start: 2;
    width: fit-content;
}
</style>

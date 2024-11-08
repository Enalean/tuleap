<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <input
        type="search"
        class="tlp-search tlp-search-small"
        v-bind:placeholder="placeholder"
        v-on:input="updateFilter"
    />
</template>

<script setup lang="ts">
defineProps<{
    placeholder: string;
}>();

const emit = defineEmits<{
    (e: "update-filter", value: string): void;
}>();

function updateFilter(event: Event): void {
    const event_target = event.currentTarget;
    if (event_target instanceof HTMLInputElement) {
        emit("update-filter", event_target.value);
    }
}
</script>

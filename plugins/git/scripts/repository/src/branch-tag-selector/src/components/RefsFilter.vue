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
        ref="filter"
        v-bind:value="value"
        v-on:input="onInput"
    />
</template>

<script setup lang="ts">
import { ref } from "vue";

defineProps<{
    value: string;
    placeholder: string;
}>();

const emit = defineEmits<{ (e: "input", value: string): void }>();

const filter = ref<HTMLInputElement>();

function onInput(event: Event): void {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }
    emit("input", event.target.value);
}
</script>

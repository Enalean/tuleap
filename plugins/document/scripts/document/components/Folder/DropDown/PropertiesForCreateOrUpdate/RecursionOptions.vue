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
    <section class="document-properties-recursion-option-section">
        <svg
            class="document-properties-recursion-option-arrow"
            width="20"
            height="11"
            viewBox="0 0 20 11"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M19.2947 6.17726C19.6871 5.8003 19.7053 5.1798 19.3367 4.77944L15.2906 0.383877C15.0703 0.163594 14.7766 0.0350952 14.4645 0.0350952C13.822 0.0350952 13.2897 0.567446 13.2897 1.20993L13.2897 4.19644L1.15178 4.19645C0.515677 4.19645 1.62842e-05 4.71211 1.04905e-05 5.34821V5.34821C4.69678e-06 5.98431 0.51567 6.49998 1.15178 6.49998H13.2897L13.2897 9.21283C13.2897 9.85532 13.822 10.3877 14.4645 10.3877C14.7766 10.3877 15.0703 10.2592 15.2906 10.0389C15.6354 9.69405 18.0672 7.35637 19.2947 6.17726Z"
            />
        </svg>
        <label class="tlp-label" for="recursion-option">{{ $gettext("Recursion option") }}</label>
        <select
            class="tlp-select tlp-select-adjusted"
            id="recursion-option"
            name="recursion"
            v-bind:value="value"
            v-on:input="updateRecursionOption"
        >
            <option value="none"></option>
            <option value="folders">
                {{ $gettext("Apply new values on this folder and its subfolders only") }}
            </option>
            <option value="all_items">
                {{ $gettext("Apply new values on this folder and all its content") }}
            </option>
        </select>
    </section>
</template>
<script setup lang="ts">
defineProps<{ value: string }>();

const emit = defineEmits<{
    (e: "update-recursion-option", value: string): void;
}>();

function updateRecursionOption(event: Event): void {
    if (event.target instanceof HTMLSelectElement) {
        emit("update-recursion-option", event.target.value);
    }
}
</script>

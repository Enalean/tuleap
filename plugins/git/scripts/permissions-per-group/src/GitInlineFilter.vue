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
    <div class="tlp-table-actions">
        <div class="tlp-table-actions-spacer"></div>
        <div class="tlp-form-element tlp-table-actions-element">
            <input
                type="search"
                class="tlp-search"
                autocomplete="off"
                v-bind:placeholder="placeholder"
                v-bind:value="modelValue"
                v-on:keyup="search"
                data-test="git-inline-filter-input"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

// modelValue in camel case is needed for v-model until vue 3.4 (replaced by defineModel)
// eslint-disable-next-line vue/prop-name-casing
defineProps<{ modelValue?: string }>();

const { $gettext } = useGettext();

const placeholder = computed(() => $gettext("Repository name"));

const emit = defineEmits<{
    "update:modelValue": [value: string];
}>();

function search(event: Event): void {
    if (event.target instanceof HTMLInputElement) {
        emit("update:modelValue", event.target.value);
    }
}
</script>

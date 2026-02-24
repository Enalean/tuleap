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
        <button
            type="button"
            class="expand-collapse"
            v-bind:title="title"
            v-on:click="toggle"
            data-test="expand-collapse"
        >
            <i
                class="fa-fw fa-solid fa-caret-down"
                v-if="is_open"
                role="img"
                v-bind:aria-label="title"
            ></i>
            <i
                class="fa-fw fa-solid fa-caret-right"
                v-else
                role="img"
                v-bind:aria-label="title"
            ></i>
        </button>
        <i class="fa-regular fa-folder-open" v-if="is_open" aria-hidden="true"></i>
        <i class="fa-solid fa-folder" v-else aria-hidden="true"></i>
        {{ category.label }}
    </div>
    <template v-if="is_open">
        <div
            class="field"
            v-for="field of matching"
            v-bind:key="field.label"
            v-bind:title="$gettext('Not implemented yet')"
        >
            <span class="fa-fw" aria-hidden="true"></span>
            <i class="fa-fw" v-bind:class="field.icon" aria-hidden="true"></i>
            {{ field.label }}
        </div>
    </template>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { CategoryOfPaletteFields } from "./type";
import { useGettext } from "vue3-gettext";

const props = defineProps<{ category: CategoryOfPaletteFields; search: string }>();

const matching = computed(() =>
    props.search.trim() === ""
        ? props.category.fields
        : props.category.fields.filter((field) =>
              field.label.toLowerCase().includes(props.search.trim().toLowerCase()),
          ),
);

const { $gettext } = useGettext();

const is_open = ref(true);
const title = computed(() => (is_open.value ? $gettext("Collapse") : $gettext("Expand")));

function toggle(): void {
    is_open.value = !is_open.value;
}

watch(
    () => props.search,
    () => {
        is_open.value = true;
    },
);
</script>

<style scoped lang="scss">
.category,
.field {
    display: flex;
    margin: 0 0 var(--tlp-medium-spacing);
    gap: var(--tlp-small-spacing);
}

.field {
    color: var(--tlp-dimmed-color);
    cursor: not-allowed;
}

.field:last-child {
    margin-bottom: 0;
}

.expand-collapse {
    padding: 0;
    border: 0;
    background: none;
    font-size: 1rem;

    &:focus {
        box-shadow: var(--tlp-shadow-focus);
    }
}
</style>

<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <button v-on:click="toggle_state.toggle" v-bind:title="title">
        <i v-bind:class="icon" aria-hidden="true"></i>
    </button>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { ToggleState } from "./toggle-state";

const props = defineProps<{ toggle_state: ToggleState }>();

const icon = computed(() =>
    props.toggle_state.is_open.value ? "fa-solid fa-caret-down" : "fa-solid fa-caret-right",
);

const { $gettext } = useGettext();
const title = computed(() =>
    props.toggle_state.is_open.value
        ? $gettext("Hide automatic versions")
        : $gettext("Display automatic versions"),
);
</script>

<style scoped lang="scss">
button {
    padding: var(--tlp-small-spacing);
    transition: color 75ms;
    border: unset;
    background: none;
    color: var(--tlp-dimmed-color);
    cursor: pointer;

    &:focus,
    &:hover {
        color: var(--tlp-main-color);
    }
}
</style>

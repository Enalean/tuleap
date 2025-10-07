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
    <button type="button" v-bind:title="title" v-on:click="click">
        <i class="fa-solid fa-eye" role="img"></i>
    </button>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const title = $gettext("Display this version");

const emit = defineEmits<{ (e: "click", event: Event): void }>();

function click(evt: Event): void {
    emit("click", evt);
}
</script>

<style scoped lang="scss">
button {
    padding: var(--tlp-small-spacing);
    border: unset;
    opacity: 0;
    background: none;
    color: var(--tlp-main-color);
    pointer-events: none;

    &:focus,
    &:hover {
        opacity: 1;
        pointer-events: auto;
    }
}
</style>

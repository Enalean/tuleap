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
    <div class="reorder-arrows">
        <button
            data-test="move-up"
            class="tlp-button-primary"
            type="button"
            v-bind:title="title_up"
            v-bind:class="{ 'hide-button': is_first }"
            v-bind:disabled="is_first"
            v-on:click="onUp"
        >
            <i class="fa-solid fa-chevron-up" role="img"></i>
        </button>
        <button
            data-test="move-down"
            class="tlp-button-primary"
            type="button"
            v-bind:title="title_down"
            v-bind:class="{ 'hide-button': is_last }"
            v-bind:disabled="is_last"
            v-on:click="onDown"
        >
            <i class="fa-solid fa-chevron-down" role="img"></i>
        </button>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";

const props = defineProps<{
    is_first: boolean;
    is_last: boolean;
    field: ConfigurationField;
}>();

const emit = defineEmits<{
    (event: "move-up", field: ConfigurationField): void;
    (event: "move-down", field: ConfigurationField): void;
}>();

const { $gettext } = useGettext();
const title_up = $gettext("Move up");
const title_down = $gettext("Move down");

function onUp(): void {
    emit("move-up", props.field);
}

function onDown(): void {
    emit("move-down", props.field);
}
</script>

<style scoped lang="scss">
@use "@/themes/includes/size";

.reorder-arrows {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

button {
    width: size.$reorder-arrow-size;
    height: size.$reorder-arrow-size;
    padding: 0;
    border-radius: 50%;
    font-size: 0.625rem;
}

.hide-button {
    opacity: 0;
    pointer-events: none;
}
</style>

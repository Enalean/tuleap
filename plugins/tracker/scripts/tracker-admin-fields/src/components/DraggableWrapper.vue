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
    <div
        v-bind:data-element-id="field_id"
        class="draggable-wrapper"
        v-bind:class="{ 'drek-hide': dragged_field_id === field_id }"
        draggable="true"
    >
        <div class="draggable-form-element" draggable="true" data-not-drag-handle="true">
            <slot></slot>
        </div>
        <div class="draggable-handle-container" aria-hidden="true">
            <i
                class="fa-solid fa-grip-vertical draggable-handle"
                v-bind:title="$gettext('Move element')"
            ></i>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { DRAGGED_FIELD_ID } from "../injection-symbols";

const { $gettext } = useGettext();

const dragged_field_id = strictInject(DRAGGED_FIELD_ID);

defineProps<{ field_id: number }>();
</script>

<style lang="scss" scoped>
.draggable-wrapper {
    transition: background 250ms ease-in-out;

    &:hover {
        background: var(--tlp-main-color-hover-background);
    }
}

.draggable-form-element {
    padding: var(--tlp-medium-spacing) 0 var(--tlp-medium-spacing) var(--tlp-medium-spacing);
}
</style>

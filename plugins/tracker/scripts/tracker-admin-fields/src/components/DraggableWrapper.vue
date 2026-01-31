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
    <div v-bind:data-element-id="field_id" class="draggable-wrapper" draggable="true">
        <i
            class="fa-solid fa-grip-vertical draggable-handle"
            role="img"
            v-bind:title="$gettext('Move element')"
        ></i>
        <div class="draggable-form-element">
            <slot></slot>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

defineProps<{ field_id: number }>();
</script>

<style lang="scss">
.draggable-wrapper {
    display: flex;
    flex: 1 0 auto;
    gap: var(--tlp-small-spacing);
    margin: 0 0 var(--tlp-medium-spacing);

    &:last-child {
        margin: 0;
    }

    &:hover > .draggable-handle {
        visibility: visible;
    }

    &.drek-ghost {
        border-radius: var(--tlp-medium-radius);
        border-color: #00000000;
        opacity: 1;
        background: var(--tlp-neutral-normal-color);

        > .draggable-handle,
        > .draggable-form-element {
            visibility: hidden;
        }
    }
}

.draggable-form-element {
    flex: 1 0 auto;
}

.draggable-handle {
    visibility: hidden;
    color: var(--tlp-dimmed-color);
    cursor: move;
}
</style>

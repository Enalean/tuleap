<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
    <span
        v-if="hasColorValue(value)"
        v-bind:class="buildColor(value)"
        class="input-box-colored-element"
        data-test="input-box-colored-element"
    ></span>
</template>

<script setup lang="ts">
import { buildColor, hasColorValue } from "../../helpers/color-helper";
import type { StaticListItem } from "@tuleap/plugin-tracker-rest-api-types";

defineProps<{
    value: StaticListItem;
}>();
</script>

<style scoped lang="scss">
$size-of-color: 16px;
$size-of-border: 2px;

.input-box-colored-element {
    position: relative;
    flex-shrink: 0;
    width: $size-of-color;
    height: $size-of-color;
    border: $size-of-border solid var(--border-color);
    border-radius: 50%;
    background: var(--secondary-color);
}

[data-user-has-accessibility-mode="1"] .input-box-colored-element::before {
    content: "";
    display: block;
    position: absolute;
    width: calc(#{$size-of-color} - 2 * #{$size-of-border});
    height: calc(#{$size-of-color} - 2 * #{$size-of-border});
    border-radius: 50%;
    background: var(--border-color);
    mask-image: var(--accessibility-pattern);
}
</style>

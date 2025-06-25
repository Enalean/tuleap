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
  -->

<template>
    <span
        class="tlp-color-bubble document-color-bubble"
        v-bind:class="`tlp-swatch-${tlp_color}`"
        data-test="tlp-color-bubble"
    />
</template>

<script setup lang="ts">
import type { ColorName } from "@tuleap/plugin-tracker-constants";

defineProps<{
    tlp_color: ColorName;
}>();
</script>

<style scoped lang="scss">
.tlp-color-bubble {
    content: " ";
    display: flex;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: var(--primary-color);
}
</style>

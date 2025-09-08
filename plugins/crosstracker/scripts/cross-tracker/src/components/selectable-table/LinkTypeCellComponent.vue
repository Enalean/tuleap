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
        v-bind:class="{
            'tlp-badge-firemist-silver tlp-badge-outline': cell.direction === FORWARD_DIRECTION,
            'tlp-badge-inca-silver tlp-badge-outline': cell.direction === REVERSE_DIRECTION,
        }"
        data-test="link-type-cell-label"
        >{{ cell.label }}</span
    >
</template>

<script setup lang="ts">
import type { LinkTypeCell } from "../../domain/ArtifactsTable";
import { FORWARD_DIRECTION, REVERSE_DIRECTION } from "../../domain/ArtifactsTable";

defineProps<{
    cell: LinkTypeCell;
}>();
</script>

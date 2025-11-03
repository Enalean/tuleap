<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
        v-if="badge.icon !== ''"
        class="tracker-cross-reference-title-icon"
        v-bind:class="getIconColorClass(badge.color)"
    >
        <i v-bind:class="badge.icon" aria-hidden="true"></i>
    </span>
    <span
        v-else
        class="cross-ref-badge tracker-cross-reference-title-badge"
        v-bind:class="getSwatchClass(badge.color)"
    >
        {{ badge.label }}
    </span>
</template>

<script setup lang="ts">
import type { TitleBadge } from "../../api/references-rest-querier";

defineProps<{
    badge: TitleBadge;
}>();

function getSwatchClass(color: string): string {
    return `tlp-swatch-${color}`;
}

function getIconColorClass(color: string): string {
    return `tracker-cross-reference-title-icon-${color}`;
}
</script>

<style scoped lang="scss">
@use "sass:map";
@use "pkg:@tuleap/tlp-swatch-colors";

.tracker-cross-reference-title-badge {
    margin: 0 4px 0 0;
}

.tracker-cross-reference-title-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    margin: 0 4px 0 0;
    border-radius: 50%;
    font-size: 0.8125rem;
}

@each $color-name, $colors in tlp-swatch-colors.$color-map {
    .tracker-cross-reference-title-icon-#{$color-name} {
        background: map.get($colors, "primary");
    }
}
</style>

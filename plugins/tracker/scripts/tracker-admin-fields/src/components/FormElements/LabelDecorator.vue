<!--
  - Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
    <span class="tlp-badge-primary tlp-badge-outline" v-bind:title="decorator.description">
        <i
            v-bind:class="decorator.icon"
            v-if="decorator.icon"
            aria-hidden="true"
            data-test="icon"
        ></i>
        <a
            v-bind:href="decorator.url"
            v-if="decorator.url"
            data-test="link"
            data-not-drag-handle="true"
        >
            {{ decorator.label }}
        </a>
        <template v-else>{{ decorator.label }}</template>
    </span>
</template>

<script setup lang="ts">
import type { LabelDecorator } from "@tuleap/plugin-tracker-rest-api-types";

defineProps<{
    decorator: LabelDecorator;
}>();
</script>

<style lang="scss" scoped>
i {
    margin: 0 calc(var(--tlp-small-spacing) / 2) 0 0;
}
</style>

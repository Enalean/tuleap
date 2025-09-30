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
    <i
        class="fa-solid fa-circle disc"
        v-bind:class="{ 'disc-for-version-with-title': is_version_with_title }"
        aria-hidden="true"
    ></i>
</template>

<script setup lang="ts">
defineProps<{ is_version_with_title: boolean }>();
</script>

<style scoped lang="scss">
@use "@/themes/includes/versions";

.disc {
    // Make sure that the disc is above the timeline line
    z-index: 1;
    color: var(--timeline-color);
    font-size: #{versions.$disc-width};

    &.disc-for-version-with-title {
        color: var(--timeline-color-for-version-with-title);
    }
}
</style>

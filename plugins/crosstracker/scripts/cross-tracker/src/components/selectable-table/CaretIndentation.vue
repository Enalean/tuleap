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
    <span class="caret-indentation" />
</template>

<script setup lang="ts">
defineProps<{
    level: number;
}>();
</script>

<style scoped lang="scss">
.caret-indentation {
    /* 32px is the equivalent to --tlp-x-large-spacing. We are setting it fixed here
     so that it is not impacted by the theme density. */
    margin-left: calc(32px * v-bind(level));
}
</style>

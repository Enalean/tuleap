<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
            'tlp-badge-danger tlp-badge-outline': is_project_admin,
            'tlp-badge-primary tlp-badge-outline': is_static,
            'tlp-badge-secondary tlp-badge-outline': is_custom,
        }"
    >
        {{ group_name }}
    </span>
</template>

<script lang="ts" setup>
defineProps<{
    is_project_admin: boolean;
    is_static: boolean;
    is_custom: boolean;
    group_name: string;
}>();
</script>

<!--
  - Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
    <span class="cell" data-test="edit-cell"
        ><a v-bind:href="uri" v-bind:title="$gettext('Edit')" class="link"
            ><i class="fa-solid fa-fw fa-edit" role="img"></i></a
    ></span>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

defineProps<{
    uri: string;
}>();
</script>

<style scoped lang="scss">
@use "../../../themes/cell";
@use "../../../themes/links";

.cell {
    @include cell.cell-template;

    grid-column: edit;
    padding-left: var(--tlp-medium-spacing);
}

.link {
    @include links.link;
}
</style>

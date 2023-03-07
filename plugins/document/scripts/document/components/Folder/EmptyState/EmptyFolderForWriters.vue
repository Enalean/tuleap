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
    <section
        class="empty-state-page document-empty-folder-for-writers"
        data-test="document-empty-state"
    >
        <div class="empty-state-illustration">
            <empty-folder-for-writers-svg />
        </div>
        <h1 class="empty-state-title">{{ $gettext("It's time to add new documents!") }}</h1>
        <div class="empty-state-action">
            <new-item-dropdown v-bind:item="current_folder" />
        </div>
    </section>
</template>

<script setup lang="ts">
import EmptyFolderForWritersSvg from "../../svg/folder/EmptyFolderForWritersSvg.vue";
import type { RootState } from "../../../type";
import { useNamespacedState } from "vuex-composition-helpers";
import NewItemDropdown from "../DropDown/NewDocument/NewItemDropdown.vue";

const { current_folder } = useNamespacedState<Pick<RootState, "current_folder">>([
    "current_folder",
]);
</script>

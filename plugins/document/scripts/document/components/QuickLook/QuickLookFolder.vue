<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div class="document-quick-look-document-action">
        <new-item-dropdown
            v-if="item.user_can_write"
            v-bind:item="item"
            v-bind:is_in_quicklook="true"
            class="document-quick-look-folder-action-new-folder-button"
            data-test="create-folder-button"
        />
        <drop-down-quick-look v-bind:item="item" />
        <template v-if="can_delete_folder">
            <div class="document-header-spacer"></div>
            <quick-look-delete-button v-bind:item="item" data-test="delete-folder-button" />
        </template>
    </div>
</template>

<script setup lang="ts">
import QuickLookDeleteButton from "../Folder/ActionsQuickLookButton/QuickLookDeleteButton.vue";
import DropDownQuickLook from "../Folder/DropDown/DropDownQuickLook.vue";
import type { Item } from "../../type";
import { computed } from "vue";
import NewItemDropdown from "../Folder/DropDown/NewDocument/NewItemDropdown.vue";

const props = defineProps<{ item: Item }>();

const can_delete_folder = computed((): boolean => {
    return props.item.user_can_write;
});
</script>

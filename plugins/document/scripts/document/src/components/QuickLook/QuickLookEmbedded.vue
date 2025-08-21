<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
        <router-link
            class="tlp-button-primary tlp-button-small"
            v-bind:to="{ name: 'item', params: { folder_id: item.parent_id, item_id: item.id } }"
        >
            {{ $gettext("Open") }}
            <i class="fa-solid fa-right-long tlp-button-icon" aria-hidden="true"></i>
        </router-link>
        <drop-down-quick-look v-bind:item="item" />
    </div>
</template>

<script setup lang="ts">
import DropDownQuickLook from "../Folder/DropDown/DropDownQuickLook.vue";
import type { Item } from "../../type";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

defineProps<{ item: Item }>();
</script>

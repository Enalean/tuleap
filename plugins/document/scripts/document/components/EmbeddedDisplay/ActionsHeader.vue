<!--
  - Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div class="document-header-actions">
        <div class="document-dropdown-menu-button">
            <create-new-item-version-button
                class="tlp-button-primary"
                v-bind:item="item"
                button-classes=""
                icon-classes="fa-solid fa-share tlp-button-icon"
                data-test="item-action-create-new-version-button"
                v-if="item.user_can_write"
            />
            <drop-down-button
                v-bind:is-in-large-mode="false"
                v-bind:is-appended="false"
                v-bind:is-in-quick-look-mode="false"
            >
                <drop-down-displayed-embedded />
            </drop-down-button>
        </div>
    </div>
</template>

<script setup lang="ts">
import CreateNewItemVersionButton from "../Folder/DropDown/NewVersion/NewItemVersionButton.vue";
import DropDownButton from "../Folder/DropDown/DropDownButton.vue";
import DropDownDisplayedEmbedded from "../Folder/DropDown/DropDownDisplayedEmbedded.vue";
import type { Item } from "../../type";

defineProps<{ item: Item }>();
</script>

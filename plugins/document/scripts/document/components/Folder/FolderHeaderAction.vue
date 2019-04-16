<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="tlp-dropdown-split-button">
        <new-item-button class="tlp-button-primary tlp-dropdown-split-button-main"
                         v-bind:item="item"
                         v-if="item.user_can_write"
                         data-test="item-action-new-button"
        />
        <details-item-button v-bind:item="item"
                             v-else data-test="item-action-details-button"
                             v-bind:button-class="'tlp-dropdown-split-button-main'"
        />
        <dropdown-button>
            <dropdown-menu-current-folder/>
        </dropdown-button>
    </div>
</template>

<script>
import NewItemButton from "./ActionsButton/NewItemButton.vue";
import DetailsItemButton from "./ActionsButton/DetailsItemButton.vue";
import DropdownButton from "./ActionsDropDown/DropdownButton.vue";
import DropdownMenuCurrentFolder from "./ActionsDropDown/DropdownMenuCurrentFolder.vue";
export default {
    name: "FolderHeaderAction",
    components: { DropdownMenuCurrentFolder, DropdownButton, DetailsItemButton, NewItemButton },
    props: {
        item: Object
    }
};
</script>

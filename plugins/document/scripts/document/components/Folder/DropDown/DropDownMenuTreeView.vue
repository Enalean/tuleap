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
    <drop-down-menu
        v-bind:item="item"
        v-bind:is-in-quick-look-mode="true"
        v-bind:is-in-folder-empty-state="false"
        role="menu"
    >
        <drop-down-item-title
            slot="display-item-title"
            v-bind:item="item"
            data-test="document-folder-title"
        />

        <template v-if="item.user_can_write && is_item_a_folder(item)">
            <new-folder-secondary-action
                v-bind:item="item"
                slot="new-folder-secondary-action"
                data-test="document-folder-content-creation"
            />
            <new-document v-bind:item="item" slot="new-document" />
        </template>

        <template v-if="!is_item_a_folder(item)">
            <lock-item
                v-bind:item="item"
                data-test="document-dropdown-menu-lock-item"
                slot="lock-item"
            />
            <unlock-item
                v-bind:item="item"
                data-test="document-dropdown-menu-unlock-item"
                slot="unlock-item"
            />
        </template>

        <template v-if="item.user_can_write && !is_item_a_folder(item)">
            <create-new-item-version-button
                v-bind:item="item"
                v-bind:button-classes="`tlp-dropdown-menu-item`"
                v-bind:icon-classes="`fas fa-fw fa-share tlp-dropdown-menu-item-icon`"
                v-if="!is_item_a_folder(item)"
                data-test="document-dropdown-create-new-version-button"
                slot="new-item-version"
            />
        </template>

        <template v-if="item.user_can_write">
            <update-properties
                slot="update-properties"
                v-bind:item="item"
                v-if="item.user_can_write"
                data-test="document-update-properties"
            />
            <update-permissions v-bind:item="item" slot="update-permissions" />
            <drop-down-separator slot="delete-item-separator" />
            <delete-item
                v-bind:item="item"
                role="menuitem"
                data-test="document-dropdown-delete"
                slot="delete-item"
            />
        </template>
    </drop-down-menu>
</template>
<script lang="ts">
import DropDownMenu from "./DropDownMenu.vue";
import CreateNewItemVersionButton from "../DropDown/NewVersion/NewItemVersionButton.vue";
import DeleteItem from "./Delete/DeleteItem.vue";
import NewFolderSecondaryAction from "./NewFolderSecondaryAction.vue";
import DropDownSeparator from "./DropDownSeparator.vue";
import NewDocument from "./NewDocument/NewDocument.vue";
import LockItem from "./Lock/LockItem.vue";
import UnlockItem from "./Lock/UnlockItem.vue";
import UpdateProperties from "./UpdateMetadata/UpdateProperties.vue";
import UpdatePermissions from "./UpdatePermissions.vue";
import DropDownItemTitle from "./DropDownItemTitle.vue";
import { isFolder } from "../../../helpers/type-check-helper";
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Item } from "../../../type";

@Component({
    components: {
        DropDownItemTitle,
        UpdateProperties,
        UpdatePermissions,
        UnlockItem,
        LockItem,
        NewDocument,
        DropDownSeparator,
        NewFolderSecondaryAction,
        DeleteItem,
        CreateNewItemVersionButton,
        DropDownMenu,
    },
})
export default class DropDownMenuTreeView extends Vue {
    @Prop({ required: true })
    readonly item!: Item;
    is_item_a_folder(item: Item): boolean {
        return isFolder(item);
    }
}
</script>

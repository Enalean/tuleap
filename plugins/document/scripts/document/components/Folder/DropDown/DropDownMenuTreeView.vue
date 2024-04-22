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
    <div class="dropdown-menu">
        <drop-down-item-title
            slot="display-item-title"
            v-bind:item="item"
            data-test="document-folder-title"
        />

        <new-item-submenu
            v-bind:item="item"
            slot="new-document"
            data-test="document-folder-content-creation"
            v-if="item.user_can_write && is_item_a_folder"
        />

        <template v-if="should_display_lock_unlock">
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

        <download-file
            v-if="is_item_a_file"
            v-bind:item="item"
            slot="download"
            data-test="document-dropdown-menu-download-file"
        />

        <create-new-item-version-button
            v-bind:item="item"
            v-bind:button-classes="`tlp-dropdown-menu-item`"
            v-bind:icon-classes="`fa-solid fa-fw fa-share tlp-dropdown-menu-item-icon`"
            v-if="should_display_new_version_button"
            data-test="document-dropdown-create-new-version-button"
            slot="new-item-version"
        />
        <new-version-empty-submenu
            v-bind:item="item"
            data-test="document-dropdown-create-new-version-button"
            slot="new-item-version"
            v-if="item.user_can_write && !is_item_a_folder && is_item_an_empty"
        />

        <template v-if="item.user_can_write">
            <update-properties
                slot="update-properties"
                v-bind:item="item"
                v-if="should_display_update_properties"
                data-test="document-update-properties"
            />
            <update-permissions v-bind:item="item" slot="update-permissions" />
        </template>
        <drop-down-menu v-bind:item="item" />
        <template v-if="item.user_can_write">
            <drop-down-separator slot="delete-item-separator" v-if="should_display_delete_item" />
            <delete-item
                v-bind:item="item"
                role="menuitem"
                data-test="document-dropdown-delete"
                slot="delete-item"
                v-if="should_display_delete_item"
            />
        </template>
    </div>
</template>

<script setup lang="ts">
import DropDownMenu from "./DropDownMenu.vue";
import CreateNewItemVersionButton from "../DropDown/NewVersion/NewItemVersionButton.vue";
import DeleteItem from "./Delete/DeleteItem.vue";
import DropDownSeparator from "./DropDownSeparator.vue";
import LockItem from "./Lock/LockItem.vue";
import UnlockItem from "./Lock/UnlockItem.vue";
import UpdateProperties from "./UpdateProperties/UpdateProperties.vue";
import UpdatePermissions from "./UpdatePermissions.vue";
import DropDownItemTitle from "./DropDownItemTitle.vue";
import { isEmpty, isFile, isFolder, isOtherType } from "../../../helpers/type-check-helper";
import type { Item } from "../../../type";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";
import { computed } from "vue";
import { canUpdateProperties } from "../../../helpers/can-update-properties-helper";
import { canDelete } from "../../../helpers/can-delete-helper";
import DownloadFile from "./DownloadFile.vue";
import NewItemSubmenu from "./NewDocument/NewItemSubmenu.vue";
import NewVersionEmptySubmenu from "./NewVersion/NewVersionEmptySubmenu.vue";

const props = defineProps<{ item: Item }>();

const { forbid_writers_to_update, forbid_writers_to_delete } = useNamespacedState<
    Pick<ConfigurationState, "forbid_writers_to_update" | "forbid_writers_to_delete">
>("configuration", ["forbid_writers_to_update", "forbid_writers_to_delete"]);

const is_item_a_folder = computed((): boolean => {
    return isFolder(props.item);
});

const is_item_another_type = computed((): boolean => {
    return isOtherType(props.item);
});

const is_item_a_file = computed((): boolean => {
    return isFile(props.item);
});

const is_item_an_empty = computed((): boolean => {
    return isEmpty(props.item);
});

const should_display_update_properties = computed((): boolean => {
    return canUpdateProperties(forbid_writers_to_update.value, props.item);
});

const should_display_delete_item = computed((): boolean => {
    return canDelete(forbid_writers_to_delete.value, props.item);
});

const should_display_lock_unlock = computed(
    (): boolean => !is_item_another_type.value && !is_item_a_folder.value,
);

const should_display_new_version_button = computed(
    (): boolean =>
        !is_item_another_type.value &&
        !is_item_an_empty.value &&
        !is_item_a_folder.value &&
        props.item.user_can_write,
);
</script>

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
    <div class="document-quick-look-folder-action">
        <drop-down-button
            v-bind:is-in-quick-look-mode="true"
            v-bind:is-in-large-mode="false"
            v-bind:is-appended="false"
        >
            <download-file
                v-if="should_display_download_button"
                v-bind:item="item"
                slot="download"
                data-test="document-dropdown-menu-download-file"
            />
            <create-new-item-version-button
                v-bind:item="item"
                v-bind:button-classes="`tlp-dropdown-menu-item`"
                v-bind:icon-classes="`fa-solid fa-fw fa-share tlp-dropdown-menu-item-icon`"
                v-if="should_display_new_version_button"
                data-test="document-quicklook-action-button-new-version"
                slot="new-item-version"
            />

            <template v-if="!is_item_a_folder && item.user_can_write">
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
                <drop-down-separator slot="display-item-title-separator" />
            </template>

            <update-properties
                slot="update-properties"
                data-test="document-dropdown-menu-update-properties"
                v-bind:item="item"
                v-if="should_display_update_properties"
            />
            <update-permissions slot="update-permissions" v-bind:item="item" />

            <drop-down-menu v-bind:item="item" />

            <drop-down-separator slot="delete-item-separator" v-if="should_display_delete" />
            <delete-item
                v-bind:item="item"
                role="menuitem"
                data-test="document-quick-look-delete-button"
                slot="delete-item"
                v-if="should_display_delete"
            />
        </drop-down-button>
    </div>
</template>
<script setup lang="ts">
import DropDownMenu from "./DropDownMenu.vue";
import CreateNewItemVersionButton from "./NewVersion/NewItemVersionButton.vue";
import DropDownButton from "./DropDownButton.vue";
import LockItem from "./Lock/LockItem.vue";
import UnlockItem from "./Lock/UnlockItem.vue";
import DropDownSeparator from "./DropDownSeparator.vue";
import UpdateProperties from "./UpdateProperties/UpdateProperties.vue";
import UpdatePermissions from "./UpdatePermissions.vue";
import { isEmpty, isFile, isFolder, isWiki } from "../../../helpers/type-check-helper";
import type { Item } from "../../../type";
import { computed } from "vue";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";
import { canUpdateProperties } from "../../../helpers/can-update-properties-helper";
import { canDelete } from "../../../helpers/can-delete-helper";
import DeleteItem from "./Delete/DeleteItem.vue";
import DownloadFile from "./DownloadFile.vue";

const props = defineProps<{ item: Item }>();

const { forbid_writers_to_update, forbid_writers_to_delete, is_deletion_allowed } =
    useNamespacedState<
        Pick<
            ConfigurationState,
            "forbid_writers_to_update" | "forbid_writers_to_delete" | "is_deletion_allowed"
        >
    >("configuration", [
        "forbid_writers_to_update",
        "forbid_writers_to_delete",
        "is_deletion_allowed",
    ]);

const is_item_a_wiki_with_approval_table = computed((): boolean => {
    return isWiki(props.item) && props.item.approval_table !== null;
});

const is_item_a_folder = computed((): boolean => {
    return isFolder(props.item);
});

const should_display_download_button = computed(
    (): boolean =>
        isFile(props.item) &&
        props.item.file_properties !== null &&
        (props.item.file_properties.open_href || "") !== "",
);

const should_display_new_version_button = computed(
    (): boolean =>
        !is_item_a_wiki_with_approval_table.value &&
        !is_item_a_folder.value &&
        !isEmpty(props.item) &&
        props.item.user_can_write &&
        !is_item_a_folder.value,
);

const should_display_update_properties = computed((): boolean =>
    canUpdateProperties(forbid_writers_to_update.value, props.item),
);

const should_display_delete = computed(
    (): boolean =>
        is_deletion_allowed.value && canDelete(forbid_writers_to_delete.value, props.item),
);

defineExpose({
    should_display_delete,
    should_display_new_version_button,
    should_display_update_properties,
});
</script>

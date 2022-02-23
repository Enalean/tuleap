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
        v-bind:is-in-folder-empty-state="isInFolderEmptyState"
        v-bind:is-in-quick-look-mode="false"
        v-bind:item="current_folder"
    >
        <drop-down-item-title
            slot="display-item-title"
            v-bind:item="current_folder"
            data-test="document-folder-title"
        />
        <template v-if="current_folder.user_can_write">
            <new-folder-secondary-action
                v-bind:item="current_folder"
                slot="new-folder-secondary-action"
                data-test="document-new-folder-creation-button"
            />

            <update-properties
                v-bind:item="current_folder"
                data-test="document-update-properties"
                slot="update-properties"
            />
            <update-permissions v-bind:item="current_folder" slot="update-permissions" />

            <drop-down-separator
                slot="delete-item-separator"
                v-if="can_user_delete_item"
                data-test="document-delete-folder-separator"
            />
            <delete-item
                v-bind:item="current_folder"
                role="menuitem"
                data-test="document-delete-folder-button"
                slot="delete-item"
                v-if="can_user_delete_item"
            />
        </template>
    </drop-down-menu>
</template>

<script lang="ts">
import DropDownMenu from "./DropDownMenu.vue";
import NewFolderSecondaryAction from "./NewFolderSecondaryAction.vue";
import DropDownSeparator from "./DropDownSeparator.vue";
import DeleteItem from "./Delete/DeleteItem.vue";
import UpdateProperties from "./UpdateProperties/UpdateProperties.vue";
import UpdatePermissions from "./UpdatePermissions.vue";
import DropDownItemTitle from "./DropDownItemTitle.vue";
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Folder } from "../../../type";
import { State } from "vuex-class";

@Component({
    components: {
        DropDownItemTitle,
        UpdateProperties,
        UpdatePermissions,
        DeleteItem,
        DropDownSeparator,
        NewFolderSecondaryAction,
        DropDownMenu,
    },
})
export default class DropDownCurrentFolder extends Vue {
    @Prop({ required: true })
    readonly isInFolderEmptyState!: boolean;

    @State
    readonly current_folder!: Folder;

    get can_user_delete_item(): boolean {
        return Boolean(this.current_folder.user_can_write && this.current_folder.parent_id);
    }
}
</script>

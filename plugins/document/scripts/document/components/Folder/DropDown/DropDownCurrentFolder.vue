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
    <drop-down-menu v-bind:item="current_folder">
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
                v-if="should_display_update_properties"
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
import { Component, Vue } from "vue-property-decorator";
import type { Folder } from "../../../type";
import { namespace, State } from "vuex-class";
import { canUpdateProperties } from "../../../helpers/can-update-properties-helper";
import { canDelete } from "../../../helpers/can-delete-helper";

const configuration = namespace("configuration");

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
    @State
    readonly current_folder!: Folder;

    @configuration.State
    readonly forbid_writers_to_update!: boolean;

    @configuration.State
    readonly forbid_writers_to_delete!: boolean;

    get can_user_delete_item(): boolean {
        return (
            this.current_folder.user_can_write &&
            canDelete(this.forbid_writers_to_delete, this.current_folder) &&
            Boolean(this.current_folder.parent_id)
        );
    }

    get should_display_update_properties(): boolean {
        return canUpdateProperties(this.forbid_writers_to_update, this.current_folder);
    }
}
</script>

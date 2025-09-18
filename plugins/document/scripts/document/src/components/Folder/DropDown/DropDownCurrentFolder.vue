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
            v-bind:item="current_folder"
            data-test="document-folder-title"
        />
        <template v-if="current_folder.user_can_write">
            <update-properties
                v-bind:item="current_folder"
                data-test="document-update-properties"
                slot="update-properties"
                v-if="should_display_update_properties"
            />
            <update-permissions v-bind:item="current_folder" slot="update-permissions" />

            <drop-down-menu v-bind:item="current_folder" />
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
    </div>
</template>

<script setup lang="ts">
import DropDownMenu from "./DropDownMenu.vue";
import DropDownSeparator from "./DropDownSeparator.vue";
import DeleteItem from "./Delete/DeleteItem.vue";
import UpdateProperties from "./UpdateProperties/UpdateProperties.vue";
import UpdatePermissions from "./UpdatePermissions.vue";
import DropDownItemTitle from "./DropDownItemTitle.vue";
import type { State } from "../../../type";
import { canUpdateProperties } from "../../../helpers/can-update-properties-helper";
import { canDelete } from "../../../helpers/can-delete-helper";
import { useState } from "vuex-composition-helpers";
import { computed } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { FORBID_WRITERS_TO_DELETE, FORBID_WRITERS_TO_UPDATE } from "../../../configuration-keys";

const { current_folder } = useState<Pick<State, "current_folder">>(["current_folder"]);
const forbid_writers_to_update = strictInject(FORBID_WRITERS_TO_UPDATE);
const forbid_writers_to_delete = strictInject(FORBID_WRITERS_TO_DELETE);

const can_user_delete_item = computed((): boolean => {
    return (
        current_folder.value.user_can_write &&
        canDelete(forbid_writers_to_delete, current_folder.value) &&
        Boolean(current_folder.value.parent_id)
    );
});

const should_display_update_properties = computed((): boolean => {
    return canUpdateProperties(forbid_writers_to_update, current_folder.value);
});
</script>

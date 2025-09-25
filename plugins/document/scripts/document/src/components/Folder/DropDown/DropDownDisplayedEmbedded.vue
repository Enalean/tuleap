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
        <template v-if="currently_previewed_item.user_can_write">
            <lock-item
                v-bind:item="currently_previewed_item"
                v-bind:document_lock="getDocumentLock()"
                data-test="document-dropdown-menu-lock-item"
                slot="lock-item"
            />
            <unlock-item
                v-bind:item="currently_previewed_item"
                data-test="document-dropdown-menu-unlock-item"
                slot="unlock-item"
            />
            <drop-down-separator
                slot="display-item-title-separator"
                data-test="document-dropdown-separator"
            />
            <update-properties
                v-bind:item="currently_previewed_item"
                data-test="document-update-properties"
                slot="update-properties"
                v-if="should_display_update_properties"
            />
            <update-permissions v-bind:item="currently_previewed_item" slot="update-permissions" />

            <drop-down-menu v-bind:item="currently_previewed_item" />
            <drop-down-separator slot="delete-item-separator" v-if="can_user_delete_item" />
            <delete-item
                v-bind:item="currently_previewed_item"
                role="menuitem"
                data-test="document-delete-item"
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
import LockItem from "./Lock/LockItem.vue";
import UnlockItem from "./Lock/UnlockItem.vue";
import UpdateProperties from "./UpdateProperties/UpdateProperties.vue";
import UpdatePermissions from "./UpdatePermissions.vue";
import type { State } from "../../../type";
import { canUpdateProperties } from "../../../helpers/can-update-properties-helper";
import { canDelete } from "../../../helpers/can-delete-helper";
import { useState } from "vuex-composition-helpers";
import { computed } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { FORBID_WRITERS_TO_DELETE, FORBID_WRITERS_TO_UPDATE } from "../../../configuration-keys";
import { getDocumentLock } from "../../../helpers/lock/document-lock";

const { currently_previewed_item } = useState<Pick<State, "currently_previewed_item">>([
    "currently_previewed_item",
]);
const forbid_writers_to_update = strictInject(FORBID_WRITERS_TO_UPDATE);
const forbid_writers_to_delete = strictInject(FORBID_WRITERS_TO_DELETE);

const can_user_delete_item = computed((): boolean => {
    return (
        currently_previewed_item.value !== null &&
        currently_previewed_item.value.user_can_write &&
        canDelete(forbid_writers_to_delete, currently_previewed_item.value) &&
        Boolean(currently_previewed_item.value.parent_id)
    );
});

const should_display_update_properties = computed((): boolean => {
    if (!currently_previewed_item.value) {
        return false;
    }
    return canUpdateProperties(forbid_writers_to_update, currently_previewed_item.value);
});
</script>

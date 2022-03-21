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
        <div class="tlp-dropdown-split-button">
            <create-new-item-version-button
                v-bind:item="item"
                v-bind:button-classes="'tlp-button-primary tlp-button-outline tlp-button-small tlp-dropdown-split-button-main'"
                v-bind:icon-classes="'fas fa-share tlp-button-icon'"
                v-if="!is_item_a_wiki_with_approval_table && !is_item_a_folder"
                data-test="document-quicklook-action-button-new-version"
            />
            <new-item-button
                v-if="item.user_can_write && is_item_a_folder"
                class="tlp-button-primary tlp-button-small tlp-button-outline"
                v-bind:item="item"
                data-test="document-quicklook-action-button-new-item"
            />
            <drop-down-button
                v-bind:is-in-quick-look-mode="true"
                v-bind:is-in-folder-empty-state="false"
                v-bind:is-in-large-mode="false"
                v-bind:is-appended="item.user_can_write && !is_item_a_wiki_with_approval_table"
            >
                <drop-down-menu v-bind:item="item">
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
                </drop-down-menu>
            </drop-down-button>
        </div>
    </div>
</template>
<script setup lang="ts">
import DropDownMenu from "./DropDownMenu.vue";
import CreateNewItemVersionButton from "./NewVersion/NewItemVersionButton.vue";
import NewItemButton from "../ActionsButton/NewItemButton.vue";
import DropDownButton from "./DropDownButton.vue";
import LockItem from "./Lock/LockItem.vue";
import UnlockItem from "./Lock/UnlockItem.vue";
import DropDownSeparator from "./DropDownSeparator.vue";
import UpdateProperties from "./UpdateProperties/UpdateProperties.vue";
import UpdatePermissions from "./UpdatePermissions.vue";
import { isFolder, isWiki } from "../../../helpers/type-check-helper";
import type { Item } from "../../../type";
import { computed } from "@vue/composition-api";
import { useState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";
import { canUpdateProperties } from "../../../helpers/can-update-properties-helper";

const props = defineProps<{ item: Item }>();

const { forbid_writers_to_update } = useState<Pick<ConfigurationState, "forbid_writers_to_update">>(
    "configuration",
    ["forbid_writers_to_update"]
);

const is_item_a_wiki_with_approval_table = computed((): boolean => {
    return isWiki(props.item) && props.item.approval_table !== null;
});

const is_item_a_folder = computed((): boolean => {
    return isFolder(props.item);
});

const should_display_update_properties = computed((): boolean => {
    return canUpdateProperties(forbid_writers_to_update.value, props.item);
});
</script>

<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>

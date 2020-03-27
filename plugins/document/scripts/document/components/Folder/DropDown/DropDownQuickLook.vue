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
                v-bind:icon-classes="'fa fa-mail-forward tlp-button-icon'"
                v-if="!is_item_a_wiki_with_approval_table && !is_item_a_folder(item)"
                data-test="document-quicklook-action-button-new-version"
            />
            <new-item-button
                v-if="item.user_can_write && is_item_a_folder(item)"
                class="tlp-button-primary tlp-button-small tlp-button-outline"
                v-bind:item="item"
                data-test="document-quicklook-action-button-new-item"
            />
            <drop-down-button
                v-bind:is-in-quick-look-mode="true"
                v-bind:is-appended="item.user_can_write && !is_item_a_wiki_with_approval_table"
            >
                <drop-down-menu v-bind:item="item" v-bind:is-in-quick-look-mode="true">
                    <template v-if="!is_item_a_folder(item) && item.user_can_write">
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
                        v-if="item.user_can_write"
                    />
                    <update-permissions slot="update-permissions" v-bind:item="item" />
                </drop-down-menu>
            </drop-down-button>
        </div>
    </div>
</template>
<script>
import { mapGetters } from "vuex";
import DropDownMenu from "./DropDownMenu.vue";
import CreateNewItemVersionButton from "../ActionsButton/NewItemVersionButton.vue";
import NewItemButton from "../ActionsButton/NewItemButton.vue";
import DropDownButton from "./DropDownButton.vue";
import LockItem from "./LockItem.vue";
import UnlockItem from "./UnlockItem.vue";
import DropDownSeparator from "./DropDownSeparator.vue";
import UpdateProperties from "./UpdateProperties.vue";
import UpdatePermissions from "./UpdatePermissions.vue";
import { TYPE_WIKI } from "../../../constants.js";

export default {
    name: "DropDownQuickLook",
    components: {
        UpdateProperties,
        UpdatePermissions,
        DropDownSeparator,
        UnlockItem,
        LockItem,
        DropDownButton,
        CreateNewItemVersionButton,
        NewItemButton,
        DropDownMenu,
    },
    props: {
        item: Object,
    },
    computed: {
        ...mapGetters(["is_item_a_folder"]),
        is_item_a_wiki_with_approval_table() {
            return this.item.type === TYPE_WIKI && this.item.approval_table !== null;
        },
    },
};
</script>

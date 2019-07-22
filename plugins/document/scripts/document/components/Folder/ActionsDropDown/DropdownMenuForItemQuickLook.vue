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
    <dropdown-menu v-bind:item="item"
                   v-bind:is-in-quick-look-mode="true"
                   role="menu"
    >
        <a
            v-if="is_item_a_folder(item) && item.user_can_write"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            v-on:click.prevent="showNewFolderModal"
            data-test="dropdown-menu-folder-creation"
        >
            <i class="fa fa-fw fa-folder-open-o tlp-dropdown-menu-item-icon"></i>
            <translate>New folder</translate>
        </a>
        <a
            v-if="is_item_a_folder(item) && item.user_can_write"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            v-on:click.prevent="showNewDocumentModal"
            data-test="dropdown-menu-file-creation"
        >
            <i class="fa fa-fw fa-plus tlp-dropdown-menu-item-icon"></i>
            <translate>New document</translate>
        </a>
        <create-new-item-version-button
            v-bind:item="item"
            v-bind:button-classes="button_classes"
            v-bind:icon-classes="icon_classes"
            v-if="! is_item_a_folder(item)"
            data-test="docman-dropdown-create-new-version-button"
        />
    </dropdown-menu>
</template>
<script>
import { mapGetters } from "vuex";
import DropdownMenu from "./DropdownMenu.vue";
import CreateNewItemVersionButton from "../ActionsButton/NewItemVersionButton.vue";
import EventBus from "../../../helpers/event-bus.js";

export default {
    components: { CreateNewItemVersionButton, DropdownMenu },
    props: {
        item: Object
    },
    computed: {
        ...mapGetters(["is_item_a_folder"]),
        button_classes() {
            return "tlp-dropdown-menu-item";
        },
        icon_classes() {
            return "fa fa-fw fa-mail-forward tlp-dropdown-menu-item-icon";
        }
    },
    methods: {
        showNewFolderModal() {
            EventBus.$emit("show-new-folder-modal", { detail: { parent: this.item } });
        },
        showNewDocumentModal() {
            EventBus.$emit("show-new-document-modal", { detail: { parent: this.item } });
        }
    }
};
</script>

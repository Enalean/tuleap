<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <a
        class="tlp-dropdown-menu-item document-clipboard-menu-item-paste"
        role="menuitem"
        v-if="can_item_be_pasted"
        v-on:click="pasteItem"
        v-bind:class="{ 'tlp-dropdown-menu-item-disabled': pasting_in_progress }"
        v-bind:disabled="pasting_in_progress"
    >
        <i
            class="fa tlp-dropdown-menu-item-icon document-clipboard-paste-icon-status"
            v-bind:class="[pasting_in_progress ? ' fa-spin fa-circle-o-notch' : 'fa-fw fa-paste']"
        ></i>
        <div class="document-clipboard-item-to-paste-container">
            <translate>Paste</translate>
            <span class="document-clipboard-item-to-paste">
                <i class="fa fa-file-o"></i>
                {{ item_title }}
            </span>
        </div>
    </a>
</template>
<script>
import { mapState, mapGetters } from "vuex";
import EventBus from "../../../helpers/event-bus.js";
import { TYPE_FOLDER, CLIPBOARD_OPERATION_COPY } from "../../../constants.js";
import {
    doesFolderNameAlreadyExist,
    doesDocumentNameAlreadyExist,
} from "../../../helpers/metadata-helpers/check-item-title.js";
import { isItemDestinationIntoItself } from "../../../helpers/clipboard/clipboard-helpers.js";

export default {
    name: "PasteItem",
    props: {
        destination: Object,
    },
    computed: {
        ...mapState(["folder_content"]),
        ...mapState("clipboard", [
            "item_title",
            "pasting_in_progress",
            "operation_type",
            "item_type",
            "item_id",
        ]),
        ...mapGetters(["is_item_a_folder"]),
        can_item_be_pasted() {
            if (
                this.item_title === null ||
                this.operation_type === null ||
                !this.is_item_a_folder(this.destination) ||
                !this.destination.user_can_write
            ) {
                return false;
            }

            if (this.operation_type === CLIPBOARD_OPERATION_COPY) {
                return true;
            }

            if (this.item_type !== TYPE_FOLDER) {
                return !doesDocumentNameAlreadyExist(
                    this.item_title,
                    this.folder_content,
                    this.destination
                );
            }

            return (
                !doesFolderNameAlreadyExist(
                    this.item_title,
                    this.folder_content,
                    this.destination
                ) &&
                !isItemDestinationIntoItself(this.folder_content, this.item_id, this.destination.id)
            );
        },
    },
    methods: {
        async pasteItem() {
            if (!this.pasting_in_progress) {
                EventBus.$emit("hide-action-menu");
            }
            await this.$store.dispatch("clipboard/pasteItem", [
                this.destination,
                this.$store.state.current_folder,
                this.$store,
            ]);
        },
    },
};
</script>

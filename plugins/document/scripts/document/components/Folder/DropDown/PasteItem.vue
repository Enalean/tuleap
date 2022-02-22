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
    <button
        class="tlp-dropdown-menu-item document-clipboard-menu-item-paste"
        type="button"
        role="menuitem"
        v-if="can_item_be_pasted"
        v-on:click="pasteItem"
        v-bind:class="{ 'tlp-dropdown-menu-item-disabled': pasting_in_progress }"
        v-bind:disabled="pasting_in_progress"
        data-shortcut-paste
    >
        <i
            class="fa tlp-dropdown-menu-item-icon document-clipboard-paste-icon-status"
            v-bind:class="[pasting_in_progress ? ' fa-spin fa-circle-o-notch' : 'fa-fw fa-paste']"
        ></i>
        <div class="document-clipboard-item-to-paste-container">
            <translate>Paste</translate>
            <span class="document-clipboard-item-to-paste">
                <i class="far fa-file"></i>
                {{ item_title }}
            </span>
        </div>
    </button>
</template>

<script lang="ts">
import { namespace, State } from "vuex-class";
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Folder, Item } from "../../../type";
import emitter from "../../../helpers/emitter";
import { isFolder } from "../../../helpers/type-check-helper";
import { CLIPBOARD_OPERATION_COPY, TYPE_FOLDER } from "../../../constants";
import {
    doesDocumentNameAlreadyExist,
    doesFolderNameAlreadyExist,
} from "../../../helpers/properties-helpers/check-item-title";
import { isItemDestinationIntoItself } from "../../../helpers/clipboard/clipboard-helpers";

const clipboard = namespace("clipboard");

@Component
export default class PasteItem extends Vue {
    @Prop({ required: true })
    readonly destination!: Folder;

    @State
    readonly folder_content!: Array<Item>;

    @clipboard.State
    readonly item_title!: string | null;

    @clipboard.State
    readonly pasting_in_progress!: boolean;

    @clipboard.State
    readonly operation_type!: string | null;

    @clipboard.State
    readonly item_type!: string | null;

    @clipboard.State
    readonly item_id!: number | null;

    get can_item_be_pasted(): boolean {
        if (
            this.item_title === null ||
            this.operation_type === null ||
            this.item_id === null ||
            !isFolder(this.destination) ||
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
            !doesFolderNameAlreadyExist(this.item_title, this.folder_content, this.destination) &&
            !isItemDestinationIntoItself(this.folder_content, this.item_id, this.destination.id)
        );
    }

    async pasteItem(): Promise<void> {
        if (!this.pasting_in_progress) {
            emitter.emit("hide-action-menu");
        }

        await this.$store.dispatch("clipboard/pasteItem", {
            destination_folder: this.destination,
            current_folder: this.$store.state.current_folder,
            global_context: this.$store,
        });
    }
}
</script>

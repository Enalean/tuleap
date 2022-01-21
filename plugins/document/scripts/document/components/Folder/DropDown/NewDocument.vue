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
    <button
        v-if="is_item_a_folder(item) && item.user_can_write"
        class="tlp-dropdown-menu-item"
        type="button"
        role="menuitem"
        v-on:click.prevent="showNewDocumentModal()"
        data-test="document-new-item"
        data-shortcut-create-document
    >
        <i class="fa fa-fw fa-plus tlp-dropdown-menu-item-icon"></i>
        <translate>New document</translate>
    </button>
</template>

<script lang="ts">
import { isFolder } from "../../../helpers/type-check-helper";
import type { Item } from "../../../type";
import { Component, Prop, Vue } from "vue-property-decorator";
import emitter from "../../../helpers/emitter";

@Component
export default class NewDocument extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    showNewDocumentModal(): void {
        emitter.emit("createItem", { item: this.item });
    }
    is_item_a_folder(item: Item): boolean {
        return isFolder(item);
    }
}
</script>

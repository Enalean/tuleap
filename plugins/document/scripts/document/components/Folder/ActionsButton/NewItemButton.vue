<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
  -
  -->

<template>
    <button
        class="tlp-dropdown-split-button-main"
        type="button"
        v-on:click="showNewDocumentModal()"
        data-test="docman-new-item-button"
        data-shortcut-create-document
    >
        <i class="fa fa-plus tlp-button-icon"></i>
        <translate>New document</translate>
    </button>
</template>

<script lang="ts">
import emitter from "../../../helpers/emitter";
import Component from "vue-class-component";
import { Prop } from "vue-property-decorator";
import type { Item } from "../../../type";
import Vue from "vue";

@Component
export default class NewItemButton extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    showNewDocumentModal(): void {
        emitter.emit("createItem", { item: this.item });
    }
}
</script>

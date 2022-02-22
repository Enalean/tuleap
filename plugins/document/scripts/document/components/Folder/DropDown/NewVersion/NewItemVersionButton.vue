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
        v-if="item.user_can_write"
        v-bind:class="button_classes"
        type="button"
        role="menuitem"
        v-bind:data-tlp-tooltip="cannot_create_new_wiki_version_because_approval_table"
        v-on:click="goToUpdate"
        data-test="document-new-item-version-button"
        data-shortcut-new-version
    >
        <i
            v-if="is_loading_item"
            v-bind:class="iconClasses"
            class="fa fa-spin fa-circle-o-notch"
        ></i>
        <i v-else v-bind:class="iconClasses"></i>
        <translate>Create new version</translate>
    </button>
</template>
<script lang="ts">
import { isLink, isWiki } from "../../../../helpers/type-check-helper";
import Component from "vue-class-component";
import Vue from "vue";
import { Prop } from "vue-property-decorator";
import type { Item } from "../../../../type";
import emitter from "../../../../helpers/emitter";

@Component
export default class NewItemVersionButton extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    @Prop({ required: true })
    readonly buttonClasses!: string;

    @Prop({ required: true })
    readonly iconClasses!: string;

    private is_loading_item = false;

    get is_item_a_wiki_with_approval_table(): boolean {
        return isWiki(this.item) && this.item.approval_table !== null;
    }

    get cannot_create_new_wiki_version_because_approval_table(): string {
        return this.$gettext("This wiki has a approval table, you can't update it.");
    }

    get button_classes(): string {
        let classes = this.buttonClasses;

        if (this.is_item_a_wiki_with_approval_table) {
            classes += " document-new-item-version-button-disabled tlp-tooltip tlp-tooltip-left";
        }

        return classes;
    }

    async goToUpdate(): Promise<void> {
        if (this.is_item_a_wiki_with_approval_table) {
            return;
        }

        if (isLink(this.item)) {
            this.is_loading_item = true;

            const link_with_all_properties = await this.$store.dispatch(
                "loadDocument",
                this.item.id
            );

            emitter.emit("show-create-new-item-version-modal", {
                detail: { current_item: link_with_all_properties },
            });

            this.is_loading_item = false;
            return;
        }

        emitter.emit("show-create-new-item-version-modal", {
            detail: { current_item: this.item },
        });
    }
}
</script>

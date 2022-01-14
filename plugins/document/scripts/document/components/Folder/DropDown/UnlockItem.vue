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
        v-if="can_unlock_document"
        class="tlp-dropdown-menu-item"
        type="button"
        role="menuitem"
        data-test="document-dropdown-menu-unlock-item"
        v-on:click.prevent="unlockDocument"
        data-shortcut-lock-document
    >
        <i class="fa fa-fw fa-unlock tlp-dropdown-menu-item-icon"></i>
        <translate>Unlock</translate>
    </button>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Item } from "../../../type";
import { State } from "vuex-class";

@Component
export default class UnlockItem extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    @State
    private readonly user_id!: number;
    get can_unlock_document(): boolean {
        if (this.item.lock_info === null) {
            return false;
        }

        return this.item.user_can_write;
    }

    async unlockDocument(): Promise<void> {
        await this.$store.dispatch("lock/unlockDocument", this.item);
    }
}
</script>

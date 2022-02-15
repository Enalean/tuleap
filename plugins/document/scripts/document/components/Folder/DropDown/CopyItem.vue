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
        class="tlp-dropdown-menu-item"
        type="button"
        role="menuitem"
        v-on:click="copyItem(item)"
        v-bind:class="{ 'tlp-dropdown-menu-item-disabled': pasting_in_progress }"
        v-bind:disabled="pasting_in_progress"
        data-shortcut-copy
    >
        <i class="fa fa-fw fa-copy tlp-dropdown-menu-item-icon"></i>
        <translate>Copy</translate>
    </button>
</template>

<script lang="ts">
import { namespace } from "vuex-class";
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Item } from "../../../type";
import emitter from "../../../helpers/emitter";

const clipboard = namespace("clipboard");

@Component
export default class CopyItem extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    @clipboard.State
    readonly pasting_in_progress!: boolean;

    copyItem(): void {
        if (!this.pasting_in_progress) {
            emitter.emit("hide-action-menu");
        }
        this.$store.commit("clipboard/copyItem", this.item);
    }
}
</script>

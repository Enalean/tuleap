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
        v-on:click.prevent="showUpdateModal"
        class="tlp-dropdown-menu-item"
        type="button"
        role="menuitem"
        data-test="document-dropdown-update-properties"
        data-shortcut-update-properties
    >
        <i class="fa fa-fw fa-list tlp-dropdown-menu-item-icon"></i>
        <translate>Update properties</translate>
    </button>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Item } from "../../../../type";
import emitter from "../../../../helpers/emitter";

@Component
export default class UpdateProperties extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    showUpdateModal(): void {
        emitter.emit("show-update-item-properties-modal", {
            detail: { current_item: this.item },
        });
    }
}
</script>

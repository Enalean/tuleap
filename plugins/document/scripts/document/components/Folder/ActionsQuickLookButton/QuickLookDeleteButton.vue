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
        v-if="should_display_delete_button"
        type="button"
        class="tlp-button-small tlp-button-outline tlp-button-danger"
        v-on:click="processDeletion"
        data-test="document-quick-look-delete-button"
    >
        <i class="far fa-trash-alt tlp-button-icon"></i>
        <translate>Delete</translate>
    </button>
</template>

<script lang="ts">
import { namespace } from "vuex-class";
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Item } from "../../../type";
import emitter from "../../../helpers/emitter";
import { canDelete } from "../../../helpers/can-delete-helper";

const configuration = namespace("configuration");

@Component
export default class QuickLookDeleteButton extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    @configuration.State
    readonly is_deletion_allowed!: boolean;

    @configuration.State
    readonly forbid_writers_to_delete!: boolean;

    processDeletion(): void {
        emitter.emit("deleteItem", { item: this.item });
    }

    get should_display_delete_button(): boolean {
        return this.is_deletion_allowed && canDelete(this.forbid_writers_to_delete, this.item);
    }
}
</script>

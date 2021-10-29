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
    <div class="tlp-form-element">
        <label class="tlp-label tlp-checkbox" data-test="lock-property-label">
            <input
                type="checkbox"
                name="is_file_locked"
                data-test="lock-property-input-switch"
                v-on:input="$emit('input', $event.target.checked)"
                v-bind:checked="is_checked"
            />
            {{ lock_label }}
        </label>
    </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Item } from "../../../type";

@Component
export default class LockProperty extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    get is_checked(): boolean {
        return this.item.lock_info !== null;
    }
    get lock_label(): string {
        return this.is_checked ? this.$gettext("Keep lock?") : this.$gettext("Lock new version");
    }
}
</script>

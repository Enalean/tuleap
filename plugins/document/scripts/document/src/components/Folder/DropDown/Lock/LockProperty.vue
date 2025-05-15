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
                v-on:input="oninput"
                v-bind:checked="is_checked"
            />
            {{ lock_label }}
        </label>
    </div>
</template>

<script setup lang="ts">
import type { Item } from "../../../../type";
import emitter from "../../../../helpers/emitter";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{ item: Item }>();

const is_checked = computed((): boolean => {
    return props.item.lock_info !== null;
});

const { $gettext } = useGettext();
const lock_label = computed((): string => {
    return is_checked.value ? $gettext("Keep lock?") : $gettext("Lock new version");
});

function oninput($event: Event): void {
    if ($event.target instanceof HTMLInputElement) {
        emitter.emit("update-lock", $event.target.checked);
    }
}
</script>

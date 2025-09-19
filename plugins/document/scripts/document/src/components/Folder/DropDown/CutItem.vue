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
        v-on:click="doCutItem(item)"
        v-bind:class="{ 'tlp-dropdown-menu-item-disabled': clipboard.pasting_in_progress }"
        v-bind:disabled="clipboard.pasting_in_progress"
        v-if="can_cut_item"
        data-shortcut-cut
    >
        <i class="fa-solid fa-fw fa-scissors tlp-dropdown-menu-item-icon"></i>
        {{ $gettext("Cut") }}
    </button>
</template>
<script setup lang="ts">
import type { Item } from "../../../type";
import emitter from "../../../helpers/emitter";
import { useClipboardStore } from "../../../stores/clipboard";
import { computed } from "vue";
import { useStore } from "vuex";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT, USER_ID } from "../../../configuration-keys";

const store = useStore();
const props = defineProps<{ item: Item }>();

const user_id = strictInject(USER_ID);
const project = strictInject(PROJECT);
const clipboard = useClipboardStore(store, project.id, user_id);

const can_cut_item = computed((): boolean => {
    return props.item.user_can_write && props.item.parent_id !== 0;
});

function doCutItem(): void {
    if (!clipboard.pasting_in_progress) {
        emitter.emit("hide-action-menu");
    }
    clipboard.cutItem(props.item);
}
</script>

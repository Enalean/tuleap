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
            class="fa-solid fa-spin fa-circle-notch"
        ></i>
        <i v-else v-bind:class="iconClasses"></i>
        {{ $gettext("Create new version") }}
    </button>
</template>
<script setup lang="ts">
import { isLink, isWiki } from "../../../../helpers/type-check-helper";
import { computed, ref } from "vue";
import type { Item } from "../../../../type";
import emitter from "../../../../helpers/emitter";
import { useGettext } from "vue3-gettext";
import { useActions } from "vuex-composition-helpers";
import type { RootActionsRetrieve } from "../../../../store/actions-retrieve";

const props = defineProps<{ item: Item; buttonClasses: string; iconClasses: string }>();

const { loadDocument } = useActions<Pick<RootActionsRetrieve, "loadDocument">>(["loadDocument"]);

let is_loading_item = ref(false);

const { $gettext } = useGettext();
const cannot_create_new_wiki_version_because_approval_table = $gettext(
    "This wiki has a approval table, you can't update it.",
);

const is_item_a_wiki_with_approval_table = computed((): boolean => {
    return isWiki(props.item) && props.item.approval_table !== null;
});

const button_classes = computed((): string => {
    let classes = props.buttonClasses;

    if (is_item_a_wiki_with_approval_table.value) {
        classes += " document-new-item-version-button-disabled tlp-tooltip tlp-tooltip-left";
    }

    return classes;
});

async function goToUpdate(): Promise<void> {
    if (is_item_a_wiki_with_approval_table.value) {
        return;
    }

    if (isLink(props.item)) {
        is_loading_item.value = true;

        const link_with_all_properties = await loadDocument(props.item.id);

        if (link_with_all_properties) {
            emitter.emit("show-create-new-item-version-modal", {
                detail: { current_item: link_with_all_properties },
            });
        }

        is_loading_item.value = false;
        return;
    }

    emitter.emit("show-create-new-item-version-modal", {
        detail: { current_item: props.item },
    });
}
</script>

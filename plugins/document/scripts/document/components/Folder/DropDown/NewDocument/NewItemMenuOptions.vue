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
    <div class="tlp-dropdown-menu" role="menu" v-bind:aria-label="$gettext('New')">
        <button
            v-on:click.prevent="showNewFolderModal"
            class="tlp-dropdown-menu-item"
            type="button"
            role="menuitem"
            data-test="document-new-folder-creation-button"
            data-shortcut-create-folder
        >
            <i class="fa-fw tlp-dropdown-menu-item-icon" v-bind:class="ICON_FOLDER_ICON"></i>
            {{ $gettext("Folder") }}
        </button>
        <button
            type="button"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-test="document-new-file-creation-button"
            v-on:click.prevent="showNewDocumentModal(TYPE_FILE)"
        >
            <i class="fa-solid fa-fw fa-upload tlp-dropdown-menu-item-icon"></i>
            {{ $gettext("Uploaded file") }}
        </button>
        <button
            type="button"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-test="document-new-embedded-creation-button"
            v-on:click.prevent="showNewDocumentModal(TYPE_EMBEDDED)"
            v-if="embedded_are_allowed"
        >
            <i class="fa-fw tlp-dropdown-menu-item-icon" v-bind:class="ICON_EMBEDDED"></i>
            {{ $gettext("Embedded") }}
        </button>
        <button
            type="button"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-test="document-new-wiki-creation-button"
            v-on:click.prevent="showNewDocumentModal(TYPE_WIKI)"
            v-if="user_can_create_wiki"
        >
            <i class="fa-fw tlp-dropdown-menu-item-icon" v-bind:class="ICON_WIKI"></i>
            {{ $gettext("Wiki page") }}
        </button>
        <template v-for="section in create_new_item_alternatives" v-bind:key="section.title">
            <span class="tlp-dropdown-menu-title document-dropdown-menu-title">{{
                section.title
            }}</span>
            <button
                v-for="alternative in section.alternatives"
                class="tlp-dropdown-menu-item"
                role="menuitem"
                v-bind:key="section.title + alternative.title"
                data-test="alternative"
                v-on:click.prevent="showNewDocumentAlternativeModal(alternative)"
            >
                <i
                    class="fa fa-fw tlp-dropdown-menu-item-icon"
                    v-bind:class="iconForMimeType(alternative.mime_type)"
                ></i>
                {{ alternative.title }}
            </button>
        </template>
        <span
            class="tlp-dropdown-menu-separator"
            role="separator"
            v-if="create_new_item_alternatives.length > 0"
        ></span>
        <button
            type="button"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-test="document-new-link-creation-button"
            v-on:click.prevent="showNewDocumentModal(TYPE_LINK)"
        >
            <i class="fa-fw tlp-dropdown-menu-item-icon" v-bind:class="ICON_LINK"></i>
            {{ $gettext("Link") }}
        </button>
        <button
            type="button"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-test="document-new-empty-creation-button"
            v-on:click.prevent="showNewDocumentModal(TYPE_EMPTY)"
        >
            <i class="fa-fw tlp-dropdown-menu-item-icon" v-bind:class="ICON_EMPTY"></i>
            {{ $gettext("Empty") }}
        </button>
    </div>
</template>

<script setup lang="ts">
import type { Item, ItemType, NewItemAlternative } from "../../../../type";
import {
    ICON_EMBEDDED,
    TYPE_FILE,
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_LINK,
    ICON_EMPTY,
    ICON_LINK,
    TYPE_WIKI,
    ICON_WIKI,
    ICON_FOLDER_ICON,
} from "../../../../constants";
import emitter from "../../../../helpers/emitter";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../store/configuration";
import { iconForMimeType } from "../../../../helpers/icon-for-mime-type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { NEW_ITEMS_ALTERNATIVES } from "../../../../injection-keys";

const { embedded_are_allowed, user_can_create_wiki } = useNamespacedState<
    Pick<ConfigurationState, "embedded_are_allowed" | "user_can_create_wiki">
>("configuration", ["embedded_are_allowed", "user_can_create_wiki"]);

const create_new_item_alternatives = strictInject(NEW_ITEMS_ALTERNATIVES);

const props = defineProps<{ item: Item }>();
function showNewDocumentModal(type: ItemType): void {
    emitter.emit("createItem", { item: props.item, type });
}

function showNewDocumentAlternativeModal(alternative: NewItemAlternative): void {
    emitter.emit("createItem", {
        item: props.item,
        type: TYPE_FILE,
        from_alternative: alternative,
    });
}

function showNewFolderModal(): void {
    emitter.emit("show-new-folder-modal", { detail: { parent: props.item } });
}
</script>

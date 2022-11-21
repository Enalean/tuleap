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
            type="button"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-test="document-new-file-creation-button"
            v-on:click.prevent="showNewVersionModal(TYPE_FILE)"
        >
            <i class="fa-solid fa-fw fa-upload tlp-dropdown-menu-item-icon"></i>
            {{ $gettext("Upload a file") }}
        </button>
        <button
            type="button"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-test="document-new-embedded-creation-button"
            v-on:click.prevent="showNewVersionModal(TYPE_EMBEDDED)"
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
            v-on:click.prevent="showNewVersionModal(TYPE_WIKI)"
            v-if="user_can_create_wiki"
        >
            <i class="fa-fw tlp-dropdown-menu-item-icon" v-bind:class="ICON_WIKI"></i>
            {{ $gettext("Wiki page") }}
        </button>
        <button
            type="button"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-test="document-new-link-creation-button"
            v-on:click.prevent="showNewVersionModal(TYPE_LINK)"
        >
            <i class="fa-fw tlp-dropdown-menu-item-icon" v-bind:class="ICON_LINK"></i>
            {{ $gettext("Link") }}
        </button>
    </div>
</template>

<script setup lang="ts">
import type { Empty, ItemType } from "../../../../type";
import {
    ICON_EMBEDDED,
    TYPE_FILE,
    TYPE_EMBEDDED,
    TYPE_LINK,
    ICON_LINK,
    TYPE_WIKI,
    ICON_WIKI,
} from "../../../../constants";
import emitter from "../../../../helpers/emitter";
import { useState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../store/configuration";

const { embedded_are_allowed, user_can_create_wiki } = useState<
    Pick<ConfigurationState, "embedded_are_allowed" | "user_can_create_wiki">
>("configuration", ["embedded_are_allowed", "user_can_create_wiki"]);

const props = defineProps<{ item: Empty }>();
function showNewVersionModal(type: ItemType): void {
    emitter.emit("show-create-new-version-modal-for-empty", {
        item: props.item,
        type,
    });
}
</script>

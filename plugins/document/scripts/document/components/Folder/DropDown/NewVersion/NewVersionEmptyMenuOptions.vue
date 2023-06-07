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
    <div class="tlp-dropdown-menu" role="menu" v-bind:aria-label="$gettext('Convert to…')">
        <button
            type="button"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-test="document-new-file-creation-button"
            v-on:click.prevent="showNewVersionModal(TYPE_FILE)"
        >
            <i class="fa-solid fa-fw fa-upload tlp-dropdown-menu-item-icon"></i>
            {{ $gettext("Uploaded file") }}
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
                v-on:click.prevent="convertEmptyDocument(alternative)"
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
            v-on:click.prevent="showNewVersionModal(TYPE_LINK)"
        >
            <i class="fa-fw tlp-dropdown-menu-item-icon" v-bind:class="ICON_LINK"></i>
            {{ $gettext("Link") }}
        </button>
    </div>
</template>

<script setup lang="ts">
import type {
    Empty,
    ItemType,
    NewItemAlternative,
    NewItemAlternativeArray,
} from "../../../../type";
import {
    ICON_EMBEDDED,
    TYPE_FILE,
    TYPE_EMBEDDED,
    TYPE_LINK,
    ICON_LINK,
    TYPE_WIKI,
    ICON_WIKI,
} from "../../../../constants";
import type { ItemHasJustBeenUpdatedEvent } from "../../../../helpers/emitter";
import emitter from "../../../../helpers/emitter";
import { useActions, useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../store/configuration";
import { inject } from "vue";
import { iconForMimeType } from "../../../../helpers/icon-for-mime-type";
import { getEmptyOfficeFileFromMimeType } from "../../../../helpers/office/get-empty-office-file";
import type {
    RootActionsUpdate,
    NewVersionFromEmptyInformation,
} from "../../../../store/actions-update";
import { isFile } from "../../../../helpers/type-check-helper";

const { createNewVersionFromEmpty } = useActions<
    Pick<RootActionsUpdate, "createNewVersionFromEmpty">
>(["createNewVersionFromEmpty"]);
const { embedded_are_allowed, user_can_create_wiki, user_locale } = useNamespacedState<
    Pick<ConfigurationState, "embedded_are_allowed" | "user_can_create_wiki" | "user_locale">
>("configuration", ["embedded_are_allowed", "user_can_create_wiki", "user_locale"]);

const create_new_item_alternatives = inject<NewItemAlternativeArray>(
    "create_new_item_alternatives",
    []
);

const props = defineProps<{ item: Empty; location?: Location }>();
const location = props.location || window.location;

function showNewVersionModal(type: ItemType): void {
    emitter.emit("show-create-new-version-modal-for-empty", {
        item: props.item,
        type,
    });
}

async function convertEmptyDocument(alternative: NewItemAlternative): Promise<void> {
    const office_file = await getEmptyOfficeFileFromMimeType(
        user_locale.value,
        alternative.mime_type
    );
    const file = new File([office_file.file], props.item.title + "." + office_file.extension, {
        type: alternative.mime_type,
    });

    const new_version: NewVersionFromEmptyInformation = {
        link_properties: {
            link_url: "",
        },
        embedded_properties: {
            content: "",
        },
        file_properties: {
            file,
        },
    };

    await createNewVersionFromEmpty([TYPE_FILE, props.item, new_version]);

    const redirectToEditor = (event: ItemHasJustBeenUpdatedEvent): void => {
        const item = event.item;
        if (item.id === props.item.id) {
            emitter.off("item-has-just-been-updated", redirectToEditor);
            if (isFile(item) && item.file_properties && item.file_properties.open_href) {
                location.href = item.file_properties.open_href;
            }
        }
    };
    emitter.on("item-has-just-been-updated", redirectToEditor);
}
</script>

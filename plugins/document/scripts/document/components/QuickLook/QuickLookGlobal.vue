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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->
<template>
    <section class="tlp-pane-container">
        <div class="tlp-pane-header document-quick-look-header">
            <h2
                class="tlp-pane-title document-quick-look-title"
                v-bind:title="currently_previewed_item.title"
            >
                <i class="tlp-pane-title-icon" v-bind:class="icon_class"></i>
                {{ currently_previewed_item.title }}
            </h2>
            <div class="document-quick-look-close-button" v-on:click="closeQuickLookEvent">×</div>
        </div>
        <section class="tlp-pane-section">
            <quick-look-item-is-locked-message v-if="currently_previewed_item.lock_info !== null" />
            <quick-look-document-preview
                v-bind:icon-class="icon_class"
                v-bind:item="currently_previewed_item"
            />
            <component
                v-if="quick_look_component_action !== null"
                v-bind:is="quick_look_component_action"
                v-bind:item="currently_previewed_item"
            />
        </section>
        <quick-look-document-properties v-bind:item="currently_previewed_item" />
        <section class="tlp-pane-section" v-if="currently_previewed_item.description">
            <div class="tlp-property">
                <label class="tlp-label" for="item-description">{{
                    $gettext("Description")
                }}</label>
                <p id="item-description" v-dompurify-html="get_description"></p>
            </div>
        </section>
    </section>
</template>

<script setup lang="ts">
import {
    ICON_EMBEDDED,
    ICON_EMPTY,
    ICON_FOLDER_ICON,
    ICON_LINK,
    ICON_WIKI,
    TYPE_EMBEDDED,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
    TYPE_EMPTY,
} from "../../constants";
import { iconForMimeType } from "../../helpers/icon-for-mime-type";
import QuickLookDocumentProperties from "./QuickLookDocumentProperties.vue";
import QuickLookDocumentPreview from "./QuickLookDocumentPreview.vue";
import QuickLookItemIsLockedMessage from "./QuickLookItemIsLockedMessage.vue";
import { useState } from "vuex-composition-helpers";
import type { State } from "../../type";
import { computed, defineAsyncComponent } from "vue";
import { isFile } from "../../helpers/type-check-helper";

const { currently_previewed_item } = useState<Pick<State, "currently_previewed_item">>([
    "currently_previewed_item",
]);

const emit = defineEmits<{
    (e: "close-quick-look-event"): void;
}>();

const get_description = computed((): string => {
    return currently_previewed_item.value
        ? currently_previewed_item.value.post_processed_description
        : "";
});

const icon_class = computed((): string => {
    const item = currently_previewed_item.value;
    if (!item) {
        return ICON_EMPTY;
    }
    switch (item.type) {
        case TYPE_FOLDER:
            return ICON_FOLDER_ICON;
        case TYPE_LINK:
            return ICON_LINK;
        case TYPE_WIKI:
            return ICON_WIKI;
        case TYPE_EMBEDDED:
            return ICON_EMBEDDED;
        case TYPE_FILE:
            if (!isFile(item) || !item.file_properties) {
                return ICON_EMPTY;
            }
            return iconForMimeType(item.file_properties.file_type);
        default:
            return ICON_EMPTY;
    }
});

const quick_look_component_action = computed(() => {
    if (!currently_previewed_item.value) {
        return null;
    }
    switch (currently_previewed_item.value.type) {
        case TYPE_FILE:
            return defineAsyncComponent(
                () => import(/* webpackChunkName: "quick-look-file" */ `./QuickLookFile.vue`),
            );
        case TYPE_WIKI:
            return defineAsyncComponent(
                () => import(/* webpackChunkName: "quick-look-wiki" */ `./QuickLookWiki.vue`),
            );
        case TYPE_FOLDER:
            return defineAsyncComponent(
                () => import(/* webpackChunkName: "quick-look-folder" */ `./QuickLookFolder.vue`),
            );
        case TYPE_LINK:
            return defineAsyncComponent(
                () => import(/* webpackChunkName: "quick-look-link" */ `./QuickLookLink.vue`),
            );
        case TYPE_EMPTY:
            return defineAsyncComponent(
                () =>
                    import(
                        /* webpackChunkName: "quick-look-empty-embedded" */ `./QuickLookEmpty.vue`
                    ),
            );
        case TYPE_EMBEDDED:
            return defineAsyncComponent(
                () =>
                    import(
                        /* webpackChunkName: "quick-look-empty-embedded" */ `./QuickLookEmbedded.vue`
                    ),
            );
        default:
            return null;
    }
});

function closeQuickLookEvent(): void {
    emit("close-quick-look-event");
}

defineExpose({
    get_description,
});
</script>

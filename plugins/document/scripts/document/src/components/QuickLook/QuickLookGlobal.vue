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
            <quick-look-item-is-locked-message
                v-if="currently_previewed_item.lock_info !== null"
                v-bind:lock_info="currently_previewed_item.lock_info"
            />
            <quick-look-obsolescence-warning v-bind:item="currently_previewed_item" />
            <quick-look-document-preview
                v-bind:icon-class="icon_class"
                v-bind:item="currently_previewed_item"
            />

            <quick-look-file
                v-if="isFile(currently_previewed_item)"
                v-bind:item="currently_previewed_item"
            />
            <quick-look-wiki
                v-if="isWiki(currently_previewed_item)"
                v-bind:item="currently_previewed_item"
            />
            <quick-look-folder
                v-if="isFolder(currently_previewed_item)"
                v-bind:item="currently_previewed_item"
            />
            <quick-look-link
                v-if="isLink(currently_previewed_item)"
                v-bind:item="currently_previewed_item"
            />
            <quick-look-empty
                v-if="isEmpty(currently_previewed_item)"
                v-bind:item="currently_previewed_item"
            />
            <quick-look-embedded
                v-if="isEmbedded(currently_previewed_item)"
                v-bind:item="currently_previewed_item"
            />
            <quick-look-other-type
                v-if="isOtherType(currently_previewed_item)"
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
import QuickLookDocumentProperties from "./QuickLookDocumentProperties.vue";
import QuickLookDocumentPreview from "./QuickLookDocumentPreview.vue";
import QuickLookItemIsLockedMessage from "./QuickLookItemIsLockedMessage.vue";
import type { Item } from "../../type";
import { computed, defineAsyncComponent } from "vue";
import {
    isEmbedded,
    isEmpty,
    isFile,
    isFolder,
    isLink,
    isOtherType,
    isWiki,
} from "../../helpers/type-check-helper";
import { useGettext } from "vue3-gettext";
import QuickLookObsolescenceWarning from "./QuickLookObsolescenceWarning.vue";

const QuickLookFile = defineAsyncComponent(() => import("./QuickLookFile.vue"));
const QuickLookWiki = defineAsyncComponent(() => import("./QuickLookWiki.vue"));
const QuickLookFolder = defineAsyncComponent(() => import("./QuickLookFolder.vue"));
const QuickLookLink = defineAsyncComponent(() => import("./QuickLookLink.vue"));
const QuickLookEmpty = defineAsyncComponent(() => import("./QuickLookEmpty.vue"));
const QuickLookEmbedded = defineAsyncComponent(() => import("./QuickLookEmbedded.vue"));
const QuickLookOtherType = defineAsyncComponent(() => import("./QuickLookOtherType.vue"));

const { $gettext } = useGettext();

const props = defineProps<{ currently_previewed_item: Item }>();

const emit = defineEmits<{
    (e: "close-quick-look-event"): void;
}>();

const get_description = computed((): string => {
    return props.currently_previewed_item
        ? props.currently_previewed_item.post_processed_description
        : "";
});

const icon_class = computed((): string => {
    const item = props.currently_previewed_item;
    if (!item) {
        return "";
    }
    return item.item_icon;
});

function closeQuickLookEvent(): void {
    emit("close-quick-look-event");
}

defineExpose({
    get_description,
});
</script>

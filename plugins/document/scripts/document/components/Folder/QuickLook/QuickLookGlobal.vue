<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
            <h2 class="tlp-pane-title document-quick-look-title" v-bind:title="item.title">
                <i class="tlp-pane-title-icon fa" v-bind:class="icon_class"></i>
                {{ item.title }}
            </h2>
            <div class="document-quick-look-close-button" v-on:click="closeQuickLookEvent">
                ×
            </div>
        </div>
        <section class="tlp-pane-section">
            <quick-look-document-preview v-bind:icon-class="icon_class" v-bind:item="item"/>
            <component
                v-bind:is="quick_look_component_action"
                v-bind:item="item"
            />
        </section>
        <quick-look-document-metadata v-bind:item="item"/>
        <section class="tlp-pane-section" v-if="item.description">
            <div class="tlp-property">
                <label class="tlp-label" for="item-description" v-translate>
                    Description
                </label>
                <p id="item-description">
                    {{ item.description }}
                </p>
            </div>
        </section>
    </section>
</template>

<script>
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
    TYPE_EMPTY
} from "../../../constants.js";
import { iconForMimeType } from "../../../helpers/icon-for-mime-type.js";
import QuickLookDocumentMetadata from "./QuickLookDocumentMetadata.vue";
import QuickLookDocumentPreview from "./QuickLookDocumentPreview.vue";
import DropdownButton from "../ActionsDropDown/DropdownButton.vue";
import DropdownMenu from "../ActionsDropDown/DropdownMenu.vue";

export default {
    name: "QuicklookGlobal",
    components: {
        QuickLookDocumentPreview,
        QuickLookDocumentMetadata,
        DropdownButton,
        DropdownMenu
    },
    props: {
        item: Object
    },
    computed: {
        icon_class() {
            switch (this.item.type) {
                case TYPE_FOLDER:
                    return ICON_FOLDER_ICON;
                case TYPE_LINK:
                    return ICON_LINK;
                case TYPE_WIKI:
                    return ICON_WIKI;
                case TYPE_EMBEDDED:
                    return ICON_EMBEDDED;
                case TYPE_FILE:
                    if (!this.item.file_properties) {
                        return ICON_EMPTY;
                    }
                    return iconForMimeType(this.item.file_properties.file_type);
                default:
                    return ICON_EMPTY;
            }
        },
        quick_look_component_action() {
            let name = "";
            switch (this.item.type) {
                case TYPE_FILE:
                    name = "File";
                    break;
                case TYPE_WIKI:
                    name = "Wiki";
                    break;
                case TYPE_FOLDER:
                    name = "Folder";
                    break;
                case TYPE_LINK:
                    name = "Link";
                    break;
                case TYPE_EMPTY:
                case TYPE_EMBEDDED:
                    name = "EmptyOrEmbedded";
                    break;
                default:
                    return;
            }
            return () => import(/* webpackChunkName: "quick-look-" */ `./QuickLook${name}.vue`);
        }
    },
    methods: {
        closeQuickLookEvent() {
            this.$emit("closeQuickLookEvent", false);
        }
    }
};
</script>

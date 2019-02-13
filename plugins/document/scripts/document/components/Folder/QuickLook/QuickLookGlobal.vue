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
    <div class="tlp-pane-container">
        <div class="tlp-pane-header document-quick-look-header">
            <h2 class="tlp-pane-title document-quick-look-title" v-bind:title="item.title">
                <i class="tlp-pane-title-icon fa" v-bind:class="icon_class"></i>
                {{ item.title }}
            </h2>
            <div class="document-quick-look-close-button" v-on:click="closeQuickLookEvent">
                ×
            </div>
        </div>
        <div class="tlp-pane-section">
            <div class="document-quick-look-icon">
                <i class="fa " v-bind:class="icon_class"></i>
            </div>
            <component
                v-bind:is="quick_look_component_action"
                v-bind:item="item"
            />
        </div>
    </div>
</template>

<script>
import QuickLookFile from "./QuickLookFile.vue";
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
    TYPE_WIKI
} from "../../../constants.js";
import { iconForMimeType } from "../../../helpers/icon-for-mime-type.js";

export default {
    name: "QuicklookGlobal",
    components: { QuickLookFile },
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
                case TYPE_FOLDER:
                    name = "Folder";
                    break;
                case TYPE_LINK:
                case TYPE_WIKI:
                case TYPE_EMBEDDED:
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

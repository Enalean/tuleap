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
    <div
        class="tlp-dropdown-menu document-dropdown-menu"
        v-bind:class="{
            'tlp-dropdown-menu-large tlp-dropdown-menu-top': isInFolderEmptyState,
            'tlp-dropdown-menu-right': isInQuickLookMode,
        }"
        role="menu"
    >
        <slot name="new-folder-secondary-action" />

        <slot name="new-item-version" />
        <slot name="new-document" />

        <slot name="lock-item" />
        <slot name="unlock-item" />

        <slot name="display-item-title-separator" />
        <slot name="display-item-title" />

        <slot name="update-properties" />

        <a
            v-bind:href="getUrlForPane(NOTIFS_PANE_NAME)"
            class="tlp-dropdown-menu-item"
            role="menuitem"
        >
            <i class="fa fa-fw fa-bell-o tlp-dropdown-menu-item-icon"></i>
            <span v-translate>
                Notifications
            </span>
        </a>
        <a
            v-bind:href="getUrlForPane(HISTORY_PANE_NAME)"
            class="tlp-dropdown-menu-item"
            role="menuitem"
        >
            <i class="fa fa-fw fa-history tlp-dropdown-menu-item-icon"></i>
            <span v-translate>
                History
            </span>
        </a>
        <slot name="update-permissions" />
        <a
            v-if="!is_item_an_empty_document(item)"
            v-bind:href="getUrlForPane(APPROVAL_TABLES_PANE_NAME)"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-test="document-dropdown-approval-tables"
        >
            <i class="fa fa-fw fa-check-square-o tlp-dropdown-menu-item-icon"></i>
            <span v-translate>
                Approval tables
            </span>
        </a>

        <drop-down-separator />

        <cut-item v-bind:item="item" />
        <copy-item v-bind:item="item" />
        <paste-item v-bind:destination="item" />

        <slot name="delete-item-separator" v-if="is_deletion_allowed" />
        <slot name="delete-item" />
    </div>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import CutItem from "./CutItem.vue";
import CopyItem from "./CopyItem.vue";
import PasteItem from "./PasteItem.vue";
import DropDownSeparator from "./DropDownSeparator.vue";

export default {
    name: "DropDownMenu",
    components: {
        DropDownSeparator,
        CutItem,
        CopyItem,
        PasteItem,
    },
    props: {
        isInFolderEmptyState: Boolean,
        isInQuickLookMode: Boolean,
        hideItemTitle: Boolean,
        hideDetailsEntry: Boolean,
        item: Object,
    },
    data() {
        return {
            NOTIFS_PANE_NAME: "notifications",
            HISTORY_PANE_NAME: "history",
            APPROVAL_TABLES_PANE_NAME: "approval",
        };
    },
    computed: {
        ...mapState(["project_id", "is_deletion_allowed"]),
        ...mapGetters(["is_item_an_empty_document"]),
    },
    methods: {
        getUrlForPane(pane_name) {
            return `/plugins/docman/?group_id=${this.project_id}&id=${this.item.id}&action=details&section=${pane_name}`;
        },
    },
};
</script>

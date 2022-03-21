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
    <fragment>
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
            data-shortcut-notifications
        >
            <i class="far fa-fw fa-bell tlp-dropdown-menu-item-icon"></i>
            <span v-translate>Notifications</span>
        </a>
        <a
            v-bind:href="getUrlForPane(HISTORY_PANE_NAME)"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-shortcut-history
            data-test="document-history"
        >
            <i class="fa fa-fw fa-history tlp-dropdown-menu-item-icon"></i>
            <span v-translate>History</span>
        </a>
        <slot name="update-permissions" />
        <a
            v-if="!is_item_an_empty_document(item)"
            v-bind:href="getUrlForPane(APPROVAL_TABLES_PANE_NAME)"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-test="document-dropdown-approval-tables"
            data-shortcut-approval-tables
        >
            <i class="far fa-fw fa-check-square tlp-dropdown-menu-item-icon"></i>
            <span v-translate>Approval tables</span>
        </a>

        <drop-down-separator />

        <cut-item v-bind:item="item" />
        <copy-item v-bind:item="item" />
        <paste-item v-bind:destination="item" />

        <template v-if="is_item_a_folder(item)">
            <drop-down-separator />
            <download-folder-as-zip
                data-test="document-dropdown-download-folder-as-zip"
                v-bind:item="item"
            />
        </template>

        <slot name="delete-item-separator" v-if="is_deletion_allowed" />
        <slot name="delete-item" />
    </fragment>
</template>
<script lang="ts">
import { Fragment } from "vue-frag";
import CutItem from "./CutItem.vue";
import CopyItem from "./CopyItem.vue";
import PasteItem from "./PasteItem.vue";
import DropDownSeparator from "./DropDownSeparator.vue";
import DownloadFolderAsZip from "./DownloadFolderAsZip/DownloadFolderAsZip.vue";
import { isFolder, isEmpty } from "../../../helpers/type-check-helper";
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Item } from "../../../type";
import { namespace } from "vuex-class";

const configuration = namespace("configuration");

@Component({
    components: {
        Fragment,
        DropDownSeparator,
        CutItem,
        CopyItem,
        PasteItem,
        DownloadFolderAsZip,
    },
})
export default class DropDownMenu extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    @configuration.State
    readonly project_id!: number;

    @configuration.State
    readonly is_deletion_allowed!: boolean;

    private NOTIFS_PANE_NAME = "notifications";
    private HISTORY_PANE_NAME = "history";
    private APPROVAL_TABLES_PANE_NAME = "approval";

    getUrlForPane(pane_name: string): string {
        return `/plugins/docman/?group_id=${this.project_id}&id=${this.item.id}&action=details&section=${pane_name}`;
    }
    is_item_a_folder(item: Item): boolean {
        return isFolder(item);
    }
    is_item_an_empty_document(item: Item): boolean {
        return isEmpty(item);
    }
}
</script>

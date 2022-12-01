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
        <slot name="download" />

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
            <i class="fa-regular fa-fw fa-bell tlp-dropdown-menu-item-icon"></i>
            <span v-translate>Notifications</span>
        </a>
        <router-link
            v-if="should_display_versions_link"
            v-bind:to="{ name: 'versions', params: { item_id: item.id } }"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-shortcut-history
            data-test="document-versions"
        >
            <i
                class="fa-solid fa-fw fa-clock-rotate-left tlp-dropdown-menu-item-icon"
                aria-hidden="true"
            ></i>
            <span v-translate>Versions</span>
        </router-link>
        <a
            v-if="!should_display_history_in_document"
            v-bind:href="getUrlForPane(HISTORY_PANE_NAME)"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-shortcut-history
            data-test="document-history"
        >
            <i class="fa-solid fa-fw fa-clock-rotate-left tlp-dropdown-menu-item-icon"></i>
            <span v-translate>History</span>
        </a>
        <router-link
            v-else
            v-bind:to="{ name: 'history', params: { item_id: item.id } }"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-shortcut-history
            data-test="document-history"
        >
            <i class="fa-solid fa-fw fa-list tlp-dropdown-menu-item-icon" aria-hidden="true"></i>
            <span v-translate>Logs</span>
        </router-link>

        <slot name="update-permissions" />
        <a
            v-if="!is_item_an_empty_document"
            v-bind:href="getUrlForPane(APPROVAL_TABLES_PANE_NAME)"
            class="tlp-dropdown-menu-item"
            role="menuitem"
            data-test="document-dropdown-approval-tables"
            data-shortcut-approval-tables
        >
            <i class="fa-regular fa-fw fa-square-check tlp-dropdown-menu-item-icon"></i>
            <span v-translate>Approval tables</span>
        </a>

        <drop-down-separator />

        <cut-item v-bind:item="item" />
        <copy-item v-bind:item="item" />
        <paste-item v-bind:destination="item" />

        <template v-if="is_item_a_folder">
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
<script setup lang="ts">
import { Fragment } from "vue-frag";
import CutItem from "./CutItem.vue";
import CopyItem from "./CopyItem.vue";
import PasteItem from "./PasteItem.vue";
import DropDownSeparator from "./DropDownSeparator.vue";
import DownloadFolderAsZip from "./DownloadFolderAsZip/DownloadFolderAsZip.vue";
import { isFolder, isEmpty, isFile, isLink, isEmbedded } from "../../../helpers/type-check-helper";
import type { Item } from "../../../type";
import { useState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";
import { computed, inject } from "vue";

const props = defineProps<{ item: Item }>();

const { project_id, is_deletion_allowed } = useState<
    Pick<ConfigurationState, "project_id" | "is_deletion_allowed">
>("configuration", ["project_id", "is_deletion_allowed"]);

const NOTIFS_PANE_NAME = "notifications";
const HISTORY_PANE_NAME = "history";
const APPROVAL_TABLES_PANE_NAME = "approval";

const is_item_a_folder = computed((): boolean => isFolder(props.item));

const is_item_an_empty_document = computed((): boolean => isEmpty(props.item));

const should_display_history_in_document = inject("should_display_history_in_document", false);

const should_display_versions_link = computed(
    (): boolean =>
        should_display_history_in_document &&
        (isFile(props.item) || isLink(props.item) || isEmbedded(props.item))
);

function getUrlForPane(pane_name: string): string {
    return `/plugins/docman/?group_id=${project_id.value}&id=${props.item.id}&action=details&section=${pane_name}`;
}
</script>

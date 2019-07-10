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
    <div class="tlp-dropdown-menu document-dropdown-menu"
         v-bind:class="{
             'tlp-dropdown-menu-large tlp-dropdown-menu-top': isInFolderEmptyState,
             'tlp-dropdown-menu-right': isInQuickLookMode
         }"
         role="menu"
    >
        <slot></slot>
        <template v-if="item.user_can_write && is_lock_supported_for_item">
            <lock-item v-bind:item="item" data-test="dropdown-menu-lock-item"/>
            <unlock-item v-bind:item="item" data-test="dropdown-menu-unlock-item"/>
            <span class="tlp-dropdown-menu-separator" role="separator" v-if="hideItemTitle" data-test="docman-lock-separator"></span>
        </template>
        <span v-if="! hideItemTitle" class="tlp-dropdown-menu-title document-dropdown-menu-title" role="menuitem">
            {{ item.title }}
        </span>
        <a v-if="! hideDetailsEntry"
           v-on:click.prevent="showUpdateModal"
           class="tlp-dropdown-menu-item"
           role="menuitem"
           data-test="docman-dropdown-details"
        >
            <i class="fa fa-fw fa-list tlp-dropdown-menu-item-icon"></i>
            <span>
                {{ properties_label }}
            </span>
        </a>
        <a v-bind:href="getUrlForPane(NOTIFS_PANE_NAME)" class="tlp-dropdown-menu-item" role="menuitem">
            <i class="fa fa-fw fa-bell-o tlp-dropdown-menu-item-icon"></i>
            <span v-translate>
                Notifications
            </span>
        </a>
        <a v-bind:href="getUrlForPane(HISTORY_PANE_NAME)" class="tlp-dropdown-menu-item" role="menuitem">
            <i class="fa fa-fw fa-history tlp-dropdown-menu-item-icon"></i>
            <span v-translate>
                History
            </span>
        </a>
        <a v-if="item.can_user_manage"
           v-bind:href="getUrlForPane(PERMISSIONS_PANE_NAME)"
           class="tlp-dropdown-menu-item"
           role="menuitem"
           data-test="docman-dropdown-permissions"
        >
            <i class="fa fa-fw fa-lock tlp-dropdown-menu-item-icon"></i>
            <span v-translate>
                Permissions
            </span>
        </a>
        <a v-if="! is_item_an_empty_document(item)"
           v-bind:href="getUrlForPane(APPROVAL_TABLES_PANE_NAME)"
           class="tlp-dropdown-menu-item"
           role="menuitem"
           data-test="docman-dropdown-approval-tables"
        >
            <i class="fa fa-fw fa-check-square-o tlp-dropdown-menu-item-icon"></i>
            <span v-translate>
                Approval tables
            </span>
        </a>
        <template v-if="can_user_delete_item">
            <span class="tlp-dropdown-menu-separator" role="separator"></span>
            <quick-look-delete-button
                v-bind:is-in-dropdown="true"
                v-bind:item="item"
                role="menuitem"
                data-test="docman-dropdown-delete"
            />
        </template>
    </div>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import { TYPE_EMBEDDED, TYPE_EMPTY, TYPE_FILE, TYPE_LINK, TYPE_WIKI } from "../../../constants.js";
import { redirectToUrl } from "../../../helpers/location-helper.js";
import QuickLookDeleteButton from "../ActionsQuickLookButton/QuickLookDeleteButton.vue";
import LockItem from "./LockItem.vue";
import UnlockItem from "./UnlockItem.vue";

export default {
    name: "DropdownMenu",
    components: { UnlockItem, LockItem, QuickLookDeleteButton },
    props: {
        isInFolderEmptyState: Boolean,
        isInQuickLookMode: Boolean,
        hideItemTitle: Boolean,
        hideDetailsEntry: Boolean,
        item: Object
    },
    data() {
        return {
            DETAILS_PANE_NAME: "details",
            NOTIFS_PANE_NAME: "notifications",
            HISTORY_PANE_NAME: "history",
            PERMISSIONS_PANE_NAME: "permissions",
            APPROVAL_TABLES_PANE_NAME: "approval"
        };
    },
    computed: {
        ...mapState(["project_id"]),
        ...mapGetters(["is_item_an_empty_document"]),
        can_user_delete_item() {
            return this.item.user_can_write && this.item.parent_id;
        },
        is_lock_supported_for_item() {
            return (
                this.item.type === TYPE_FILE ||
                this.item.type === TYPE_EMBEDDED ||
                this.item.type === TYPE_WIKI ||
                this.item.type === TYPE_LINK ||
                this.item.type === TYPE_EMPTY
            );
        },
        properties_label() {
            if (this.item.type === TYPE_FILE) {
                return this.$gettext("Update properties");
            }
            return this.$gettext("Properties");
        }
    },
    methods: {
        getUrlForPane(pane_name) {
            return `/plugins/docman/?group_id=${this.project_id}&id=${
                this.item.id
            }&action=details&section=${pane_name}`;
        },
        showUpdateModal() {
            if (this.item.type !== TYPE_FILE) {
                const details_url = this.getUrlForPane(this.DETAILS_PANE_NAME);
                redirectToUrl(details_url);
                return;
            }

            document.dispatchEvent(
                new CustomEvent("show-update-item-metadata-modal", {
                    detail: { current_item: this.item }
                })
            );
        }
    }
};
</script>

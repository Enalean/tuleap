<!--
  - Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
  -
  -->

<template>
    <div class="embedded-document-container">
        <div class="document-header tlp-framed-horizontally">
            <document-title-lock-info
                v-bind:item="currently_previewed_item"
                v-bind:is-displaying-in-header="true"
            />

            <h1 class="document-header-title">{{ currently_previewed_item.title }}</h1>

            <actions-header v-bind:item="currently_previewed_item" />

            <approval-badge
                v-bind:item="currently_previewed_item"
                v-bind:is-in-folder-content-row="false"
            />

            <embedded-file-edition-switcher v-bind:is-in-large-view="is_embedded_in_large_view" />
        </div>

        <section
            class="tlp-pane embedded-document"
            v-bind:class="{ narrow: !is_embedded_in_large_view }"
            data-test="display-embedded-content"
        >
            <div class="tlp-pane-container">
                <section class="tlp-pane-section" v-dompurify-html="embedded_content"></section>
            </div>
        </section>

        <create-new-embedded-file-version-modal
            v-if="is_modal_shown"
            v-bind:item="currently_previewed_item"
            v-on:hidden="hideModal()"
        />
        <confirm-deletion-modal
            v-if="show_confirm_deletion_modal"
            v-bind:item="currently_previewed_item"
            v-bind:should-redirect-to-parent-after-deletion="true"
            v-on:delete-modal-closed="hideDeleteItemModal"
        />

        <update-properties-modal
            v-if="show_update_properties_modal"
            v-bind:item="currently_previewed_item"
            v-on:update-properties-modal-closed="hideUpdatePropertiesModal"
        />
        <permissions-update-modal
            v-if="show_update_permissions_modal"
            v-bind:item="currently_previewed_item"
        />
    </div>
</template>

<script>
import ActionsHeader from "./ActionsHeader.vue";
import DocumentTitleLockInfo from "../Folder/LockInfo/DocumentTitleLockInfo.vue";
import ApprovalBadge from "../Folder/ApprovalTables/ApprovalBadge.vue";
import EmbeddedFileEditionSwitcher from "./EmbeddedFileEditionSwitcher.vue";
import UpdatePropertiesModal from "../Folder/DropDown/UpdateProperties/UpdatePropertiesModal.vue";
import { mapState } from "vuex";
import emitter from "../../helpers/emitter";

export default {
    name: "DisplayEmbeddedContent",
    components: {
        UpdatePropertiesModal,
        EmbeddedFileEditionSwitcher,
        ApprovalBadge,
        DocumentTitleLockInfo,
        ActionsHeader,
        "permissions-update-modal": () =>
            import(
                /* webpackChunkName: "document-permissions-update-modal" */ "../Folder/Permissions/PermissionsUpdateModal.vue"
            ),
        "create-new-embedded-file-version-modal": () =>
            import(
                /* webpackChunkName: "document-new-embedded-file-version-modal" */ "../Folder/DropDown/NewVersion/CreateNewVersionEmbeddedFileModal.vue"
            ),
        "confirm-deletion-modal": () =>
            import(
                /* webpackChunkName: "document-confirm-item-deletion-modal" */ "../Folder/DropDown/Delete/ModalConfirmDeletion.vue"
            ),
        "update-properties-modal": () =>
            import(
                /* webpackChunkName: "update-properties-modal" */ "../Folder/DropDown/UpdateProperties/UpdatePropertiesModal.vue"
            ),
    },
    data() {
        return {
            is_modal_shown: false,
            show_confirm_deletion_modal: false,
            show_update_properties_modal: false,
            show_update_permissions_modal: false,
            is_in_large_view: false,
        };
    },
    computed: {
        ...mapState(["currently_previewed_item"]),
        ...mapState("preferencies", ["is_embedded_in_large_view"]),
        embedded_content() {
            if (!this.currently_previewed_item.embedded_file_properties) {
                return "";
            }

            return this.currently_previewed_item.embedded_file_properties.content;
        },
    },
    created() {
        emitter.on("deleteItem", this.showDeleteItemModal);
        emitter.on("show-create-new-item-version-modal", this.showCreateNewItemVersionModal);
        emitter.on("show-update-item-properties-modal", this.showUpdatePropertiesModal);
        emitter.on("show-update-permissions-modal", this.showUpdateItemPermissionsModal);
    },
    beforeDestroy() {
        emitter.off("deleteItem", this.showDeleteItemModal);
        emitter.off("show-create-new-item-version-modal", this.showCreateNewItemVersionModal);
        emitter.off("show-update-item-properties-modal", this.showUpdatePropertiesModal);
        emitter.off("show-update-permissions-modal", this.showUpdateItemPermissionsModal);
    },
    methods: {
        showCreateNewItemVersionModal() {
            this.is_modal_shown = true;
        },
        hideModal() {
            this.is_modal_shown = false;
        },
        showUpdatePropertiesModal() {
            this.show_update_properties_modal = true;
        },
        hideUpdatePropertiesModal() {
            this.show_update_properties_modal = false;
        },
        showUpdateItemPermissionsModal() {
            this.show_update_permissions_modal = true;
        },
        showDeleteItemModal() {
            this.show_confirm_deletion_modal = true;
        },
        hideDeleteItemModal() {
            this.show_confirm_deletion_modal = false;
        },
    },
};
</script>

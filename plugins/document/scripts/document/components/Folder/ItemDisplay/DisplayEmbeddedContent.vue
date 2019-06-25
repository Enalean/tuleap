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
    <div class="tlp-framed">
        <div class="document-header">
            <document-title-lock-info v-bind:item="embedded_file"
                                      v-bind:is-displaying-in-header="true"
            />

            <h1 class="document-header-title">{{ embedded_title }}</h1>

            <actions-header v-bind:item="embedded_file"/>

            <approval-table-badge v-bind:item="embedded_file" v-bind:is-in-folder-content-row="false"/>
        </div>

        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <section class="tlp-pane-section" v-dompurify-html="embedded_content"></section>
            </div>
        </section>

        <create-new-embedded-file-version-modal
            v-if="is_modal_shown"
            v-bind:item="embedded_file"
            v-on:hidden="hideModal()"
        />
        <confirm-deletion-modal
            v-if="show_confirm_deletion_modal"
            v-bind:item="embedded_file"
            v-on:delete-modal-closed="hideDeleteItemModal"
            v-bind:should-redirect-to-parent-after-deletion="true"
        />
    </div>
</template>

<script>
import DropdownButton from "../ActionsDropDown/DropdownButton.vue";
import DropdownMenu from "../ActionsDropDown/DropdownMenu.vue";
import ActionsHeader from "./ActionsHeader.vue";
import DocumentTitleLockInfo from "../LockInfo/DocumentTitleLockInfo.vue";
import ApprovalTableBadge from "../ApprovalTables/ApprovalTableBadge.vue";

export default {
    name: "DisplayEmbeddedContent",
    components: {
        ApprovalTableBadge,
        DocumentTitleLockInfo,
        ActionsHeader,
        DropdownMenu,
        DropdownButton,
        "create-new-embedded-file-version-modal": () =>
            import(/* webpackChunkName: "document-new-embedded-file-version-modal" */ "../ModalCreateNewItemVersion/CreateNewVersionEmbeddedFileModal.vue"),
        "confirm-deletion-modal": () =>
            import(/* webpackChunkName: "document-confirm-item-deletion-modal" */ "../ModalDeleteItem/ModalConfirmDeletion.vue")
    },
    props: {
        embedded_file: Object
    },
    data() {
        return {
            is_modal_shown: false,
            show_confirm_deletion_modal: false
        };
    },
    computed: {
        embedded_title() {
            return this.embedded_file.title;
        },
        embedded_content() {
            if (!this.embedded_file.embedded_file_properties) {
                return "";
            }

            return this.embedded_file.embedded_file_properties.content;
        }
    },
    mounted() {
        document.addEventListener(
            "show-create-new-item-version-modal",
            this.showCreateNewItemVersionModal
        );

        document.addEventListener("show-confirm-item-deletion-modal", this.showDeleteItemModal);

        this.$once("hook:beforeDestroy", () => {
            document.removeEventListener(
                "show-create-new-item-version-modal",
                this.showCreateNewItemVersionModal
            );

            document.removeEventListener(
                "show-confirm-item-deletion-modal",
                this.showDeleteItemModal
            );
        });
    },
    methods: {
        showCreateNewItemVersionModal() {
            this.is_modal_shown = true;
        },
        hideModal() {
            this.is_modal_shown = false;
        },
        showDeleteItemModal() {
            this.show_confirm_deletion_modal = true;
        },
        hideDeleteItemModal() {
            this.show_confirm_deletion_modal = false;
        }
    }
};
</script>

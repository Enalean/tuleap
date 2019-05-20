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
    <div class="embedded-document-container tlp-framed-vertically">
        <div class="document-header tlp-framed-horizontally">
            <document-title-lock-info v-bind:item="embeddedFile"
                                      v-bind:is-displaying-in-header="true"
            />

            <h1 class="document-header-title">{{ embeddedFile.title }}</h1>

            <actions-header v-bind:item="embeddedFile"/>

            <approval-table-badge v-bind:item="embeddedFile" v-bind:is-in-folder-content-row="false"/>

            <embedded-file-edition-switcher
                v-bind:is-in-large-view="is_embedded_in_large_view"
            />
        </div>

        <section class="tlp-pane embedded-document"
                 v-bind:class="{ 'narrow': !is_embedded_in_large_view }"
                 data-test="display-embedded-content"
        >
            <div class="tlp-pane-container">
                <section class="tlp-pane-section" v-dompurify-html="embedded_content"></section>
            </div>
        </section>

        <create-new-embedded-file-version-modal
            v-if="is_modal_shown"
            v-bind:item="embeddedFile"
            v-on:hidden="hideModal()"
        />
        <confirm-deletion-modal
            v-if="show_confirm_deletion_modal"
            v-bind:item="embeddedFile"
            v-on:delete-modal-closed="hideDeleteItemModal"
            v-bind:should-redirect-to-parent-after-deletion="true"
        />
    </div>
</template>

<script>
import ActionsHeader from "./ActionsHeader.vue";
import DocumentTitleLockInfo from "../LockInfo/DocumentTitleLockInfo.vue";
import ApprovalTableBadge from "../ApprovalTables/ApprovalTableBadge.vue";
import EmbeddedFileEditionSwitcher from "./EmbeddedFileEditionSwitcher.vue";
import { mapState } from "vuex";

export default {
    name: "DisplayEmbeddedContent",
    components: {
        EmbeddedFileEditionSwitcher,
        ApprovalTableBadge,
        DocumentTitleLockInfo,
        ActionsHeader,
        "create-new-embedded-file-version-modal": () =>
            import(/* webpackChunkName: "document-new-embedded-file-version-modal" */ "../ModalCreateNewItemVersion/CreateNewVersionEmbeddedFileModal.vue"),
        "confirm-deletion-modal": () =>
            import(/* webpackChunkName: "document-confirm-item-deletion-modal" */ "../ModalDeleteItem/ModalConfirmDeletion.vue")
    },
    props: {
        embeddedFile: Object
    },
    data() {
        return {
            is_modal_shown: false,
            show_confirm_deletion_modal: false,
            is_in_large_view: false
        };
    },
    computed: {
        ...mapState(["is_embedded_in_large_view"]),
        embedded_content() {
            if (!this.embeddedFile.embedded_file_properties) {
                return "";
            }

            return this.embeddedFile.embedded_file_properties.content;
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

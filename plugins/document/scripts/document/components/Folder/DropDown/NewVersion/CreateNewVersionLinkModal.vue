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
    <form
        class="tlp-modal"
        role="dialog"
        v-bind:aria-labelled-by="aria_labelled_by"
        v-on:submit="createNewLinkVersion"
        data-test="document-new-item-version-modal"
    >
        <modal-header v-bind:modal-title="modal_title" v-bind:aria-labelled-by="aria_labelled_by" />
        <modal-feedback />
        <div class="tlp-modal-body">
            <item-update-properties
                v-bind:version="version"
                v-bind:item="item"
                v-on:approval-table-action-change="setApprovalUpdateAction"
                v-bind:is-open-after-dnd="false"
            >
                <link-properties
                    v-if="link_model"
                    v-bind:value="item.link_properties.link_url"
                    v-bind:item="item"
                    key="link-props"
                />
            </item-update-properties>
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="submit_button_label"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-submit-button-class="'fa-solid fa-plus'"
            data-test="document-modal-submit-button-create-link-version"
        />
    </form>
</template>

<script>
import { mapState } from "vuex";
import { createModal } from "@tuleap/tlp-modal";
import { sprintf } from "sprintf-js";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import ItemUpdateProperties from "./PropertiesForUpdate/ItemUpdateProperties.vue";
import LinkProperties from "../PropertiesForCreateOrUpdate/LinkProperties.vue";
import emitter from "../../../../helpers/emitter";

export default {
    name: "CreateNewVersionLinkModal",
    components: {
        LinkProperties,
        ItemUpdateProperties,
        ModalFeedback,
        ModalHeader,
        ModalFooter,
    },
    props: {
        item: Object,
    },
    data() {
        return {
            link_model: null,
            version: {},
            is_loading: false,
            is_displayed: false,
            modal: null,
        };
    },
    computed: {
        ...mapState("error", ["has_modal_error"]),
        submit_button_label() {
            return this.$gettext("Create new version");
        },
        modal_title() {
            return sprintf(this.$gettext('New version for "%s"'), this.item.title);
        },
        aria_labelled_by() {
            return "document-new-item-version-modal";
        },
    },
    mounted() {
        this.modal = createModal(this.$el);
        this.registerEvents();
        emitter.on("update-version-title", this.updateTitleValue);
        emitter.on("update-changelog-property", this.updateChangelogValue);
        emitter.on("update-lock", this.updateLock);
        emitter.on("update-link-properties", this.updateLinkProperties);
    },
    beforeUnmount() {
        emitter.off("update-version-title", this.updateTitleValue);
        emitter.off("update-changelog-property", this.updateChangelogValue);
        emitter.off("update-lock", this.updateLock);
        emitter.off("update-link-properties", this.updateLinkProperties);
    },
    methods: {
        setApprovalUpdateAction(value) {
            this.approval_table_action = value;
        },
        registerEvents() {
            this.modal.addEventListener("tlp-modal-hidden", this.reset);

            this.show();
        },
        show() {
            this.version = {
                title: "",
                changelog: "",
                is_file_locked: this.item.lock_info !== null,
            };

            this.link_model = this.item.link_properties;

            this.is_displayed = true;
            this.modal.show();
        },
        reset() {
            this.$store.commit("error/resetModalError");
            this.is_displayed = false;
            this.is_loading = false;
            this.link_model = null;
        },
        async createNewLinkVersion(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("error/resetModalError");

            await this.$store.dispatch("createNewLinkVersionFromModal", [
                this.item,
                this.link_model.link_url,
                this.version.title,
                this.version.changelog,
                this.version.is_file_locked,
                this.approval_table_action,
            ]);

            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.$store.dispatch("refreshLink", this.item);
                this.link_model = null;
                this.modal.hide();
            }
        },
        updateTitleValue(title) {
            this.version.title = title;
        },
        updateChangelogValue(changelog) {
            this.version.changelog = changelog;
        },
        updateLock(is_locked) {
            this.version.is_file_locked = is_locked;
        },
        updateLinkProperties(url) {
            if (!this.item) {
                return;
            }
            this.link_model.link_url = url;
        },
    },
};
</script>

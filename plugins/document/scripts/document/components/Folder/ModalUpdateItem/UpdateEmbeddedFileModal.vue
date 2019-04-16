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
    <form class="tlp-modal" role="dialog" aria-labelledby="document-item-modal" v-on:submit="updateEmbeddedFile">
        <modal-header v-bind:modal-title="modal_title" v-bind:aria-labelled-by="aria_labelled_by"/>
        <modal-feedback/>
        <div class="tlp-modal-body">
            <item-update-properties v-bind:version="version" v-bind:item="item" v-on:approvalTableActionChange="setApprovalUpdateAction">
                <embedded-properties v-if="embedded_file_model" v-model="embedded_file_model" v-bind:item="item" key="embedded-props"/>
            </item-update-properties>
        </div>
        <modal-footer v-bind:is-loading="is_loading" v-bind:submit-button-label="submit_button_label" v-bind:aria-labelled-by="aria_labelled_by"/>
    </form>
</template>

<script>
import { mapState } from "vuex";
import { modal as createModal } from "tlp";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import EmbeddedProperties from "../Property/EmbeddedProperties.vue";
import ItemUpdateProperties from "../Property/ItemUpdateProperties.vue";

export default {
    name: "UpdateEmbeddedFileModal",
    components: {
        ItemUpdateProperties,
        ModalFeedback,
        ModalHeader,
        ModalFooter,
        EmbeddedProperties
    },
    props: {
        item: Object
    },
    data() {
        return {
            embedded_file_model: null,
            version: {},
            is_loading: false,
            is_displayed: false,
            modal: null
        };
    },
    computed: {
        ...mapState("error", ["has_modal_error"]),
        submit_button_label() {
            return this.$gettext("Update");
        },
        modal_title() {
            return this.$gettext("Update embedded file");
        },
        aria_labelled_by() {
            return "document-update-item-modal";
        }
    },
    mounted() {
        this.modal = createModal(this.$el);
        this.registerEvents();
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
                is_file_locked: this.item.lock_info !== null
            };

            this.embedded_file_model = this.item.embedded_file_properties;

            this.is_displayed = true;
            this.modal.show();
        },
        reset() {
            this.$store.commit("resetModalError");
            this.is_displayed = false;
            this.is_loading = false;
            this.embedded_file_model = null;
        },
        async updateEmbeddedFile(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("resetModalError");

            await this.$store.dispatch("updateEmbeddedFileFromModal", [
                this.item,
                this.embedded_file_model.content,
                this.version.title,
                this.version.changelog,
                this.version.is_file_locked,
                this.approval_table_action
            ]);

            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.item.embedded_file_properties.content = this.embedded_file_model.content;
                this.embedded_file_model = null;
                this.modal.hide();
            }
        }
    }
};
</script>

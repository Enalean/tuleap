<!--
  - Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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
  -
  -->

<template>
    <form class="tlp-modal"
          role="dialog"
          aria-labelledby="document-new-item-modal"
          v-on:submit="addDocument"
          enctype="multipart/form-data"
    >
        <modal-header v-bind:modal-title="modal_title" v-bind:aria-labelled-by="aria_labelled_by"/>
        <modal-feedback/>
        <div class="tlp-modal-body document-item-modal-body" v-if="is_displayed">
            <type-selector v-model="item.type"/>

            <global-metadata v-bind:currently-updated-item="item" v-bind:parent="parent" v-bind:is-in-updated-context="false">
                <link-properties v-model="item.link_properties" v-bind:item="item"/>
                <wiki-properties v-model="item.wiki_properties" v-bind:item="item"/>
                <embedded-properties v-model="item.embedded_properties" v-bind:item="item"/>
                <file-properties v-model="item.file_properties" v-bind:item="item"/>
            </global-metadata>
            <other-information-metadata v-bind:currently-updated-item="item"/>
        </div>

        <modal-footer v-bind:is-loading="is_loading" v-bind:submit-button-label="submit_button_label" v-bind:aria-labelled-by="aria_labelled_by"/>
    </form>
</template>

<script>
import { mapState } from "vuex";
import { modal as createModal } from "tlp";
import { DOCMAN_ITEM_STATUS_NONE, TYPE_FILE } from "../../../constants.js";
import GlobalMetadata from "../Metadata/GlobalMetadata.vue";
import LinkProperties from "../Property/LinkProperties.vue";
import WikiProperties from "../Property/WikiProperties.vue";
import TypeSelector from "./TypeSelector.vue";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import EmbeddedProperties from "../Property/EmbeddedProperties.vue";
import FileProperties from "../Property/FileProperties.vue";
import OtherInformationMetadata from "../Metadata/OtherInformationMetadata.vue";

export default {
    name: "NewItemModal",
    components: {
        OtherInformationMetadata,
        FileProperties,
        EmbeddedProperties,
        ModalFooter,
        ModalHeader,
        GlobalMetadata,
        LinkProperties,
        WikiProperties,
        TypeSelector,
        ModalFeedback
    },
    data() {
        return {
            item: {},
            is_displayed: false,
            is_loading: false,
            modal: null,
            parent: {}
        };
    },
    computed: {
        ...mapState([
            "current_folder",
            "is_obsolescence_date_metadata_used",
            "is_item_status_metadata_used"
        ]),
        ...mapState("error", ["has_modal_error"]),
        submit_button_label() {
            return this.$gettext("Create document");
        },
        modal_title() {
            return this.$gettext("New document");
        },
        aria_labelled_by() {
            return "document-new-item-modal";
        }
    },
    mounted() {
        this.modal = createModal(this.$el);
        this.registerEvents();
    },
    methods: {
        getDefaultItem() {
            return {
                title: "",
                description: "",
                type: TYPE_FILE,
                link_properties: {
                    link_url: ""
                },
                wiki_properties: {
                    page_name: ""
                },
                file_properties: {
                    file: ""
                },
                embedded_properties: {
                    content: ""
                },
                metadata: [
                    { short_name: "status", list_value: [{ id: DOCMAN_ITEM_STATUS_NONE }] },
                    { short_name: "obsolescence_date", value: null }
                ]
            };
        },
        registerEvents() {
            document.addEventListener("show-new-document-modal", this.show);
            this.$once("hook:beforeDestroy", () => {
                document.removeEventListener("show-new-document-modal", this.show);
            });
            this.modal.addEventListener("tlp-modal-hidden", this.reset);
        },
        show(event) {
            this.item = this.getDefaultItem();
            this.parent = event.detail.parent;
            this.is_displayed = true;
            this.modal.show();
        },
        reset() {
            this.$store.commit("error/resetModalError");
            this.is_displayed = false;
            this.is_loading = false;
            this.item = this.getDefaultItem();
        },
        async addDocument(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("error/resetModalError");
            await this.$store.dispatch("createNewItem", [
                this.item,
                this.parent,
                this.current_folder
            ]);

            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.modal.hide();
            }
        }
    }
};
</script>

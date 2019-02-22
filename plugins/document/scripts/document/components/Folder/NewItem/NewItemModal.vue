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
        <modal-header v-bind:modal_title="modal_title"/>
        <modal-feedback/>
        <div class="tlp-modal-body document-new-item-modal-body" v-if="is_displayed">
            <type-selector v-model="item.type"/>

            <global-properties v-bind:item="item" v-bind:parent="parent">
                <link-properties v-model="item.link_properties" v-bind:item="item"/>
                <wiki-properties v-model="item.wiki_properties" v-bind:item="item"/>
                <embedded-properties v-model="item.embedded_properties" v-bind:item="item"/>
                <file-properties v-model="item.file_properties" v-bind:item="item"/>
            </global-properties>
        </div>

        <modal-footer v-bind:is_loading="is_loading" v-bind:submit_button_label="submit_button_label"/>
    </form>
</template>

<script>
import { mapState } from "vuex";
import { modal as createModal } from "tlp";
import GlobalProperties from "./Property/GlobalProperties.vue";
import LinkProperties from "./Property/LinkProperties.vue";
import WikiProperties from "./Property/WikiProperties.vue";
import TypeSelector from "./TypeSelector.vue";
import ModalHeader from "./ModalHeader.vue";
import ModalFooter from "./ModalFooter.vue";
import ModalFeedback from "./ModalFeedback.vue";
import EmbeddedProperties from "./Property/EmbeddedProperties.vue";
import FileProperties from "./Property/FileProperties.vue";
import { TYPE_FILE } from "../../../constants.js";

export default {
    name: "NewItemModal",
    components: {
        FileProperties,
        EmbeddedProperties,
        ModalFooter,
        ModalHeader,
        GlobalProperties,
        LinkProperties,
        WikiProperties,
        TypeSelector,
        ModalFeedback
    },
    data() {
        return {
            default_item: {
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
                }
            },
            item: {},
            is_displayed: false,
            is_loading: false,
            modal: null,
            parent: {}
        };
    },
    computed: {
        ...mapState(["current_folder", "has_modal_error"]),
        submit_button_label() {
            return this.$gettext("Create document");
        },
        modal_title() {
            return this.$gettext("New document");
        }
    },
    mounted() {
        this.modal = createModal(this.$el);
        this.registerEvents();
    },
    methods: {
        registerEvents() {
            document.addEventListener("show-new-document-modal", this.show);
            this.$once("hook:beforeDestroy", () => {
                document.removeEventListener("show-new-document-modal", this.show);
            });
            this.modal.addEventListener("tlp-modal-hidden", this.reset);
        },
        show(event) {
            this.item = { ...this.default_item };
            this.parent = event.detail.parent;
            this.is_displayed = true;
            this.modal.show();
        },
        reset() {
            this.$store.commit("resetModalError");
            this.is_displayed = false;
            this.is_loading = false;
        },
        async addDocument(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("resetModalError");
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

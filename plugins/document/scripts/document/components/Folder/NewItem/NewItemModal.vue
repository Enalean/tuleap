<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <form class="tlp-modal" role="dialog" aria-labelledby="document-new-item-modal" v-on:submit="addDocument">
        <modal-header/>
        <modal-feedback/>
        <div class="tlp-modal-body document-new-item-modal-body" v-if="is_displayed">
            <type-selector v-model="item.type"/>

            <div class="document-new-item-properties">
                <property-title v-model="item.title"/>
                <property-description v-model="item.description"/>
                <link-properties v-model="item.link_properties" v-bind:item="item"/>
            </div>

        </div>
        <modal-footer v-bind:is_loading="is_loading"/>
    </form>
</template>

<script>
import { mapState } from "vuex";
import { modal as createModal } from "tlp";
import { TYPE_EMPTY } from "../../../constants.js";
import { selfClosingInfo } from "../../../../../../../src/www/scripts/tuleap/feedback.js";
import PropertyTitle from "./Property/PropertyTitle.vue";
import PropertyDescription from "./Property/PropertyDescription.vue";
import LinkProperties from "./Property/LinkProperties.vue";
import TypeSelector from "./TypeSelector.vue";
import ModalHeader from "./ModalHeader.vue";
import ModalFooter from "./ModalFooter.vue";
import ModalFeedback from "./ModalFeedback.vue";

export default {
    name: "NewItemModal",
    components: {
        ModalFooter,
        ModalHeader,
        LinkProperties,
        TypeSelector,
        PropertyTitle,
        PropertyDescription,
        ModalFeedback
    },
    data() {
        return {
            default_item: {
                title: "",
                description: "",
                type: TYPE_EMPTY,
                link_properties: {
                    link_url: ""
                }
            },
            item: {},
            is_displayed: false,
            is_loading: false,
            modal: null
        };
    },
    computed: {
        ...mapState(["current_folder", "has_modal_error"])
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
        show() {
            this.item = { ...this.default_item };
            this.is_displayed = true;
            this.modal.show();
        },
        reset() {
            this.$store.commit("resetModalError");
            this.is_displayed = false;
        },
        async addDocument(event) {
            event.preventDefault();
            this.is_loading = true;

            await this.$store.dispatch("createNewDocument", [this.item, this.current_folder]);
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.modal.hide();
                selfClosingInfo(this.$gettext("Document has been successfully created."));
            }
        }
    }
};
</script>

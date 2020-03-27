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
        v-on:submit="createNewWikiVersion"
        data-test="document-new-item-version-modal"
    >
        <modal-header
            v-bind:modal-title="modal_title"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-header-class="'fa-plus'"
        />
        <modal-feedback />
        <div class="tlp-modal-body">
            <div class="docman-item-update-property">
                <div class="docman-item-title-update-property">
                    <wiki-properties
                        v-model="wiki_model.wiki_properties"
                        v-bind:item="wiki_model"
                    />
                    <lock-property v-model="version.is_file_locked" v-bind:item="item" />
                </div>
            </div>
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="submit_button_label"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-submit-button-class="'fa-plus'"
        />
    </form>
</template>

<script>
import { mapState } from "vuex";
import { modal as createModal } from "tlp";
import { sprintf } from "sprintf-js";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import WikiProperties from "../Property/WikiProperties.vue";
import LockProperty from "../Property/LockProperty.vue";

export default {
    name: "CreateNewVersionWikiModal",
    components: {
        WikiProperties,
        ModalFeedback,
        ModalHeader,
        ModalFooter,
        LockProperty,
    },
    props: {
        item: Object,
    },
    data() {
        return {
            wiki_model: {},
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

        this.show();
    },
    methods: {
        setApprovalUpdateAction(value) {
            this.approval_table_action = value;
        },
        registerEvents() {
            this.modal.addEventListener("tlp-modal-hidden", this.reset);
        },
        show() {
            this.version = {
                title: "",
                changelog: "",
                is_file_locked: this.item.lock_info !== null,
            };
            this.wiki_model = {
                type: this.item.type,
                wiki_properties: this.item.wiki_properties,
            };
            this.is_displayed = true;
            this.modal.show();
        },
        reset() {
            this.$store.commit("error/resetModalError");
            this.is_displayed = false;
            this.is_loading = false;
            this.wiki_model = {};
        },
        async createNewWikiVersion(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("error/resetModalError");

            await this.$store.dispatch("createNewWikiVersionFromModal", [
                this.item,
                this.wiki_model.wiki_properties.page_name,
                this.version.title,
                this.version.changelog,
                this.version.is_file_locked,
                this.approval_table_action,
            ]);
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.item.wiki_properties.page_name = this.wiki_model.wiki_properties.page_name;
                this.$store.dispatch("refreshWiki", this.item);
                this.wiki_model = {};
                this.modal.hide();
            }
        },
    },
};
</script>

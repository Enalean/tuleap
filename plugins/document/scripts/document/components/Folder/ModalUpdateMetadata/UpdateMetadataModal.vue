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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <form class="tlp-modal" role="dialog" v-bind:aria-labelled-by="aria_labelled_by">
        <modal-header v-bind:modal-title="modal_title" v-bind:aria-labelled-by="aria_labelled_by"/>
        <div class="tlp-modal-body document-new-item-modal-body">
            <global-metadata v-bind:parent="current_folder" v-bind:currently-updated-item="item_to_update" v-bind:is-in-updated-context="true">
                <owner-metadata v-bind:currently-updated-item="item_to_update"/>
            </global-metadata>

            <other-information-metadata v-bind:currently-updated-item="item_to_update"/>

            <modal-footer v-bind:is-loading="is_loading"
                          v-bind:submit-button-label="submit_button_label"
                          v-bind:aria-labelled-by="aria_labelled_by"
            />
        </div>
    </form>
</template>

<script>
import { modal as createModal } from "tlp";
import { sprintf } from "sprintf-js";
import { mapState } from "vuex";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import GlobalMetadata from "../Metadata/GlobalMetadata.vue";
import OtherInformationMetadata from "../Metadata/OtherInformationMetadata.vue";
import OwnerMetadata from "../Metadata/OwnerMetadata.vue";

export default {
    components: {
        OwnerMetadata,
        OtherInformationMetadata,
        GlobalMetadata,
        ModalHeader,
        ModalFooter
    },
    props: {
        item: Object
    },
    data() {
        return {
            item_to_update: {},
            is_loading: true,
            modal: null
        };
    },
    computed: {
        ...mapState(["current_folder"]),
        submit_button_label() {
            return this.$gettext("Update properties");
        },
        modal_title() {
            return sprintf(this.$gettext('Edit "%s" properties'), this.item.title);
        },
        aria_labelled_by() {
            return "document-update-file-metadata-modal";
        }
    },
    beforeMount() {
        this.item_to_update = { ...this.item };
    },
    mounted() {
        this.modal = createModal(this.$el);

        this.registerEvents();

        this.show();
    },
    methods: {
        show() {
            this.is_displayed = true;
            this.modal.show();
        },
        registerEvents() {
            this.modal.addEventListener("tlp-modal-hidden", this.reset);
        },
        reset() {
            this.is_displayed = false;
        }
    }
};
</script>

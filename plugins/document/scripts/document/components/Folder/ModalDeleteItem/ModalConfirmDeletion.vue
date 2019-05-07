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
    <div class="tlp-modal tlp-modal-danger" role="dialog" aria-labelledby="document-confirm-deletion-modal-title">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="document-confirm-deletion-modal-title" v-translate>Hold on a second!</h1>
            <div class="tlp-modal-close" data-dismiss="modal" v-bind:aria-label="close">
                ×
            </div>
        </div>
        <modal-feedback/>
        <div class="tlp-modal-body">
            <p>{{ modal_description }}</p>
        </div>
        <div class="tlp-modal-footer">
            <button type="button" class="tlp-button-danger tlp-button-outline tlp-modal-action" data-dismiss="modal">Cancel</button>
            <button
                type="button"
                class="tlp-button-danger tlp-modal-action"
                v-on:click="deleteItem()"
                v-bind:class="{ 'disabled': has_modal_error }"
                v-bind:disabled="has_modal_error"
            >
                <i
                    class="fa tlp-button-icon"
                    v-bind:class="{
                        'fa-spin fa-circle-o-notch': is_item_being_deleted,
                        'fa-trash': ! is_item_being_deleted
                    }"
                ></i>
                <span v-translate>Delete</span>
            </button>
        </div>
    </div>
</template>

<script>
import { sprintf } from "sprintf-js";
import { mapState } from "vuex";
import { modal as createModal } from "tlp";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";

export default {
    components: { ModalFeedback },
    props: {
        item: Object
    },
    data() {
        return {
            modal: null,
            is_item_being_deleted: false
        };
    },
    computed: {
        ...mapState("error", ["has_modal_error"]),
        close() {
            return this.$gettext("Close");
        },
        modal_description() {
            return sprintf(
                this.$gettext(
                    'You are about to delete "%s" permanently. Please confirm your action.'
                ),
                this.item.title
            );
        }
    },
    mounted() {
        this.modal = createModal(this.$el);
        this.modal.addEventListener("tlp-modal-hidden", this.resetModal);

        this.modal.show();
    },
    methods: {
        async deleteItem() {
            this.is_item_being_deleted = true;

            await this.$store.dispatch("deleteItem", this.item);

            if (!this.has_modal_error) {
                this.modal.hide();
            }

            this.is_item_being_deleted = false;
        },
        resetModal() {
            this.$store.commit("error/resetModalError");
            this.$emit("delete-modal-closed");
        }
    }
};
</script>

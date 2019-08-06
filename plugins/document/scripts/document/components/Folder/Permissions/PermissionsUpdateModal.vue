<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
          aria-labelledby="document-update-permissions-modal"
          enctype="multipart/form-data"
    >
        <modal-header v-bind:modal-title="modal_title"
                      v-bind:aria-labelled-by="aria_labelled_by"
                      v-bind:icon-header-class="'fa-pencil'"
        />
        <modal-feedback/>
        <div class="tlp-modal-body document-item-modal-body">
            <div class="tlp-alert-info document-access-legacy-permissions-update-page">
                <a v-bind:href="legacy_update_page_href">
                    <translate>Access to legacy permissions update page</translate>
                </a>
            </div>
            <div></div>
        </div>

        <div v-bind:aria-labelled-by="aria_labelled_by" class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-translate
            >
                Cancel
            </button>
        </div>
    </form>
</template>
<script>
import { mapState } from "vuex";
import { modal as createModal } from "tlp";
import { sprintf } from "sprintf-js";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import EventBus from "../../../helpers/event-bus.js";

export default {
    name: "PermissionsUpdateModal",
    components: {
        ModalHeader,
        ModalFeedback
    },
    props: {
        item: Object
    },
    data: () => {
        return {
            aria_labelled_by: "document-update-permissions-modal"
        };
    },
    computed: {
        ...mapState(["project_id"]),
        ...mapState("error", ["has_modal_error"]),
        modal_title() {
            return sprintf(this.$gettext('Edit "%s" permissions'), this.item.title);
        },
        legacy_update_page_href() {
            return (
                "/plugins/docman/?group_id=" +
                encodeURIComponent(this.project_id) +
                "&id=" +
                encodeURIComponent(this.item.id) +
                "&action=details&section=permissions"
            );
        }
    },
    mounted() {
        this.modal = createModal(this.$el);
        EventBus.$on("show-update-permissions-modal", this.show);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
    },
    beforeDestroy() {
        EventBus.$off("show-update-permissions-modal", this.show);
        this.modal.removeEventListener("tlp-modal-hidden", this.reset);
    },
    methods: {
        show() {
            this.modal.show();
        },
        reset() {
            this.$store.commit("error/resetModalError");
        }
    }
};
</script>

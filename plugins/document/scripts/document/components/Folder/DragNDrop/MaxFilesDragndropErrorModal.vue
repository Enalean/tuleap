<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <div class="tlp-modal tlp-modal-danger"
         role="dialog"
         aria-labelledby="document-max-files-dragndrop-error-modal-title"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="document-max-files-dragndrop-error-modal-title">
                <i class="fa fa-frown-o tlp-modal-title-icon"></i>
                <translate>Oops</translate>
            </h1>
            <div class="tlp-modal-close" data-dismiss="modal" v-bind:aria-label="close">
                &times;
            </div>
        </div>
        <div class="tlp-modal-body">
            <translate
                tag="p"
                v-bind:translate-params="{nb: max_files_dragndrop}"
                v-bind:translate-n="max_files_dragndrop"
                translate-plural="You are not allowed to drag 'n drop more than %{ nb } files at once."
            >
                You are not allowed to drag 'n drop more than %{ nb } file at once.
            </translate>
            <translate tag="p">
                Please start again.
            </translate>
        </div>
        <div class="tlp-modal-footer tlp-modal-footer-large">
            <button type="submit"
                    class="tlp-button-danger tlp-button-outline tlp-modal-action"
                    data-dismiss="modal"
                    v-translate
            >
                Close
            </button>
        </div>
    </div>
</template>

<script>
import { mapState } from "vuex";
import { modal as createModal } from "tlp";

export default {
    computed: {
        ...mapState(["max_files_dragndrop"]),
        close() {
            return this.$gettext("Close");
        }
    },
    mounted() {
        const modal = createModal(this.$el);
        modal.addEventListener("tlp-modal-hidden", () => {
            this.$emit("error-modal-hidden");
        });
        modal.show();
    }
};
</script>

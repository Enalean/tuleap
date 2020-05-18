<!--
  - Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
  -
  - This item is a part of Tuleap.
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
    <div
        class="tlp-modal tlp-modal-info"
        role="dialog"
        aria-labelledby="modal-confirm-download-archive-label"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="modal-confirm-download-archive-label" v-translate>
                Confirmation needed
            </h1>
            <div
                class="tlp-modal-close"
                tabindex="0"
                role="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
                v-on:click="close()"
            >
                ×
            </div>
        </div>
        <div class="tlp-modal-body">
            <p v-translate>
                Every sub-folder, file and embedded file in this folder will be downloaded as a zip
                archive.
            </p>
            <warning-about-archive-errors />
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-info tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-on:click="close()"
                data-test="close-confirm-archive-download-modal"
                v-translate
            >
                Cancel
            </button>
            <a
                type="button"
                download
                v-bind:href="folderHref"
                class="tlp-button-info tlp-button-primary tlp-modal-action"
                data-dismiss="modal"
                v-on:click="close()"
                data-test="confirm-download-archive-button"
                v-translate
            >
                Download
            </a>
        </div>
    </div>
</template>
<script>
import { modal } from "tlp";
import WarningAboutArchiveErrors from "./WarningAboutArchiveErrors.vue";

export default {
    name: "ModalConfirmArchiveDownload",
    components: { WarningAboutArchiveErrors },
    props: {
        folderHref: String,
    },
    data() {
        return {
            modal: null,
        };
    },
    mounted() {
        this.modal = modal(this.$el);
        this.modal.addEventListener("tlp-modal-hidden", this.close);
        this.modal.show();
    },
    methods: {
        close() {
            this.$emit("download-folder-as-zip-modal-closed");
        },
    },
};
</script>

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
        class="tlp-modal tlp-modal-warning"
        role="dialog"
        aria-labelledby="modal-archive-size-warning-label"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="modal-archive-size-warning-label" v-translate>
                Archive size warning threshold reached
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
            <p v-translate="{ warning_threshold }">
                The archive you want to download has a size greater than %{ warning_threshold } MB.
            </p>
            <p v-translate>
                Depending on the speed of your network, it can take some time to complete. Do you
                want to continue?
            </p>
            <div
                class="tlp-alert-warning"
                data-test="download-as-zip-folder-size-warning"
                v-translate="{ size_in_MB }"
            >
                Size of the archive to be downloaded: %{ size_in_MB } MB
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-warning tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-on:click="close()"
                data-test="close-archive-size-warning"
                v-translate
            >
                Cancel
            </button>
            <button
                type="button"
                class="tlp-button-warning tlp-button-primary tlp-modal-action"
                data-dismiss="modal"
                v-on:click="processDownload()"
                data-test="confirm-download-archive-button-despite-size-warning"
                v-translate
            >
                Download anyway
            </button>
        </div>
    </div>
</template>
<script>
import { mapState } from "vuex";
import { modal } from "tlp";

export default {
    name: "ModalArchiveSizeWarning",
    props: {
        size: Number,
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
    computed: {
        ...mapState(["warning_threshold"]),
        size_in_MB() {
            const size_in_mb = this.size / Math.pow(10, 6);
            return Number.parseFloat(size_in_mb).toFixed(2);
        },
    },
    methods: {
        close() {
            this.$emit("download-folder-as-zip-modal-closed");
        },
        processDownload() {
            window.location.assign(this.folderHref);
            this.close();
        },
    },
};
</script>

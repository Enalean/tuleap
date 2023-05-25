<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <div>
        <div
            class="document-header-global-progress tlp-tooltip tlp-tooltip-left"
            v-if="should_display_progress_bar"
            v-bind:data-tlp-tooltip="progress_bar_tooltip"
            v-on:click="modal.show()"
        >
            <global-upload-progress-bar
                v-bind:progress="global_upload_progress"
                v-bind:nb_uploads_in_error="nb_uploads_in_error"
            />
        </div>
        <files-uploads-modal ref="uploads_modal" />
    </div>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import { createModal } from "@tuleap/tlp-modal";
import FilesUploadsModal from "./FilesUploadsModal.vue";
import GlobalUploadProgressBar from "../ProgressBar/GlobalUploadProgressBar.vue";

export default {
    components: { FilesUploadsModal, GlobalUploadProgressBar },
    data() {
        return {
            modal: null,
            nb_uploads_in_error: 0,
        };
    },
    computed: {
        ...mapState(["files_uploads_list"]),
        ...mapGetters(["global_upload_progress"]),
        progress_bar_tooltip() {
            return this.$gettext(
                "Some files are being uploaded, click here to see the whole list."
            );
        },
        should_display_progress_bar() {
            return this.files_uploads_list.filter((file) => file.upload_error === null).length > 0;
        },
    },
    mounted() {
        this.modal = createModal(this.$refs.uploads_modal.$el);

        this.$store.watch(
            (state) => state.files_uploads_list.filter((file) => file.upload_error !== null),
            (uploads_in_error) => {
                if (uploads_in_error.length > this.nb_uploads_in_error && !this.modal.is_shown) {
                    this.modal.show();
                }

                this.nb_uploads_in_error = uploads_in_error.length;
            },
            { deep: true }
        );
    },
};
</script>

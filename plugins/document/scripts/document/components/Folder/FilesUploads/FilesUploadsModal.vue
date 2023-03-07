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
    <div class="tlp-modal" role="dialog" aria-labelledby="document-uploads-files-modal-title">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="document-uploads-files-modal-title">
                {{ $gettext("Uploading documents") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="close"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body document-uploads-files-list">
            <div
                class="tlp-pane-section document-dragndrop-file-upload"
                v-for="file in files_uploads_list"
                v-bind:key="file.id"
                v-bind:class="{
                    'document-dragndrop-file-upload-error': file.upload_error !== null,
                }"
            >
                <div class="document-uploads-file">
                    <span class="document-uploads-file-title">{{ file.title }}</span>
                    <span class="document-uploads-file-error-message" v-if="file.upload_error">
                        {{ getUploadErrorMessage(file) }}
                    </span>
                </div>
                <upload-progress-bar
                    v-if="file.is_uploading || file.is_uploading_new_version"
                    v-bind:item="file"
                />
                <span v-else-if="file.upload_error !== null" class="tlp-badge-danger">
                    {{ $gettext("Upload error") }}
                </span>
            </div>
            <div class="document-uploads-modal-empty-state" v-if="files_uploads_list.length === 0">
                <p class="empty-state-text">{{ $gettext("There is no upload in progress") }}</p>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button type="submit" class="tlp-button-primary tlp-modal-action" data-dismiss="modal">
                {{ $gettext("Close") }}
            </button>
        </div>
    </div>
</template>

<script>
import { mapState } from "vuex";
import UploadProgressBar from "../ProgressBar/UploadProgressBar.vue";
import { FILE_UPLOAD_UNKNOWN_ERROR } from "../../../constants";

export default {
    components: {
        UploadProgressBar,
    },
    computed: {
        ...mapState(["files_uploads_list"]),
        close() {
            return this.$gettext("Close");
        },
    },
    methods: {
        getUploadErrorMessage(file) {
            return file.upload_error === FILE_UPLOAD_UNKNOWN_ERROR
                ? this.$gettext("An error has occurred, please contact your administrator")
                : file.upload_error;
        },
    },
};
</script>

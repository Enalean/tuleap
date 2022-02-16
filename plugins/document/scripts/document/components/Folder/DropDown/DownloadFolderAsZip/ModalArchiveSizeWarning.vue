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
            <h1 class="tlp-modal-title" id="modal-archive-size-warning-label">
                {{ modal_header_title }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <div class="download-modal-multiple-warnings-section">
                <h2 class="tlp-modal-subtitle">
                    <span class="tlp-badge-warning download-modal-warning-number">1</span>
                    <span v-translate>Archive size warning threshold reached</span>
                </h2>
                <div class="download-modal-multiple-warnings-content">
                    <p v-translate="{ warning_threshold }">
                        The archive you want to download has a size greater than %{
                        warning_threshold } MB.
                    </p>
                    <p v-translate>
                        Depending on the speed of your network, it can take some time to complete.
                        Do you want to continue?
                    </p>
                    <div
                        class="tlp-alert-warning"
                        data-test="download-as-zip-folder-size-warning"
                        v-translate="{ size_in_MB }"
                    >
                        Size of the archive to be downloaded: %{ size_in_MB } MB
                    </div>
                </div>
            </div>
            <div class="download-modal-multiple-warnings-section" v-if="shouldWarnOsxUser">
                <h2 class="tlp-modal-subtitle">
                    <span class="tlp-badge-warning download-modal-warning-number">2</span>
                    <span v-translate>We detect you are using OSX</span>
                </h2>
                <div class="download-modal-multiple-warnings-content">
                    <p v-translate>
                        The archive you want to download has a size greater than or equal to 4GB or
                        contains more than 64000 files.
                    </p>
                    <p v-translate>
                        Please note that the OSX archive extraction tool might not succeed in
                        opening it.
                    </p>
                </div>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-warning tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                data-test="close-archive-size-warning"
                v-translate
            >
                Cancel
            </button>
            <a
                type="button"
                download
                v-bind:href="folderHref"
                class="tlp-button-warning tlp-button-primary tlp-modal-action"
                data-dismiss="modal"
                data-test="confirm-download-archive-button-despite-size-warning"
                v-translate
            >
                Download anyway
            </a>
        </div>
    </div>
</template>
<script lang="ts">
import type { Modal } from "tlp";
import { createModal } from "tlp";
import { Component, Prop, Vue } from "vue-property-decorator";
import { namespace } from "vuex-class";

const configuration = namespace("configuration");

@Component
export default class ModalArchiveSizeWarning extends Vue {
    @Prop({ required: true })
    readonly size!: number;

    @Prop({ required: true })
    readonly folderHref!: string;

    @Prop({ required: true })
    readonly shouldWarnOsxUser!: boolean;

    @configuration.State
    readonly warning_threshold!: number;

    private modal: Modal | null = null;

    get size_in_MB(): string {
        const size_in_mb = this.size / Math.pow(10, 6);
        return Number.parseFloat(size_in_mb.toString()).toFixed(2);
    }
    get modal_header_title(): string {
        const nb_warnings = this.shouldWarnOsxUser === true ? 2 : 1;
        const translated = this.$ngettext("1 warning", "%{ nb_warnings } warnings", nb_warnings);

        return this.$gettextInterpolate(translated, { nb_warnings });
    }

    mounted(): void {
        this.modal = createModal(this.$el);
        this.modal.addEventListener("tlp-modal-hidden", this.close);
        this.modal.show();
    }

    close(): void {
        this.$emit("download-folder-as-zip-modal-closed");
    }
}
</script>

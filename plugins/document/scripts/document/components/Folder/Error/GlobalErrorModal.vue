<!--
  - Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
    <div
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        aria-labelledby="document-error-modal-title"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="document-error-modal-title">
                <i class="fas fa-exclamation-triangle tlp-modal-title-icon"></i>
                <translate>Oops, there's an issue.</translate>
            </h1>
            <div
                class="tlp-modal-close"
                tabindex="0"
                role="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                ×
            </div>
        </div>
        <div class="tlp-modal-body">
            <p v-translate>It seems an action you tried to perform can't be done.</p>
            <template v-if="has_more_details">
                <a
                    v-if="!is_more_shown"
                    class="document-error-modal-link"
                    v-on:click="is_more_shown = true"
                    data-test="show-details"
                    v-translate
                >
                    Show error details
                </a>
                <pre v-if="is_more_shown" data-test="details">{{ global_modal_error_message }}</pre>
            </template>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-translate
            >
                Close
            </button>
            <button
                type="button"
                class="tlp-button-danger tlp-modal-action"
                data-test="reload"
                v-on:click="reloadPage"
            >
                <i class="fas fa-sync tlp-button-icon"></i>
                <translate>Reload the page</translate>
            </button>
        </div>
    </div>
</template>
<script>
import { mapState } from "vuex";
import { createModal } from "tlp";

export default {
    name: "GlobalErrorModal",
    data() {
        return {
            is_more_shown: false,
        };
    },
    computed: {
        ...mapState("error", ["global_modal_error_message"]),
        has_more_details() {
            return this.global_modal_error_message.length > 0;
        },
    },
    mounted() {
        const modal = createModal(this.$el, { destroy_on_hide: true });
        modal.show();
        modal.addEventListener("tlp-modal-hidden", this.reset);
    },
    methods: {
        reset() {
            this.$store.commit("error/resetErrors");
        },
        reloadPage() {
            window.location.reload();
        },
    },
};
</script>

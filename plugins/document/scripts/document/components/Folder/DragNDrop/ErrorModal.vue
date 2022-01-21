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
    <div
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        aria-labelledby="document-dragndrop-error-modal-title"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="document-dragndrop-error-modal-title">
                <slot name="modal-title">
                    <translate>Oops…</translate>
                </slot>
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="close"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body" v-bind:class="body_class">
            <slot></slot>
        </div>
        <div class="tlp-modal-footer tlp-modal-footer-large">
            <button
                type="submit"
                class="tlp-button-danger tlp-modal-action"
                data-dismiss="modal"
                v-translate
            >
                Close
            </button>
        </div>
    </div>
</template>

<script>
import { createModal } from "tlp";

export default {
    props: {
        body_class: String,
    },
    computed: {
        close() {
            return this.$gettext("Close");
        },
    },
    mounted() {
        const modal = createModal(this.$el);
        modal.addEventListener("tlp-modal-hidden", () => {
            this.$emit("error-modal-hidden");
        });
        modal.show();
    },
};
</script>

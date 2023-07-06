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
    <div
        class="tlp-modal"
        v-bind:class="modal_class"
        role="dialog"
        aria-labelledby="modal-label"
        ref="modal"
    >
        <template v-if="modal_content !== null">
            <div class="tlp-modal-header">
                <h1 id="modal-label" class="tlp-modal-title" data-test-type="modal-title">
                    {{ modal_content.title }}
                </h1>
                <button
                    class="tlp-modal-close"
                    type="button"
                    v-bind:aria-label="close_label"
                    data-dismiss="modal"
                >
                    <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
                </button>
            </div>
            <component
                v-bind:is="modal_content.component"
                v-bind="modal_content.props"
                v-bind:key="modal_content_version"
                data-test-type="modal-content"
            />
        </template>
    </div>
</template>

<script>
import { mapState } from "vuex";
import { createModal } from "@tuleap/tlp-modal";

export default {
    name: "BaselineModal",

    data() {
        return { modal: null, modal_content_version: 0 };
    },
    computed: {
        ...mapState("dialog_interface", { modal_content: "modal" }),
        close_label() {
            return this.$gettext("Close");
        },
        modal_class() {
            if (this.modal_content) {
                return this.modal_content.class;
            }
            return null;
        },
    },

    watch: {
        modal_content(new_modal_content) {
            if (new_modal_content !== null) {
                // Will force modal content to reset, thanks to v-bind:key
                this.modal_content_version = this.modal_content_version + 1;
            }
            this.$nextTick(() => {
                // Execute this after component update
                // Otherwise, classes added/removed by modal.show()/hide() would be ignored
                if (new_modal_content !== null) {
                    if (this.modal !== null) {
                        // Last modal was canceled
                        this.modal.destroy();
                    }
                    this.modal = createModal(this.$refs.modal);
                    this.modal.show();
                } else {
                    this.modal.hide();
                    this.modal.destroy();
                    this.modal = null;
                }
            });
        },
    },
};
</script>

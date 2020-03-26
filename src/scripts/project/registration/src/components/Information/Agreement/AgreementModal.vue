<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div id="modal-big-content" class="tlp-modal" role="dialog" aria-labelledby="term-of-services">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="term-of-services" v-translate>
                Policy agreement
            </h1>
            <div
                class="tlp-modal-close"
                tabindex="0"
                role="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                &times;
            </div>
        </div>
        <div class="tlp-modal-body" v-if="is_loading">
            <i class="fa fa-circle-o-notch fa-spin"></i>
        </div>
        <div class="tlp-modal-body" v-dompurify-html="agreement_content" v-else></div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-modal-action"
                v-translate
                data-dismiss="modal"
            >
                Close
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import { Component } from "vue-property-decorator";
import Vue from "vue";
import { Modal, modal as createModal } from "tlp";
import EventBus from "../../../helpers/event-bus";
import { getTermOfService } from "../../../api/rest-querier";

@Component
export default class AgreementModal extends Vue {
    modal: Modal | null = null;
    is_loading = false;
    agreement_content = "";

    mounted(): void {
        EventBus.$on("show-agreement", this.show);
    }

    beforeDestroy(): void {
        EventBus.$off("show-agreement", this.show);
        if (this.modal !== null) {
            this.modal.destroy();
        }
    }

    async show(): Promise<void> {
        this.modal = createModal(this.$el, { destroy_on_hide: true });
        if (this.modal) {
            this.is_loading = true;
            this.modal.show();

            this.agreement_content = await getTermOfService();
            this.is_loading = false;
        }
    }
}
</script>

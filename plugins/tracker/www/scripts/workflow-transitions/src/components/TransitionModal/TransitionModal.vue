<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <form
        class="tlp-modal tlp-modal-medium-sized"
        role="dialog"
        aria-labelledby="configure-modal-title"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="configure-modal-title" v-translate>Configure transition</h1>
            <div class="tlp-modal-close" data-dismiss="modal" aria-label="Close">&times;</div>
        </div>
        <modal-error-feedback/>
        <div class="tlp-modal-body">
            <pre-conditions-section/>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-translate
            >Cancel</button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                disabled
                v-translate
            >Save configuration</button>
        </div>
    </form>
</template>

<script>
import PreConditionsSection from "./PreConditionsSection.vue";
import ModalErrorFeedback from "./ModalErrorFeedback.vue";
import { modal as createModal } from "tlp";
import { mapMutations } from "vuex";

export default {
    name: "TransitionModal",
    components: {
        ModalErrorFeedback,
        PreConditionsSection
    },
    mounted() {
        const modal = createModal(this.$el);
        modal.addEventListener("tlp-modal-hidden", () => {
            this.clearModalShown();
        });
        this.$store.watch(
            state => state.transitionModal.is_modal_shown,
            new_value => {
                if (new_value === true) {
                    modal.show();
                }
            }
        );
    },
    methods: {
        ...mapMutations("transitionModal", ["clearModalShown"])
    }
};
</script>

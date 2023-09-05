<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
        v-on:submit.prevent="saveTransition"
        data-test="transition-modal"
    >
        <div class="tlp-modal-header">
            <transition-modal-title />
            <button class="tlp-modal-close" type="button" data-dismiss="modal" aria-label="Close">
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <modal-error-feedback />
        <div class="tlp-modal-body tlp-modal-body-with-sections">
            <pre-conditions-skeleton v-if="is_loading_modal" />
            <filled-pre-conditions-section v-else-if="is_modal_shown && !is_loading_modal" />
            <post-actions-section />
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                data-test="cancel-button"
                v-translate
                v-bind:disabled="is_modal_save_running"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="is_modal_save_running"
                data-test="save-button"
            >
                <i
                    class="tlp-button-icon fa fa-fw fa-spin fa-circle-o-notch"
                    v-if="is_modal_save_running"
                    data-test="save-button-spinner"
                ></i>
                <i class="tlp-button-icon fa fa-fw fa-save" v-else></i>
                <span v-translate>Save configuration</span>
            </button>
        </div>
    </form>
</template>

<script>
import PostActionsSection from "./PostActionsSection.vue";
import ModalErrorFeedback from "./ModalErrorFeedback.vue";
import PreConditionsSkeleton from "./Skeletons/PreConditionsSkeleton.vue";
import FilledPreConditionsSection from "./FilledPreConditionsSection.vue";
import TransitionModalTitle from "./TransitionModalTitle.vue";
import { createModal } from "@tuleap/tlp-modal";
import { mapMutations, mapState } from "vuex";

export default {
    name: "TransitionModal",
    components: {
        TransitionModalTitle,
        FilledPreConditionsSection,
        PreConditionsSkeleton,
        ModalErrorFeedback,
        PostActionsSection,
    },
    computed: {
        ...mapState("transitionModal", [
            "is_modal_save_running",
            "is_loading_modal",
            "is_modal_shown",
        ]),
    },
    mounted() {
        const modal = createModal(this.$el);
        modal.addEventListener("tlp-modal-hidden", () => {
            this.clearModalShown();
        });
        this.$store.watch(
            (state) => state.transitionModal.is_modal_shown,
            (new_value) => {
                if (new_value === true) {
                    modal.show();
                } else {
                    modal.hide();
                }
            },
        );
    },
    methods: {
        ...mapMutations("transitionModal", ["clearModalShown"]),
        saveTransition() {
            this.$store.dispatch("transitionModal/saveTransitionRules");
        },
    },
};
</script>

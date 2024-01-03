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
  -
  -->

<template>
    <action-button
        icon="trash-o"
        v-on:click="showConfirmation"
        class="tlp-button-danger"
        v-if="is_admin"
        v-bind:disabled="is_disabled"
        v-bind:title="title"
    >
        <span v-translate>Delete</span>
    </action-button>
</template>

<script>
import ActionButton from "../common/ActionButton.vue";
import DeleteBaselineConfirmationModal from "./DeleteBaselineConfirmationModal.vue";
import { mapState } from "vuex";

export default {
    name: "DeleteBaselineButton",
    components: { ActionButton },

    inject: ["is_admin"],

    props: {
        baseline: { required: true, type: Object },
    },

    computed: {
        ...mapState("comparisons", ["comparisons", "is_loading"]),
        is_disabled() {
            if (this.is_loading) {
                return true;
            }

            const is_part_of_comparisons = this.comparisons.some(
                (comparison) =>
                    comparison.base_baseline_id === this.baseline.id ||
                    comparison.compared_to_baseline_id === this.baseline.id,
            );

            return is_part_of_comparisons;
        },
        title() {
            return this.is_disabled
                ? this.$gettext(
                      "The baseline cannot be deleted because it is associated to a comparison.",
                  )
                : "";
        },
    },

    methods: {
        showConfirmation() {
            this.$store.commit("dialog_interface/showModal", {
                class: "tlp-modal-danger",
                component: DeleteBaselineConfirmationModal,
                title: this.$gettext("Delete baseline"),
                props: { baseline: this.baseline },
            });
        },
    },
};
</script>

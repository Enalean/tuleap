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
    <delete-confirmation-modal
        v-bind:submit_label="label"
        v-bind:default_failed_message="default_failed_message"
        v-bind:on_submit="confirm"
    >
        <span v-dompurify-html="message"></span>
    </delete-confirmation-modal>
</template>

<script>
import { deleteBaseline } from "../../api/rest-querier";
import DeleteConfirmationModal from "../common/DeleteConfirmationModal.vue";

export default {
    name: "DeleteBaselineConfirmationModal",
    components: { DeleteConfirmationModal },
    props: {
        baseline: { required: true, type: Object },
    },
    computed: {
        label() {
            return this.$gettext("Delete baseline");
        },
        default_failed_message() {
            return this.$gettext("Cannot delete baseline");
        },
        message() {
            const translated = this.$gettext(
                "You are about to delete the baseline <strong>%{ baseline_name }</strong>.",
            );
            return this.$gettextInterpolate(translated, {
                baseline_name: this.baseline.name,
            });
        },
    },
    methods: {
        async confirm() {
            try {
                await deleteBaseline(this.baseline.id);
            } catch (e) {
                // Error management is missing
                return;
            }

            this.$store.commit("baselines/delete", this.baseline);
            this.$store.commit("dialog_interface/notify", {
                text: this.$gettext("The baseline was deleted"),
                class: "success",
            });
            this.$store.commit("dialog_interface/hideModal");
        },
    },
};
</script>

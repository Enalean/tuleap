<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
        v-bind:failed_message="failed_message"
        v-bind:on_submit="confirm"
    >
        <span v-translate>
            You are about to delete the baseline <strong>%{ baseline.name }</strong>.
        </span>
    </delete-confirmation-modal>
</template>

<script>
import { deleteBaseline } from "../../api/rest-querier";
import DeleteConfirmationModal from "../common/DeleteConfirmationModal.vue";

export default {
    name: "DeleteBaselineConfirmationModal",
    components: { DeleteConfirmationModal },
    props: {
        baseline: { required: true, type: Object }
    },
    computed: {
        label() {
            return this.$gettext("Delete baseline");
        },
        failed_message() {
            return this.$gettext("Cannot delete baseline");
        }
    },
    methods: {
        async confirm() {
            await deleteBaseline(this.baseline.id);
            this.$store.commit("baselines/delete", this.baseline);
            this.$store.commit("dialog_interface/notify", {
                text: this.$gettext("The baseline was deleted"),
                class: "success"
            });
            this.$store.commit("dialog_interface/hideModal");
        }
    }
};
</script>

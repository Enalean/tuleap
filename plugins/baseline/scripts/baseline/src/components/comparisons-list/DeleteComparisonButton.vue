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
    <action-button
        icon="trash-o"
        v-on:click="showConfirmation()"
        class="tlp-button-danger"
        v-if="is_admin"
    >
        <span v-translate>Delete</span>
    </action-button>
</template>

<script>
import ActionButton from "../common/ActionButton.vue";
import DeleteComparisonConfirmationModal from "./DeleteComparisonConfirmationModal.vue";

export default {
    name: "DeleteComparisonButton",
    components: { ActionButton },
    inject: ["is_admin"],
    props: {
        comparison: { required: true, type: Object },
        base_baseline: { required: true, type: Object },
        compared_to_baseline: { required: true, type: Object },
    },

    methods: {
        showConfirmation() {
            this.$store.commit("dialog_interface/showModal", {
                class: "tlp-modal-danger",
                component: DeleteComparisonConfirmationModal,
                title: this.$gettext("Delete comparison"),
                props: {
                    comparison: this.comparison,
                    base_baseline: this.base_baseline,
                    compared_to_baseline: this.compared_to_baseline,
                },
            });
        },
    },
};
</script>

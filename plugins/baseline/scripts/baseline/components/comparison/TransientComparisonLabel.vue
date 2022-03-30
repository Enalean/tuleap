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
    <h2 class="comparison-label">
        Unsaved comparison
        <div class="comparison-actions">
            <button
                v-on:click="openSaveModal"
                type="button"
                class="tlp-button-primary"
                data-test-action="save-comparison"
            >
                <i class="fa fa-save tlp-button-icon"></i>
                <translate>Save</translate>
            </button>
        </div>
    </h2>
</template>

<script>
import SaveComparisonModal from "./SaveComparisonModal.vue";
export default {
    name: "TransientComparisonLabel",

    props: {
        base_baseline_id: { required: true, type: Number },
        compared_to_baseline_id: { required: true, type: Number },
    },

    methods: {
        openSaveModal() {
            this.$store.commit("dialog_interface/showModal", {
                title: this.$gettext("Save comparison"),
                component: SaveComparisonModal,
                props: {
                    base_baseline_id: this.base_baseline_id,
                    compared_to_baseline_id: this.compared_to_baseline_id,
                },
            });
        },
    },
};
</script>

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
    <modal v-bind:title="title">
        <form>
            <div class="tlp-modal-body">
                <h2 v-translate class="tlp-modal-subtitle">
                    Please choose two baselines to compare
                </h2>

                <div
                    class="tlp-form-element new-comparison-modal-baselines-list-scrollbar"
                    v-bind:class="{ 'tlp-form-element-error': is_selection_count_exceed_limit }"
                >
                    <label
                        v-for="baseline in baselines"
                        v-bind:key="baseline.id"
                        class="tlp-label tlp-checkbox"
                    >
                        <input
                            type="checkbox"
                            name="baseline"
                            v-bind:value="baseline"
                            v-model="selected_baselines"
                        >
                        {{ baseline.name }}
                    </label>
                </div>
            </div>

            <div class="tlp-modal-footer">
                <button
                    type="button"
                    class="tlp-button-primary tlp-button-outline tlp-modal-action"
                    data-dismiss="modal"
                >
                    <translate>Cancel</translate>
                </button>
                <button
                    disabled="disabled"
                    type="submit"
                    class="tlp-button-primary tlp-modal-action"
                >
                    <translate>Show comparison</translate>
                </button>
            </div>
        </form>
    </modal>
</template>

<script>
import Modal from "../common/Modal.vue";

const EXPECTED_SELECTION_COUNT = 2;

export default {
    name: "NewComparisonModal",
    components: { Modal },
    props: {
        baselines: { required: true, type: Array }
    },

    data() {
        return {
            selected_baselines: []
        };
    },

    computed: {
        title() {
            return this.$gettext("New comparison");
        },
        is_selection_count_exceed_limit() {
            return this.selected_baselines.length > EXPECTED_SELECTION_COUNT;
        }
    }
};
</script>

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
    <form v-on:submit.prevent="saveComparison()">
        <div class="tlp-modal-body">
            <div v-if="is_saving_failed" class="tlp-alert-danger" data-test-type="error-message">
                <translate>Cannot save comparison</translate>
            </div>

            <div class="tlp-form-element">
                <label class="tlp-label" for="name">
                    <translate>Name</translate>
                </label>
                <input
                    id="name"
                    v-model="name"
                    class="tlp-input"
                    type="text"
                    name="name"
                    v-bind:disabled="is_saving"
                />
            </div>

            <div class="tlp-form-element">
                <label class="tlp-label" for="comment">
                    <translate>Comment</translate>
                </label>
                <textarea
                    id="comment"
                    v-model="comment"
                    class="tlp-textarea"
                    type="text"
                    name="comment"
                    rows="5"
                    v-bind:disabled="is_saving"
                ></textarea>
            </div>
        </div>

        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-bind:disabled="is_saving"
            >
                <translate>Cancel</translate>
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="is_saving"
            >
                <i
                    v-if="is_saving"
                    data-test-type="spinner"
                    class="tlp-button-icon fa fa-fw fa-spinner fa-spin"
                ></i>
                <i v-else class="fa fa-fw fa-save tlp-button-icon"></i>
                <translate>Save comparison</translate>
            </button>
        </div>
    </form>
</template>

<script>
import { createComparison } from "../../api/rest-querier";
export default {
    name: "SaveComparisonModal",
    props: {
        base_baseline_id: { required: true, type: Number },
        compared_to_baseline_id: { required: true, type: Number },
    },

    data() {
        return {
            name: null,
            comment: null,
            is_saving: false,
            is_saving_failed: false,
        };
    },

    methods: {
        async saveComparison() {
            this.is_saving = true;
            this.is_saving_failed = false;

            try {
                const comparison = await createComparison(
                    this.name,
                    this.comment,
                    this.base_baseline_id,
                    this.compared_to_baseline_id,
                );
                this.$router.push({
                    name: "ComparisonPage",
                    params: {
                        comparison_id: comparison.id,
                    },
                });
                const notification = {
                    text: this.$gettext("The comparison was saved"),
                    class: "success",
                };
                this.$store.commit("dialog_interface/notify", notification);
                this.$store.commit("dialog_interface/hideModal");
            } catch (e) {
                this.is_saving_failed = true;
            } finally {
                this.is_saving = false;
            }
        },
    },
};
</script>

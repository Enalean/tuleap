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
    <div>
        <div class="tlp-modal-body">
            <p>
                <span v-translate>
                    You are about to delete the baseline <strong>%{ baseline.name }</strong>.
                </span>
                <br>
                <span v-translate>
                    Please confirm your action.
                </span>
            </p>
        </div>

        <div class="tlp-modal-footer">
            <button
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                type="button"
                v-bind:disabled="is_deleting"
                v-translate
            >
                Cancel
            </button>
            <button
                class="tlp-button-danger tlp-modal-action"
                type="button"
                data-test-action="confirm"
                v-bind:disabled="is_deleting"
                v-on:click="confirm()"
            >
                <i
                    class="tlp-button-icon fa fa-fw fa-spinner fa-spin"
                    data-test-type="spinner"
                    v-if="is_deleting"
                >
                </i>
                <i class="fa fa-fw fa-trash-o tlp-button-icon" v-else>
                </i>
                <translate>Delete baseline</translate>
            </button>
        </div>
    </div>
</template>

<script>
import { deleteBaseline } from "../api/rest-querier";

export default {
    name: "DeleteBaselineConfirmationModal",

    props: {
        baseline: { mandatory: true, type: Object }
    },

    data() {
        return {
            is_deleting: false
        };
    },

    computed: {
        title() {
            return this.$gettext("Delete baseline");
        }
    },

    methods: {
        async confirm() {
            this.is_deleting = true;
            try {
                await deleteBaseline(this.baseline.id);
                this.$store.commit("baselines/delete", this.baseline);
                this.$store.commit("notify", {
                    text: this.$gettext("The baseline was deleted"),
                    class: "success"
                });
                this.$store.commit("hideModal");
            } finally {
                this.is_deleting = false;
            }
        }
    }
};
</script>

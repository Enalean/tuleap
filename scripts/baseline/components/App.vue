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
  -->
<template>
    <main class="tlp-framed-vertically">
        <h1 class="tlp-framed-horizontally">
            Baselines
        </h1>
        <div class="tlp-framed-horizontally">
            <div class="tlp-alert-success"
                 data-test-type="successful-message"
                 v-if="is_baseline_created"
            >
                <translate>The baseline was created</translate>
            </div>
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <section class="tlp-pane-section baseline-section-new-baseline-button">
                        <button type="button"
                                data-target="new-baseline-modal"
                                class="tlp-button-primary"
                                v-on:click="showNewBaselineModal"
                        >
                            <i class="fa fa-plus tlp-button-icon"></i>
                            <translate>New baseline</translate>
                        </button>
                    </section>

                    <new-baseline-modal id="new-baseline-modal"
                                        ref="new_baseline_modal"
                                        v-bind:project_id="project_id"
                                        v-on:created="onBaselineCreated()"
                    />
                    <section class="tlp-pane-section baseline-section-content">
                        <h2 class="tlp-pane-subtitle"
                            v-translate
                        >
                            Page under construction
                        </h2>
                    </section>
                </div>
            </section>
        </div>
    </main>
</template>

<script>
import NewBaselineModal from "./NewBaselineModal.vue";
import { modal as createModal } from "tlp";

export default {
    name: "App",

    components: { NewBaselineModal },

    props: {
        project_id: { mandatory: true, type: Number }
    },

    data() {
        return {
            is_baseline_created: false,
            modal: null
        };
    },

    mounted() {
        this.modal = createModal(this.$refs.new_baseline_modal.$el);
    },

    methods: {
        showNewBaselineModal() {
            this.modal.show();
            this.$refs.new_baseline_modal.reload();
            this.is_baseline_created = false;
        },

        onBaselineCreated() {
            this.is_baseline_created = true;
            this.modal.hide();
        }
    }
};
</script>

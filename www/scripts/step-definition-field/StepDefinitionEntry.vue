<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <div class="ttm-definition-step">
        <div class="ttm-definition-step-rank ttm-execution-step-rank-edition">{{ dynamicRank }}</div>
        <div class="ttm-definition-step-description">
            <div v-show="is_marked_as_deleted">
                <div class="ttm-definition-step-actions">
                    <span>
                        <translate>Format:</translate>
                        <select ref="format" v-on:change="toggleRTE($event)" class="input-small ttm-definition-step-description-format" disabled>
                            <option value="text" v-bind:selected="is_text">Text</option>
                            <option value="html" v-bind:selected="! is_text">HTML</option>
                        </select>
                    </span>
                    <button
                        class="btn"
                        type="button"
                        v-on:click="unmarkDeletion()"
                    >
                        <i class="fa fa-undo"></i>
                        <translate>Undo deletion</translate>
                    </button>

                </div>
                <div class="ttm-definition-step-description-deleted">
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <div v-html="sanitized_description"
                         v-bind:class="{'ttm-definition-step-description-text': is_text}"
                    ></div>
                    <section class="ttm-definition-step-expected">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="11" viewBox="0 0 16 11" class="ttm-definition-step-expected-arrow">
                            <path fill-rule="evenodd" d="M0,9.42856359 L0,4 C-6.76353751e-17,3.44771525 0.44771525,3 1,3 C1.55228475,3 2,3.44771525 2,4 L2,9.14284897 L11.4285851,9.14284897 L11.4285851,7.14284658 C11.4285851,7.02677502 11.4910851,6.92856061 11.5982281,6.8839177 C11.7053711,6.83927479 11.8214427,6.85713196 11.9107285,6.92856061 L15.339304,10.0535643 C15.3928755,10.1071358 15.4285898,10.1785645 15.4285898,10.2589217 C15.4285898,10.339279 15.3928755,10.4196362 15.339304,10.4732077 L11.9107285,13.6339258 C11.8214427,13.714283 11.7053711,13.7321402 11.5982281,13.6874972 C11.5000137,13.6428543 11.4285851,13.5446399 11.4285851,13.4285684 L11.4285851,11.428566 L0.285714626,11.428566 C0.125000149,11.428566 0,11.3035658 0,11.1428514 L0,9.42856359 Z" transform="translate(0 -3)" />
                        </svg>
                        <div class="ttm-definition-step-expected-edit">
                            <div class="ttm-definition-step-expected-edit-title">
                                <translate>Expected results</translate>
                            </div>
                            <!-- eslint-disable-next-line vue/no-v-html -->
                            <div v-html="sanitized_expected_results"
                                 v-bind:class="{'ttm-definition-step-description-text': is_text}"
                            ></div>
                        </div>
                    </section>
                </div>
            </div>
            <div v-show="! is_marked_as_deleted">
                <input
                    type="hidden"
                    v-bind:name="'artifact[' + fieldId + '][id][]'"
                    v-bind:value="step.id"
                >
                <div class="ttm-definition-step-actions">
                    <span>
                        <translate>Format:</translate>
                        <select ref="format" v-on:change="toggleRTE($event)" class="input-small ttm-definition-step-description-format">
                            <option value="text" v-bind:selected="is_text">Text</option>
                            <option value="html" v-bind:selected="! is_text">HTML</option>
                        </select>
                    </span>
                    <button
                        class="btn"
                        type="button"
                        v-on:click="markAsDeleted()"
                    >
                        <i class="fa fa-trash-o"></i>
                        <translate>Delete</translate>
                    </button>
                </div>

                <input type="hidden" v-bind:name="'artifact[' + fieldId + '][description_format][]'" v-model="format">
                <textarea
                    ref="description"
                    class="ttm-definition-step-description-textarea"
                    v-bind:id="'field_description_' + step.uuid + '_' + fieldId"
                    v-bind:name="'artifact[' + fieldId + '][description][]'"
                    rows="3"
                    v-model="step.raw_description"
                ></textarea>

                <section class="ttm-definition-step-expected">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="11" viewBox="0 0 16 11" class="ttm-definition-step-expected-arrow">
                        <path fill-rule="evenodd" d="M0,9.42856359 L0,4 C-6.76353751e-17,3.44771525 0.44771525,3 1,3 C1.55228475,3 2,3.44771525 2,4 L2,9.14284897 L11.4285851,9.14284897 L11.4285851,7.14284658 C11.4285851,7.02677502 11.4910851,6.92856061 11.5982281,6.8839177 C11.7053711,6.83927479 11.8214427,6.85713196 11.9107285,6.92856061 L15.339304,10.0535643 C15.3928755,10.1071358 15.4285898,10.1785645 15.4285898,10.2589217 C15.4285898,10.339279 15.3928755,10.4196362 15.339304,10.4732077 L11.9107285,13.6339258 C11.8214427,13.714283 11.7053711,13.7321402 11.5982281,13.6874972 C11.5000137,13.6428543 11.4285851,13.5446399 11.4285851,13.4285684 L11.4285851,11.428566 L0.285714626,11.428566 C0.125000149,11.428566 0,11.3035658 0,11.1428514 L0,9.42856359 Z" transform="translate(0 -3)" />
                    </svg>
                    <div class="ttm-definition-step-expected-edit">
                        <div class="ttm-definition-step-expected-edit-title">
                            <translate>Expected results</translate>
                        </div>

                        <input type="hidden" v-bind:name="'artifact[' + fieldId + '][expected_results_format][]'" v-model="format">
                        <textarea
                            ref="expected_results"
                            class="ttm-definition-step-expected-results-textarea"
                            v-bind:id="'field_expected_results_' + step.uuid + '_' + fieldId"
                            v-bind:name="'artifact[' + fieldId + '][expected_results][]'"
                            rows="3"
                            v-model="step.raw_expected_results"
                        ></textarea>

                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script>
import { RTE } from "codendi";
import { sanitize } from "dompurify";

export default {
    name: "StepDefinitionEntry",
    props: {
        step: Object,
        dynamicRank: Number,
        fieldId: Number,
        deleteStep: Function
    },
    data() {
        return {
            is_marked_as_deleted: false,
            format: "text",
            editors: []
        };
    },
    computed: {
        sanitized_description() {
            return sanitize(this.step.raw_description);
        },
        sanitized_expected_results() {
            return sanitize(this.step.raw_expected_results);
        },
        is_text() {
            return this.format === "text";
        }
    },
    mounted() {
        this.format = this.step.description_format;
        this.editors = [this.loadRTE("expected_results"), this.loadRTE("description")];
        this.removeDeletedStepsOnFormSubmission();
    },
    methods: {
        toggleRTE(event) {
            this.format = this.$refs.format.value;

            for (const editor of this.editors) {
                editor.toggle(event, this.format);
            }
        },
        loadRTE(field) {
            const element = this.$refs[field];
            const is_html = !this.is_text;
            const editor = new RTE(element, {
                toggle: true,
                default_in_html: false,
                id: element.id,
                htmlFormat: is_html,
                autoresize_when_ready: false
            });

            if (is_html) {
                editor.init_rte();
            }

            return editor;
        },
        markAsDeleted() {
            if (this.step.raw_description.length === 0) {
                this.deleteStep(this.step);
            } else {
                this.is_marked_as_deleted = true;
            }
        },
        unmarkDeletion() {
            this.is_marked_as_deleted = false;
        },
        removeDeletedStepsOnFormSubmission() {
            const form = this.$refs.description.form;
            form.addEventListener("submit", () => {
                if (this.is_marked_as_deleted) {
                    this.deleteStep(this.step);
                }
            });
        }
    }
};
</script>

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
        <div class="ttm-definition-step-rank">{{ dynamicRank }}</div>
        <div class="ttm-definition-step-description">
            <div class="ttm-definition-step-description-deleted" v-show="is_marked_as_deleted">
                <button
                    class="btn ttm-definition-step-description-delete"
                    type="button"
                    v-on:click="unmarkDeletion()"
                >
                    <i class="icon-undo"></i>
                    <translate>Undo deletion</translate>
                </button>
                <div v-html="sanitized_description"
                     v-bind:class="{'ttm-definition-step-description-text': is_description_format_text}"
                ></div>
            </div>
            <div v-show="! is_marked_as_deleted">
                <input
                        type="hidden"
                        v-bind:name="'artifact[' + fieldId + '][id][]'"
                        v-bind:value="step.id">
                <button
                        class="btn ttm-definition-step-description-delete"
                        type="button"
                        v-on:click="markAsDeleted()"
                >
                    <i class="icon-trash"></i>
                    <translate>Delete</translate>
                </button>
                <div>
                    <textarea
                        ref="description"
                        class="ttm-definition-step-description-textarea"
                        v-bind:id="'field_new_description_' + step.uuid + '_' + fieldId"
                        v-bind:name="'artifact[' + fieldId + '][description][]'"
                        rows="4"
                    >{{ step.raw_description }}</textarea>
                </div>
                <div>
                    <h3 v-translate>Expected results</h3>
                    <textarea
                        ref="expected_results"
                        class="ttm-definition-step-expected-results-textarea"
                        v-bind:id="'field_new_expected_results_' + step.uuid + '_' + fieldId"
                        v-bind:name="'artifact[' + fieldId + '][expected_results][]'"
                        rows="4"
                    >{{ step.raw_expected_results }}</textarea>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import {textarea} from 'tuleap';
    import {sanitize} from 'dompurify';

    export default {
        name: "StepDefinitionEntry",
        data() {
            return {
                is_marked_as_deleted: false
            }
        },
        props: {
            step: Object,
            dynamicRank: Number,
            fieldId: Number,
            deleteStep: Function
        },
        mounted() {
            this.loadRTE('description');
            this.loadRTE('expected_results');
            this.removeDeletedStepsOnFormSubmission();
        },
        computed: {
            sanitized_description() {
                return sanitize(this.step.raw_description);
            },
            is_description_format_text() {
                return this.step.description_format === 'text';
            }
        },
        methods: {
            loadRTE(field) {
                const element    = this.$refs[field];
                const format_key = field + '_format';

                new textarea.RTE(
                    element,
                    {
                        toggle: true,
                        default_in_html: false,
                        id: element.id,
                        name: 'artifact[' + this.fieldId + '][' + format_key + '][]',
                        htmlFormat: this.step[format_key] !== 'text'
                    }
                );
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
                form.addEventListener('submit', () => {
                    if (this.is_marked_as_deleted) {
                        this.deleteStep(this.step);
                    }
                })
            }
        }
    }
</script>
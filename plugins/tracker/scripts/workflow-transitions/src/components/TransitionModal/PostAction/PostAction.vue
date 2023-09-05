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
    <div
        class="tlp-card tracker-workflow-transition-modal-action-card"
        data-test="post-action-action-card"
    >
        <div class="tlp-form-element tracker-workflow-transition-modal-action-type">
            <select
                class="tlp-select"
                v-model="post_action_type"
                v-bind:disabled="is_modal_save_running"
                data-test="post-action-type-select"
            >
                <optgroup v-bind:label="unique_actions_title">
                    <option
                        v-bind:value="POST_ACTION_TYPE.FROZEN_FIELDS"
                        data-test="freeze_fields"
                        v-bind:disabled="!frozen_fields_information.valid"
                        v-bind:title="frozen_fields_information.title"
                    >
                        {{ frozen_fields_information.option }}
                    </option>
                    <option
                        v-bind:value="POST_ACTION_TYPE.HIDDEN_FIELDSETS"
                        data-test="hide_fieldsets"
                        v-bind:disabled="!hidden_fieldsets_information.valid"
                        v-bind:title="hidden_fieldsets_information.title"
                    >
                        {{ hidden_fieldsets_information.option }}
                    </option>
                    <add-to-backlog-agile-dashboard-post-action-option
                        v-bind:post_action_type="post_action_type"
                    />
                    <add-to-backlog-program-management-post-action-option
                        v-bind:post_action_type="post_action_type"
                    />
                </optgroup>
                <optgroup v-bind:label="other_actions_title">
                    <option v-bind:value="POST_ACTION_TYPE.RUN_JOB" v-translate>
                        Launch a CI job
                    </option>
                    <option
                        v-bind:value="POST_ACTION_TYPE.SET_FIELD_VALUE"
                        data-test="set_field"
                        v-bind:disabled="!set_field_value_information.valid"
                        v-bind:title="set_field_value_information.title"
                    >
                        {{ set_field_value_information.option }}
                    </option>
                </optgroup>
            </select>
            <a
                href="#"
                class="tracker-workflow-transition-modal-action-remove"
                v-on:click.prevent="deletePostAction()"
                v-bind:title="delete_title"
            >
                <i class="far fa-trash-alt"></i>
            </a>
        </div>
        <div class="tracker-workflow-transition-modal-action-details">
            <slot />
        </div>
    </div>
</template>
<script>
import { POST_ACTION_TYPE } from "../../../constants/workflow-constants.js";
import {
    CONTAINER_FIELDSET,
    DATE_FIELD,
    FLOAT_FIELD,
    INT_FIELD,
    READ_ONLY_FIELDS,
    STRUCTURAL_FIELDS,
} from "@tuleap/plugin-tracker-constants";
import { mapGetters, mapState } from "vuex";
import { compare } from "../../../support/string.js";
import AddToBacklogAgileDashboardPostActionOption from "../Externals/AddToBacklogAgileDashboardPostActionOption.vue";
import AddToBacklogProgramManagementPostActionOption from "../Externals/AddToBacklogProgramManagementPostActionOption.vue";

export default {
    name: "PostAction",
    components: {
        AddToBacklogProgramManagementPostActionOption,
        AddToBacklogAgileDashboardPostActionOption,
    },
    props: {
        post_action: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            POST_ACTION_TYPE,
        };
    },
    computed: {
        ...mapGetters("transitionModal", ["post_actions", "set_value_action_fields"]),
        ...mapState("transitionModal", ["is_modal_save_running"]),
        ...mapGetters(["current_workflow_field", "is_workflow_advanced"]),
        ...mapState({
            freezable_fields(state) {
                const fields_blacklist = [...STRUCTURAL_FIELDS, ...READ_ONLY_FIELDS];

                if (state.current_tracker === null) {
                    return [];
                }
                return state.current_tracker.fields
                    .filter((field) => !fields_blacklist.includes(field.type))
                    .filter((field) => !(field.field_id === this.current_workflow_field.field_id))
                    .sort((field1, field2) => compare(field1.label, field2.label));
            },
            hidable_fieldsets(state) {
                if (state.current_tracker === null) {
                    return [];
                }
                return state.current_tracker.fields
                    .filter((field) => field.type === CONTAINER_FIELDSET)
                    .sort((field1, field2) => compare(field1.label, field2.label));
            },
        }),
        frozen_fields_information() {
            if (this.frozen_fields_is_valid) {
                return {
                    valid: true,
                    option: this.$gettext("Freeze fields"),
                    title: "",
                };
            }
            if (this.is_workflow_advanced) {
                return {
                    valid: false,
                    option: this.$gettext("Freeze fields (incompatible)"),
                    title: this.$gettext(
                        "Advanced configuration is incompatible with this post-action",
                    ),
                };
            }

            if (this.there_are_no_applicable_fields_for_frozen_fields) {
                return {
                    valid: false,
                    option: this.$gettext("Freeze fields (incompatible)"),
                    title: this.$gettext(
                        "Your tracker doesn't seem to have available writable fields",
                    ),
                };
            }

            return {
                valid: false,
                option: this.$gettext("Freeze fields (already used)"),
                title: this.$gettext("You can only have this post-action once."),
            };
        },
        hidden_fieldsets_information() {
            if (this.hidden_fieldsets_is_valid) {
                return {
                    valid: true,
                    option: this.$gettext("Hide fieldsets"),
                    title: "",
                };
            }
            if (this.is_workflow_advanced) {
                return {
                    valid: false,
                    option: this.$gettext("Hide fieldsets (incompatible)"),
                    title: this.$gettext(
                        "Advanced configuration is incompatible with this post-action",
                    ),
                };
            }

            if (this.there_are_no_applicable_fields_for_frozen_fields) {
                return {
                    valid: false,
                    option: this.$gettext("Hide fieldsets (incompatible)"),
                    title: this.$gettext("Your tracker doesn't seem to have available fieldsets"),
                };
            }

            return {
                valid: false,
                option: this.$gettext("Hide fieldsets (already used)"),
                title: this.$gettext("You can only have this post-action once."),
            };
        },
        delete_title() {
            return this.$gettext("Delete this action");
        },
        unique_actions_title() {
            return this.$gettext("Unique actions");
        },
        other_actions_title() {
            return this.$gettext("Other actions");
        },
        set_field_value_information() {
            if (this.set_field_value_is_valid) {
                return {
                    valid: true,
                    option: this.$gettext("Change the value of a field"),
                    title: "",
                };
            }

            return {
                valid: false,
                option: this.$gettext("Change the value of a field (incompatible)"),
                title: this.$gettext(
                    "Your tracker doesn't seem to have integer, float or date fields.",
                ),
            };
        },
        set_field_value_is_valid() {
            const applicable_fields = this.available_fields_for_set_value.filter(({ type }) => {
                return type === DATE_FIELD || type === INT_FIELD || type === FLOAT_FIELD;
            });
            return applicable_fields.length > 0;
        },
        available_fields_for_set_value() {
            // Side effect is prevented with array duplication before sort
            return [...this.set_value_action_fields].sort((field1, field2) =>
                compare(field1.label, field2.label),
            );
        },
        frozen_fields_is_valid() {
            return (
                !this.is_workflow_advanced &&
                (this.post_action_type === this.POST_ACTION_TYPE.FROZEN_FIELDS ||
                    !(
                        this.there_are_no_applicable_fields_for_frozen_fields ||
                        this.frozen_fields_is_already_present
                    ))
            );
        },
        frozen_fields_is_already_present() {
            return (
                this.post_actions.filter(
                    (post_action) => post_action.type === this.POST_ACTION_TYPE.FROZEN_FIELDS,
                ).length > 0
            );
        },
        there_are_no_applicable_fields_for_frozen_fields() {
            return this.freezable_fields.length === 0;
        },
        hidden_fieldsets_is_valid() {
            return (
                !this.is_workflow_advanced &&
                (this.post_action_type === this.POST_ACTION_TYPE.HIDDEN_FIELDSETS ||
                    !(
                        this.there_are_no_applicable_fieldsets_for_hidden_fieldsets ||
                        this.hidden_fieldsets_is_already_present
                    ))
            );
        },
        hidden_fieldsets_is_already_present() {
            return (
                this.post_actions.filter(
                    (post_action) => post_action.type === this.POST_ACTION_TYPE.HIDDEN_FIELDSETS,
                ).length > 0
            );
        },
        there_are_no_applicable_fieldsets_for_hidden_fieldsets() {
            return this.hidable_fieldsets.length === 0;
        },
        post_action_type: {
            get() {
                return this.post_action.type;
            },
            set(type) {
                this.$store.commit("transitionModal/updatePostActionType", {
                    post_action: this.post_action,
                    type,
                });
            },
        },
    },
    methods: {
        deletePostAction() {
            if (this.is_modal_save_running) {
                return;
            }
            this.$store.commit("transitionModal/deletePostAction", this.post_action);
        },
    },
};
</script>

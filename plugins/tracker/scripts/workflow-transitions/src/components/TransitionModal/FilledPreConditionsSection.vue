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
    <pre-conditions-section
        v-bind:is_transition_from_new_artifact="is_transition_from_new_artifact"
    >
        <template slot="authorized-ugroups">
            <label for="workflow-configuration-permission" class="tlp-label">
                {{ $gettext("Groups that may process the transition") }}
            </label>
            <select
                id="workflow-configuration-permission"
                multiple
                class="tracker-workflow-transition-modal-authorized-ugroups"
                v-bind:disabled="is_modal_save_running"
                v-model="authorized_user_group_ids"
                data-test="authorized-ugroups-select"
                required
                ref="workflow_configuration_permission"
                v-on:change="updateAuthorizedUserGroupIds"
            >
                <option
                    v-for="user_group in user_groups"
                    v-bind:key="user_group.id"
                    v-bind:value="user_group.id"
                >
                    {{ user_group.label }}
                </option>
            </select>
        </template>
        <template slot="fields-not-empty">
            <label for="workflow-configuration-not-empty-fields" class="tlp-label">
                {{ $gettext("Field(s) that must not be empty") }}
            </label>

            <select
                id="workflow-configuration-not-empty-fields"
                multiple
                v-model="not_empty_field_ids"
                v-bind:disabled="is_modal_save_running"
                data-test="not-empty-field-select"
                ref="workflow_configuration_not_empty_fields"
                v-on:change="updateNotEmptyFieldIds"
            >
                <option
                    v-for="field in writable_fields"
                    v-bind:key="field.field_id"
                    v-bind:value="field.field_id"
                >
                    {{ field.label }}
                </option>
            </select>
        </template>
        <label
            for="workflow-configuration-not-empty-comment"
            class="tlp-label tlp-checkbox"
            slot="comment-not-empty"
        >
            <input
                id="workflow-configuration-not-empty-comment"
                type="checkbox"
                name="transition-comment-not-empty"
                v-model="transition_comment_not_empty"
                v-bind:disabled="is_modal_save_running"
                data-test="not-empty-comment-checkbox"
            />
            <span>{{ $gettext("Comment must not be empty") }}</span>
        </label>
    </pre-conditions-section>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import {
    COMPUTED_FIELD,
    READ_ONLY_FIELDS,
    STRUCTURAL_FIELDS,
} from "@tuleap/plugin-tracker-constants";
import PreConditionsSection from "./PreConditionsSection.vue";
import { compare } from "../../support/string.js";
import { createListPicker } from "@tuleap/list-picker";

const fields_blacklist = [...STRUCTURAL_FIELDS, ...READ_ONLY_FIELDS, COMPUTED_FIELD];

export default {
    name: "FilledPreConditionsSection",
    components: { PreConditionsSection },
    data() {
        return {
            authorized_user_group_ids: [],
            not_empty_field_ids: [],
            configuration_permission_list_picker: null,
            not_empty_fields_list_picker: null,
        };
    },
    computed: {
        ...mapState("transitionModal", [
            "current_transition",
            "user_groups",
            "is_modal_save_running",
        ]),
        ...mapGetters("transitionModal", ["is_transition_from_new_artifact"]),
        ...mapState({
            writable_fields: (state) => {
                if (state.current_tracker === null) {
                    return [];
                }
                return state.current_tracker.fields
                    .filter((field) => !fields_blacklist.includes(field.type))
                    .sort((field1, field2) => compare(field1.label, field2.label));
            },
        }),
        transition_comment_not_empty: {
            get() {
                if (!this.current_transition) {
                    return false;
                }
                return this.current_transition.is_comment_required;
            },
            set(value) {
                this.$store.commit("transitionModal/updateIsCommentRequired", value);
            },
        },
    },
    mounted() {
        if (this.current_transition) {
            this.authorized_user_group_ids = this.current_transition.authorized_user_group_ids;
            this.not_empty_field_ids = this.current_transition.not_empty_field_ids;
        }
        this.configuration_permission_list_picker = createListPicker(
            this.$refs.workflow_configuration_permission,
            {
                locale: document.body.dataset.userLocale,
                is_filterable: true,
            },
        );
        this.not_empty_fields_list_picker = createListPicker(
            this.$refs.workflow_configuration_not_empty_fields,
            {
                locale: document.body.dataset.userLocale,
                is_filterable: true,
                placeholder: this.$gettext("Choose a field"),
            },
        );
    },
    beforeDestroy() {
        this.configuration_permission_list_picker.destroy();
        this.not_empty_fields_list_picker.destroy();
    },
    methods: {
        updateAuthorizedUserGroupIds() {
            this.$store.commit(
                "transitionModal/updateAuthorizedUserGroupIds",
                this.authorized_user_group_ids,
            );
        },
        updateNotEmptyFieldIds() {
            this.$store.commit("transitionModal/updateNotEmptyFieldIds", this.not_empty_field_ids);
        },
    },
};
</script>

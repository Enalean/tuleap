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
    <pre-conditions-section
        v-bind:is_transition_from_new_artifact="is_transition_from_new_artifact"
    >
        <template slot="authorized-ugroups">
            <label for="workflow-configuration-permission" class="tlp-label" v-translate>
                Groups that may process the transition
            </label>
            <select
                id="workflow-configuration-permission"
                class="tlp-select tracker-workflow-transition-modal-authorized-ugroups"
                multiple
                v-bind:disabled="is_modal_save_running"
                v-model="authorized_user_group_ids"
                data-test="authorized-ugroups-select"
                required
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
            <label for="workflow-configuration-not-empty-fields" class="tlp-label" v-translate>
                Field(s) that must not be empty
            </label>
            <multi-select
                id="workflow-configuration-not-empty-fields"
                class="tlp-select"
                v-bind:configuration="{
                    width: '100%',
                    placeholder: $gettext('Choose a field'),
                }"
                v-model="not_empty_field_ids"
                v-bind:disabled="is_modal_save_running"
                data-test="not-empty-field-select"
            >
                <option
                    v-for="field in writable_fields"
                    v-bind:key="field.field_id"
                    v-bind:value="field.field_id"
                >
                    {{ field.label }}
                </option>
            </multi-select>
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
            <translate>Comment must not be empty</translate>
        </label>
    </pre-conditions-section>
</template>

<script>
import { mapState, mapGetters } from "vuex";
import {
    STRUCTURAL_FIELDS,
    READ_ONLY_FIELDS,
    COMPUTED_FIELD,
} from "../../../../constants/fields-constants.js";
import MultiSelect from "./MultiSelect.vue";
import PreConditionsSection from "./PreConditionsSection.vue";
import { compare } from "../../support/string.js";

const fields_blacklist = [...STRUCTURAL_FIELDS, ...READ_ONLY_FIELDS, COMPUTED_FIELD];

export default {
    name: "FilledPreConditionsSection",
    components: { PreConditionsSection, MultiSelect },
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
        authorized_user_group_ids: {
            get() {
                if (!this.current_transition) {
                    return [];
                }
                return this.current_transition.authorized_user_group_ids;
            },
            set(value) {
                this.$store.commit("transitionModal/updateAuthorizedUserGroupIds", value);
            },
        },
        not_empty_field_ids: {
            get() {
                if (!this.current_transition) {
                    return [];
                }
                return this.current_transition.not_empty_field_ids;
            },
            set(value) {
                this.$store.commit("transitionModal/updateNotEmptyFieldIds", value);
            },
        },
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
};
</script>

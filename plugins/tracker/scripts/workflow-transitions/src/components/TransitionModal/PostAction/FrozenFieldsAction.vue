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
    <post-action v-bind:post_action="post_action">
        <div
            class="tracker-workflow-transition-modal-action-details-element tlp-form-element"
            data-test="frozen-fields-form-element"
        >
            <label for="workflow-transition-modal-frozen-fields" class="tlp-label">
                {{ $gettext("Fields that will be frozen") }}
                <i class="fa fa-asterisk"></i>
            </label>
            <select
                id="workflow-transition-modal-frozen-fields"
                multiple
                required
                v-model="frozen_field_ids"
                v-bind:disabled="is_modal_save_running"
                data-test="frozen-fields-selector"
                ref="workflow_transition_modal_frozen_fields"
                v-on:change="updateFrozenFieldsPostActionFieldIds"
            >
                <option
                    v-for="field in writable_fields"
                    v-bind:key="field.field_id"
                    v-bind:value="field.field_id"
                    v-bind:data-test="`field_${field.field_id}`"
                >
                    {{ field.label }}
                </option>
            </select>
        </div>
    </post-action>
</template>
<script>
import PostAction from "./PostAction.vue";
import { mapGetters, mapState } from "vuex";
import { READ_ONLY_FIELDS, STRUCTURAL_FIELDS } from "@tuleap/plugin-tracker-constants";
import { compare } from "../../../support/string.js";
import { createListPicker } from "@tuleap/list-picker";

const fields_blacklist = [...STRUCTURAL_FIELDS, ...READ_ONLY_FIELDS];

export default {
    name: "FrozenFieldsAction",
    components: { PostAction },
    props: {
        post_action: {
            type: Object,
            mandatory: true,
        },
    },
    data() {
        return {
            frozen_field_ids: [],
            list_picker: null,
        };
    },
    computed: {
        ...mapState(["current_tracker"]),
        ...mapState("transitionModal", ["current_transition", "is_modal_save_running"]),
        ...mapGetters(["current_workflow_field"]),
        ...mapState({
            writable_fields(state) {
                if (state.current_tracker === null) {
                    return [];
                }
                return state.current_tracker.fields
                    .filter((field) => !fields_blacklist.includes(field.type))
                    .filter((field) => !(field.field_id === this.current_workflow_field.field_id))
                    .sort((field1, field2) => compare(field1.label, field2.label));
            },
        }),
    },
    mounted() {
        this.frozen_field_ids = this.post_action.field_ids;
        this.list_picker = createListPicker(this.$refs.workflow_transition_modal_frozen_fields, {
            locale: document.body.dataset.userLocale,
            is_filterable: true,
            placeholder: this.$gettext("Choose a field"),
        });
    },
    beforeDestroy() {
        this.list_picker.destroy();
    },
    methods: {
        updateFrozenFieldsPostActionFieldIds() {
            this.$store.commit("transitionModal/updateFrozenFieldsPostActionFieldIds", {
                post_action: this.post_action,
                field_ids: this.frozen_field_ids,
            });
        },
    },
};
</script>

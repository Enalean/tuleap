<!--
  - Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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
    <div class="tlp-form-element">
        <label for="workflow-configuration-permission" class="tlp-label">
            {{ $gettext("Groups that may process the transition") }}
        </label>
        <select
            id="workflow-configuration-permission"
            multiple
            class="tracker-workflow-transition-modal-authorized-ugroups"
            v-bind:disabled="is_modal_save_running"
            v-on:change="updateUGroups"
            data-test="authorized-ugroups-select"
            required
            ref="workflow_configuration_permission"
        >
            <option
                v-for="user_group in user_groups"
                v-bind:key="user_group.id"
                v-bind:value="user_group.id"
                v-bind:selected="authorized_user_group_ids.includes(user_group.id)"
            >
                {{ user_group.label }}
            </option>
        </select>
    </div>
</template>

<script>
import { defineComponent } from "vue";
import { mapState } from "vuex";
import { createListPicker } from "@tuleap/list-picker";

export default defineComponent({
    name: "AuthorizedUGroupsSelect",
    data() {
        return {
            configuration_permission_list_picker: null,
        };
    },
    computed: {
        ...mapState("transitionModal", [
            "current_transition",
            "user_groups",
            "is_modal_save_running",
        ]),
        authorized_user_group_ids() {
            if (!this.current_transition) {
                return [];
            }
            return this.current_transition.authorized_user_group_ids;
        },
    },
    mounted() {
        this.configuration_permission_list_picker = createListPicker(
            this.$refs.workflow_configuration_permission,
            {
                locale: document.body.dataset.userLocale,
                is_filterable: true,
            },
        );
    },
    beforeUnmount() {
        this.configuration_permission_list_picker.destroy();
    },
    methods: {
        updateAuthorizedUserGroupIds(authorized_user_group_ids) {
            this.$store.commit(
                "transitionModal/updateAuthorizedUserGroupIds",
                authorized_user_group_ids,
            );
        },
        updateUGroups(event) {
            const select = event.target;
            const selected_option = Array.from(select.options).filter((option) => {
                return option.selected;
            });
            const values = selected_option.map((option) => {
                return option.value;
            });

            this.updateAuthorizedUserGroupIds(values);
        },
    },
});
</script>

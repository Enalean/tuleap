<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div
        v-if="fully_migrated_fields_count > 0"
        class="alert alert-info"
        data-test="dry-run-message-info"
    >
        <i class="fa fa-info-circle move-artifact-icon"></i>
        <translate
            v-bind:translate-n="fully_migrated_fields_count"
            translate-plural="%{ fully_migrated_fields_count } fields will be fully migrated:"
        >
            1 field will be fully migrated:
        </translate>
        <fields-list-displayer
            v-bind:fields="fully_migrated_fields"
            v-bind:type="'fully-migrated'"
        />
    </div>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import FieldsListDisplayer from "./FieldsListDisplayer.vue";

export default {
    name: "DryRunFullyMigratedFieldState",
    components: {
        FieldsListDisplayer,
    },
    computed: {
        ...mapState({
            fully_migrated_fields: (state) => state.dry_run_fields.fields_migrated,
        }),
        ...mapGetters(["fully_migrated_fields_count"]),
    },
};
</script>

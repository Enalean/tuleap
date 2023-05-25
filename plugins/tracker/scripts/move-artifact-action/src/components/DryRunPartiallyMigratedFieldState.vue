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
        v-if="partially_migrated_fields_count > 0"
        class="alert block"
        data-test="dry-run-message-warning"
    >
        <i class="fa fa-exclamation-circle move-artifact-icon"></i>
        <translate
            v-bind:translate-n="partially_migrated_fields_count"
            translate-plural="%{ partially_migrated_fields_count } fields do not fully match with the targeted tracker. One value of the fields has not been found in targeted tracker, if you confirm your action, this value will be lost forever:"
        >
            1 field does not fully match with the targeted tracker. One value of the field has not
            been found in targeted tracker, if you confirm your action, this value will be lost
            forever:
        </translate>
        <field-error-message
            v-bind:fields="partially_migrated_fields"
            v-bind:type="'partially-migrated'"
        />
    </div>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import FieldErrorMessage from "./FieldErrorMessage.vue";

export default {
    name: "DryRunPartiallyMigratedFieldState",
    components: {
        FieldErrorMessage,
    },
    computed: {
        ...mapState({
            partially_migrated_fields: (state) => state.dry_run_fields.fields_partially_migrated,
        }),
        ...mapGetters(["partially_migrated_fields_count"]),
    },
};
</script>

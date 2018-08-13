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
    <div v-if="getCountOfFullyMigratedField > 0" class="alert alert-info">
        <i class="icon-info-sign move-artifact-error-icon"></i>
        <translate v-bind:translate-n="getCountOfFullyMigratedField"
                   translate-plural="%{ getCountOfFullyMigratedField } fields will be fully migrated:"
        >%{ getCountOfFullyMigratedField } field will be fully migrated:</translate>
        <field-error-message v-bind:fields="getFullyMigratedFields" v-bind:type="'fully-migrated'" />
    </div>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import FieldErrorMessage from "./FieldErrorMessage.vue";

export default {
    name: "DryRunFullyMigratedFieldState",
    components: {
        FieldErrorMessage
    },
    computed: {
        ...mapState({
            getFullyMigratedFields: state => state.dry_run_fields.fields_migrated
        }),
        ...mapGetters(["getCountOfFullyMigratedField"])
    }
};
</script>

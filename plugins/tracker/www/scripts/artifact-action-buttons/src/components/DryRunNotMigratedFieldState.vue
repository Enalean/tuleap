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
    <div v-if="getCountOfNotMigratedField > 0" class="alert alert-error">
        <i class="icon-exclamation-sign move-artifact-icon move-artifact-error-icon"></i>
        <translate v-bind:translate-n="getCountOfNotMigratedField"
                   translate-plural="%{ getCountOfNotMigratedField } fields do not match with the targeted tracker. If you confirm your action, their values will be lost forever:"
        >%{ getCountOfNotMigratedField } field do not match with the targeted tracker. If you confirm your action, its value will be lost forever:</translate>
        <field-error-message v-bind:fields="getNotMigratedFields" v-bind:type="'not-migrated'" />
    </div>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import FieldErrorMessage from "./FieldErrorMessage.vue";

export default {
    name: "DryRunNotMigratedFieldState",
    components: {
        FieldErrorMessage
    },
    computed: {
        ...mapState({
            getNotMigratedFields: state => state.dry_run_fields.fields_not_migrated
        }),
        ...mapGetters(["getCountOfNotMigratedField"])
    }
};
</script>

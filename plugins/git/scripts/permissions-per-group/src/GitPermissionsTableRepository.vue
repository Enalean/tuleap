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
    <tbody>
        <git-repository-table-simple-permissions
            v-if="!repository.has_fined_grained_permissions && !is_hidden"
            v-bind:repository-permission="repository"
        />

        <template v-if="repository.has_fined_grained_permissions && !is_hidden">
            <git-repository-table-fine-grained-permissions-repository
                v-bind:repository-permission="repository"
            />

            <git-repository-table-fine-grained-permission
                v-for="fined_grained_permission in repository.fine_grained_permission"
                v-bind:key="fined_grained_permission.id"
                v-bind:fine-grained-permissions="fined_grained_permission"
                v-bind:data-test="`git-repository-fine-grained-permission-${fined_grained_permission.id}`"
            />
        </template>
    </tbody>
</template>

<script lang="ts">
import GitRepositoryTableSimplePermissions from "./GitRepositoryTableSimplePermissions.vue";
import GitRepositoryTableFineGrainedPermissionsRepository from "./GitRepositoryTableFineGrainedPermissionsRepository.vue";
import GitRepositoryTableFineGrainedPermission from "./GitRepositoryTableFineGrainedPermission.vue";
import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import type { RepositoryFineGrainedPermissions, RepositorySimplePermissions } from "./type";

@Component({
    components: {
        GitRepositoryTableSimplePermissions,
        GitRepositoryTableFineGrainedPermissionsRepository,
        GitRepositoryTableFineGrainedPermission,
    },
})
export default class GitPermissionsTableRepository extends Vue {
    @Prop()
    readonly repository!: RepositoryFineGrainedPermissions | RepositorySimplePermissions;
    @Prop()
    readonly filter!: string;

    is_hidden = false;

    @Watch("filter")
    filter_value(new_value: string) {
        if (!new_value || this.repository.name.includes(new_value)) {
            this.is_hidden = false;
            this.$emit("filtered", { hidden: this.is_hidden });
            return;
        }

        this.is_hidden = true;
        this.$emit("filtered", { hidden: this.is_hidden });
    }
}
</script>

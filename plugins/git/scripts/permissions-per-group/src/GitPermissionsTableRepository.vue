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
            v-bind:repository_permission="repository"
        />

        <template v-if="repository.has_fined_grained_permissions && !is_hidden">
            <git-repository-table-fine-grained-permissions-repository
                v-bind:repository_permission="repository"
            />

            <git-repository-table-fine-grained-permission
                v-for="fined_grained_permission in repository.fine_grained_permission"
                v-bind:key="fined_grained_permission.id"
                v-bind:fine_grained_permissions="fined_grained_permission"
                v-bind:data-test="`git-repository-fine-grained-permission-${fined_grained_permission.id}`"
            />
        </template>
    </tbody>
</template>

<script setup lang="ts">
import GitRepositoryTableSimplePermissions from "./GitRepositoryTableSimplePermissions.vue";
import GitRepositoryTableFineGrainedPermissionsRepository from "./GitRepositoryTableFineGrainedPermissionsRepository.vue";
import GitRepositoryTableFineGrainedPermission from "./GitRepositoryTableFineGrainedPermission.vue";
import { ref, watch } from "vue";
import type { RepositoryFineGrainedPermissions, RepositorySimplePermissions } from "./type";

const props = defineProps<{
    repository: RepositoryFineGrainedPermissions | RepositorySimplePermissions;
    filter: string;
}>();

const is_hidden = ref(false);
const emit = defineEmits<{
    filtered: [{ hidden: boolean }];
}>();

watch(
    () => props.filter,
    (new_value: string) => {
        if (!new_value || props.repository.name.includes(new_value)) {
            is_hidden.value = false;
            emit("filtered", { hidden: is_hidden.value });
            return;
        }

        is_hidden.value = true;
        emit("filtered", { hidden: is_hidden.value });
    },
);
</script>

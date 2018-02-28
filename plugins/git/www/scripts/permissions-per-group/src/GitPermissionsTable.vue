/**
* Copyright (c) Enalean, 2018. All Rights Reserved.
*
* This file is a part of Tuleap.
*
* Tuleap is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* Tuleap is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
*/

(
<template>
    <table class="tlp-table permission-per-group-table">
        <thead>
        <tr class="permission-per-group-sextuple-column-table">
            <th>{{ repository_label  }}</th>
            <th>{{ branch_label  }}</th>
            <th>{{ tag_label  }}</th>
            <th>{{ readers_label  }}</th>
            <th>{{ writers_label  }}</th>
            <th>{{ rewinders_label  }}</th>
        </tr>
        </thead>

        <tbody v-if="isEmpty">
        <tr>
            <td colspan="6" v-if="hasASelectedUGroup" class="tlp-table-cell-empty">
                {{ empty_state }}
            </td>
            <td colspan="6" v-else class="tlp-table-cell-empty">
                {{ ugroup_empty_state }}
            </td>
        </tr>
        </tbody>

        <tbody>
            <template v-for="permission in repositoryPermissions">
                <git-repository-table-simple-permissions v-if="! permission.has_fined_grained_permissions"
                                                         v-bind:repository-permission="permission"/>

                <git-repository-table-fine-grained-permissions-repository v-if="permission.has_fined_grained_permissions"
                                                                            v-bind:repository-permission="permission"/>

                <git-repository-table-fine-grained-permission v-if="permission.has_fined_grained_permissions"
                    v-for="fined_grained_permission in permission.fine_grained_permission"
                    v-bind:fine-grained-permissions="fined_grained_permission"/>
            </template>
        </tbody>
    </table>
</template>
)
(
<script>
    import GitRepositoryTableSimplePermissions                 from './GitRepositoryTableSimplePermissions.vue';
    import GitRepositoryTableFineGrainedPermissionsRepository  from './GitRepositoryTableFineGrainedPermissionsRepository.vue';
    import GitRepositoryTableFineGrainedPermission             from './GitRepositoryTableFineGrainedPermission.vue';
    import { gettext_provider }                                from './gettext-provider.js';
    import { sprintf }                                         from 'sprintf-js';

    export default {
        name: 'GitPermissionsTable',
        components: {
            GitRepositoryTableSimplePermissions,
            GitRepositoryTableFineGrainedPermissionsRepository,
            GitRepositoryTableFineGrainedPermission
        },
        props: {
            repositoryPermissions: Array,
            selectedUgroupName   : String
        },
        computed: {
            empty_state: ()          => gettext_provider.gettext("No repository found for project"),
            repository_label: ()     => gettext_provider.gettext("Repository"),
            branch_label: ()         => gettext_provider.gettext("Branch"),
            tag_label: ()            => gettext_provider.gettext("Tag"),
            readers_label: ()        => gettext_provider.gettext("Readers"),
            writers_label: ()        => gettext_provider.gettext("Writers"),
            rewinders_label: ()      => gettext_provider.gettext("Rewinders"),
            ugroup_empty_state()   {
                return sprintf(
                    gettext_provider.gettext("%s has no permission for any repository in this project"),
                    this.selectedUgroupName
                );
            },
            isEmpty()              { return this.repositoryPermissions.length === 0; },
            hasASelectedUGroup()   { return this.selectedUgroupName === '' },
        }
    };
</script>)

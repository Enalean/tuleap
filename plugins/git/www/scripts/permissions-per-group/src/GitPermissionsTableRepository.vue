(
<template>
    <tbody>
        <git-repository-table-simple-permissions v-if="! repository.has_fined_grained_permissions && ! is_hidden"
             v-bind:repositoryPermission="repository"
        />

        <template v-if="repository.has_fined_grained_permissions && ! is_hidden">
            <git-repository-table-fine-grained-permissions-repository
                v-bind:repository-permission="repository"
            />

            <git-repository-table-fine-grained-permission
                v-for="fined_grained_permission in repository.fine_grained_permission"
                v-bind:key="fined_grained_permission.id"
                v-bind:fine-grained-permissions="fined_grained_permission"
            />
        </template>
    </tbody>
</template>
)
(
<script>
import GitRepositoryTableSimplePermissions from "./GitRepositoryTableSimplePermissions.vue";
import GitRepositoryTableFineGrainedPermissionsRepository from "./GitRepositoryTableFineGrainedPermissionsRepository.vue";
import GitRepositoryTableFineGrainedPermission from "./GitRepositoryTableFineGrainedPermission.vue";

export default {
    name: "GitPermissionsTableGlobal",
    components: {
        GitRepositoryTableSimplePermissions,
        GitRepositoryTableFineGrainedPermissionsRepository,
        GitRepositoryTableFineGrainedPermission
    },
    data() {
        return {
            is_hidden: false
        };
    },
    props: {
        repository: Object,
        filter: String
    },
    watch: {
        filter(new_value) {
            if (!new_value || this.repository.name.includes(new_value)) {
                this.is_hidden = false;
                this.$emit("filtered", { hidden: this.is_hidden });
                return;
            }

            this.is_hidden = true;
            this.$emit("filtered", { hidden: this.is_hidden });
        }
    }
};
</script>
)

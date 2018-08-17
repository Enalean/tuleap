(<template>
    <tbody>
        <template v-for="packages in packagePermissions">
            <tr v-bind:key="packages.package_name">
                <td>
                    <a v-bind:href="packages.package_url">{{ packages.package_name }}</a>
                </td>
                <td></td>
                <td>
                    <ugroup-badge v-for="ugroup in packages.permissions"
                        v-bind:key="ugroup.ugroup_name"
                        v-bind:is-project-admin="ugroup.is_project_admin"
                        v-bind:is-static="ugroup.is_static"
                        v-bind:is-custom="ugroup.is_custom"
                        v-bind:group-name="ugroup.ugroup_name"
                    />
                </td>
            </tr>

            <release-permissions v-for="release in packages.releases"
                v-bind:key="release.release_name"
                v-bind:release="release"
            />
        </template>

        <empty-state v-if="! has_permissions" v-bind:selected-ugroup-name="selectedUgroupName" />
    </tbody>
</template>)
(<script>
import UgroupBadge from "../../project/admin/permissions-per-group/PermissionsPerGroupBadge.vue";
import EmptyState from "./FRSPackagePermissionsTablePackageEmptyState.vue";
import ReleasePermissions from "./FRSPackagePermissionsTablePackageRelease.vue";

export default {
    name: "PackagePermissions",
    props: {
        packagePermissions: Array,
        selectedUgroupName: String
    },
    components: {
        EmptyState,
        UgroupBadge,
        ReleasePermissions
    },
    computed: {
        has_permissions() {
            return this.packagePermissions.length > 0;
        }
    }
};
</script>)

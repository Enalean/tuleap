(<template>
    <tr>
        <td></td>
        <td><a v-bind:href="release.release_url">{{ release.release_name }}</a></td>
        <td>
            <ugroup-badge v-for="ugroup in release.release_permissions"
                v-bind:key="ugroup.ugroup_name"
                v-bind:is-project-admin="ugroup.is_project_admin"
                v-bind:is-static="ugroup.is_static"
                v-bind:is-custom="ugroup.is_custom"
                v-bind:group-name="ugroup.ugroup_name"
            />
        </td>
    </tr>
</template>)
(<script>
import UgroupBadge from "../../project/admin/permissions-per-group/PermissionsPerGroupBadge.vue";

export default {
    name: "ReleasePermissions",
    props: {
        release: Object
    },
    components: {
        UgroupBadge
    }
};
</script>)

(<template>
    <table class="tlp-table permission-per-group-table">
        <thead>
        <tr class="permission-per-group-triple-column-table">
            <th> {{ package_label }}</th>
            <th> {{ release_label }}</th>
            <th> {{ readers_label }}</th>
        </tr>
        </thead>

        <package-permissions v-bind="$props" />
    </table>
</template>)
(<script>
import { gettext_provider } from "./gettext-provider.js";
import PackagePermissions from "./FRSPackagePermissionsTablePackage.vue";

export default {
    name: "PackagePermissionsTable",
    props: {
        packagePermissions: Array,
        selectedUgroupName: String
    },
    components: {
        PackagePermissions
    },
    computed: {
        package_label: () => gettext_provider.gettext("Package"),
        release_label: () => gettext_provider.gettext("Release"),
        readers_label: () => gettext_provider.gettext("Readers")
    }
};
</script>)

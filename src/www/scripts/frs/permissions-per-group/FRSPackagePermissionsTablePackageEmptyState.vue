(<template>
    <tr>
        <td v-if="hasASelectedUserGroup" colspan="3" class="tlp-table-cell-empty">
            {{ no_permission_empty_state }}
        </td>

        <td v-else colspan="3" class="tlp-table-cell-empty">
            {{ no_package_empty_state }}
        </td>
    </tr>
</template>)
(<script>
import { sprintf } from "sprintf-js";
import { gettext_provider } from "./gettext-provider.js";

export default {
    name: "PackageEmptyState",
    props: {
        selectedUgroupName: String
    },
    computed: {
        hasASelectedUserGroup() {
            return this.selectedUgroupName !== "";
        },
        no_permission_empty_state() {
            return sprintf(
                gettext_provider.gettext("%s has no permissions for any package or release"),
                this.selectedUgroupName
            );
        },
        no_package_empty_state: () => gettext_provider.gettext("No packages found for project")
    }
};
</script>)

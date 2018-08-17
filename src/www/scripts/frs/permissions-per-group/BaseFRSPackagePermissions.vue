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

(<template>
    <section class="tlp-pane-section">
        <div v-if="hasRestError"
             class="tlp-alert-danger"
        >{{ rest_error }}</div>

        <div class="permission-per-group-load-button" v-if="! is_loaded">
            <button class="tlp-button-primary tlp-button-outline"
                    v-on:click="loadAll"
            >{{ load_all_label }}</button>
        </div>

        <div v-if="is_loading"
             v-bind:aria-label="packages_are_loading"
             class="permission-per-group-loader"
        ></div>

        <package-permissions-table
            v-if="is_loaded"
            v-bind:package-permissions="packages_list"
            v-bind:selected-ugroup-name="selectedUgroupName"
        />
    </section>
</template>)
(<script>
import { gettext_provider } from "./gettext-provider";
import { getPackagesPermissions } from "./rest-querier.js";
import PackagePermissionsTable from "./FRSPackagePermissionsTable.vue";

export default {
    name: "FrsPackagesPermissions",
    props: {
        selectedUgroupId: String,
        selectedProjectId: String,
        selectedUgroupName: String
    },
    components: {
        PackagePermissionsTable
    },
    data() {
        return {
            is_loaded: false,
            is_loading: false,
            rest_error: null,
            packages_list: []
        };
    },
    computed: {
        hasRestError() {
            return this.rest_error !== null;
        },
        load_all_label: () => gettext_provider.gettext("See all packages permissions"),
        packages_are_loading: () => gettext_provider.gettext("Packages are loading")
    },
    methods: {
        async loadAll() {
            try {
                this.is_loading = true;

                this.packages_list = await getPackagesPermissions(
                    this.selectedProjectId,
                    this.selectedUgroupId
                );

                this.is_loaded = true;
            } catch (e) {
                const { error } = await e.response.json();
                this.rest_error = error;
            } finally {
                this.is_loading = false;
            }
        }
    }
};
</script>)

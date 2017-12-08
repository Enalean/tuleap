(<template>
    <div class="dashboard-widget-content-cross-tracker-search">
        <table class="tlp-table dashboard-widget-content-cross-tracker-search-results cross-tracker-empty">
            <thead>
                <tr>
                    <th>{{ artifact_label }}</th>
                    <th>{{ status_label }}</th>
                    <th>{{ last_update_label }}</th>
                    <th>{{ submitted_by_label }}</th>
                    <th>{{ assigned_to_label }}</th>
                </tr>
            </thead>
            <tbody class="dashboard-widget-content-cross-tracker-search-artifacts">
            </tbody>
            <tbody class="dashboard-widget-content-cross-tracker-search-empty">
                <tr>
                    <td colspan="5" class="tlp-table-cell-empty">
                        {{ artifacts_empty }}
                    </td>
                </tr>
            </tbody>
            <tbody class="dashboard-widget-content-cross-tracker-search-loading">
                <tr>
                    <td colspan="5" class="cross-tracker-loader"></td>
                </tr>
            </tbody>
        </table>
        <div class="tlp-pagination">
            <button
                    class="dashboard-widget-content-cross-tracker-search-load-button tlp-button-primary tlp-button-outline tlp-button-small"
                    type="button"
            >{{ load_more_label }}</button>
        </div>
    </div>
</template>)

(<script>
    import { gettext_provider } from './gettext-provider.js';

    export default {
        name: 'ArtifactTableRenderer',
        props: [],
        computed: {
            artifact_label: () =>  gettext_provider.gettext("Artifact"),
            status_label: () => gettext_provider.gettext("Status"),
            last_update_label: () => gettext_provider.gettext("Last update date"),
            submitted_by_label: () => gettext_provider.gettext("Submitted by"),
            assigned_to_label: () => gettext_provider.gettext("Assigned to"),
            artifacts_empty: () => gettext_provider.gettext("No matching artifacts found"),
            load_more_label: () => gettext_provider.gettext("Load more"),
        }
    };
</script>)

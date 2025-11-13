/* global
    $:readonly
*/

document.observe("dom:loaded", function () {
    (function hideIrrevelantGerritMessages() {
        if ($("gerrit_past_project_delete")) {
            $("gerrit_past_project_delete").hide();
        }

        if ($("gerrit_past_project_delete_plugin_diasabled")) {
            $("gerrit_past_project_delete_plugin_diasabled").hide();
        }

        if ($("gerrit_url")) {
            $("gerrit_url").observe("change", toggleMigrateDeleteRemote);
        }
    })();

    function toggleMigrateDeleteRemote() {
        var should_delete =
                $("gerrit_url").options[$("gerrit_url").selectedIndex].readAttribute(
                    "data-repo-delete",
                ),
            plugin_enabled = $("gerrit_url").options[$("gerrit_url").selectedIndex].readAttribute(
                "data-repo-delete-plugin-enabled",
            );

        if (should_delete == 0) {
            $("gerrit_past_project_delete_plugin_diasabled").hide();
            $("gerrit_past_project_delete").hide();
            $("migrate_access_right").show();
            $("action").value = "migrate_to_gerrit";
        } else if (should_delete == 1 && plugin_enabled == 1) {
            $("gerrit_past_project_delete_plugin_diasabled").hide();
            $("gerrit_past_project_delete").show();
            $("migrate_access_right").hide();
            $("action").value = "delete_gerrit_project";
        } else if (should_delete == 1 && plugin_enabled == 0) {
            $("gerrit_past_project_delete_plugin_diasabled").show();
            $("gerrit_past_project_delete").hide();
            $("migrate_access_right").hide();
            $("action").value = "migrate_to_gerrit";
        }
    }
});

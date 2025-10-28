/* global
    codendi:readonly
    $:readonly
    codendi:readonly
    $F:readonly
    Ajax:readonly
    $$:readonly
*/

codendi.git = codendi.git || {};
codendi.git.base_url = "/plugins/git/";

document.observe("dom:loaded", function () {
    (function useTemplateConfig() {
        var gerrit_option = $("git_admin_config_from_template");

        if (gerrit_option) {
            gerrit_option.observe("click", function (event) {
                cleanTemplateForm();
                $("git_admin_config_delete").hide();
                $("git_admin_config_list_area").hide();
                $("git_admin_config_list_area").hide();
                $("git_admin_config_templates_list").hide();
                $("git_admin_config_template_name").hide();
                $("git_admin_config_form").show();
                $("git_admin_template_list_area").show();
                $("git_admin_config_edit_area").show();
                $("git_admin_file_name").show();
                $("git_admin_config_btn_create").hide();
                event.stop();
            });
        }
    })();

    (function useGerritConfig() {
        var gerrit_option = $("git_admin_config_from_gerrit");

        if (gerrit_option) {
            gerrit_option.observe("click", function (event) {
                cleanTemplateForm();
                $("git_admin_config_delete").hide();
                $("git_admin_template_list_area").hide();
                $("git_admin_config_templates_list").hide();
                $("git_admin_config_template_name").hide();
                $("git_admin_config_form").show();
                $("git_admin_config_list_area").show();
                $("git_admin_config_edit_area").show();
                $("git_admin_file_name").show();
                $("git_admin_config_btn_create").hide();
                event.stop();
            });
        }
    })();

    (function useEmptyConfig() {
        var gerrit_option = $("git_admin_config_from_scratch");

        if (gerrit_option) {
            gerrit_option.observe("click", function (event) {
                cleanTemplateForm();
                $("git_admin_config_delete").hide();
                $("git_admin_template_list_area").hide();
                $("git_admin_config_list_area").hide();
                $("git_admin_config_templates_list").hide();
                $("git_admin_config_template_name").hide();
                $("git_admin_config_edit_area").show();
                $("git_admin_config_form").show();
                $("git_admin_file_name").show();
                $("git_admin_config_btn_create").hide();
                event.stop();
            });
        }
    })();

    (function loadGerritConfig() {
        var list = $("git_admin_config_list");
        if (list) {
            list.observe("change", function () {
                var remote_repository = $F(list),
                    group_id = $F("project_id"),
                    query =
                        "?group_id=" +
                        group_id +
                        "&action=fetch_git_config&repo_id=" +
                        remote_repository;
                if (remote_repository == "") {
                    return;
                }
                new Ajax.Request(codendi.git.base_url + query, {
                    onFailure: function () {
                        //eslint-disable-next-line no-alert
                        alert(codendi.locales.git.cannot_get_gerrit_config);
                    },
                    onSuccess: function (transport) {
                        $("git_admin_config_data").value = JSON.parse(transport.responseText);
                        $("git_admin_config_edit_area").show();
                    },
                });
            });
        }
    })();

    (function loadConfigTemplate() {
        var list = $("git_admin_template_list");
        if (list) {
            list.observe("change", function () {
                var template = $F(list),
                    group_id = $F("project_id"),
                    query =
                        "?group_id=" +
                        group_id +
                        "&action=fetch_git_template&template_id=" +
                        template;

                if (template == "") {
                    return;
                }
                new Ajax.Request(codendi.git.base_url + query, {
                    onFailure: function () {
                        //eslint-disable-next-line no-alert
                        alert(codendi.locales.git.cannot_get_template);
                    },
                    onSuccess: function (transport) {
                        $("git_admin_config_data").value = JSON.parse(transport.responseText);
                    },
                });
            });
        }
    })();

    (function cancelTemplateConfig() {
        var cancel = $("git_admin_config_cancel");
        if (cancel) {
            cancel.observe("click", function () {
                cleanTemplateForm();
                $("git_admin_template_list_area").hide();
                $("git_admin_config_list_area").hide();
                $("git_admin_config_edit_area").hide();
                $("git_admin_config_form").hide();
                $("git_admin_config_btn_create").show();
                $("git_admin_config_templates_list").show();
            });
        }
    })();

    (function editTemplateConfig() {
        $$(".git_admin_config_edit_template").each(function (edit_link) {
            edit_link.observe("click", function (event) {
                var template_id = edit_link.readAttribute("data-template-id"),
                    group_id = $F("project_id"),
                    query =
                        "?group_id=" +
                        group_id +
                        "&action=fetch_git_template&template_id=" +
                        template_id;

                event.stop();
                cleanTemplateForm();

                new Ajax.Request(codendi.git.base_url + query, {
                    onFailure: function () {
                        //eslint-disable-next-line no-alert
                        alert(codendi.locales.git.cannot_get_template);
                    },
                    onSuccess: function (transport) {
                        $("git_admin_config_data").value = JSON.parse(transport.responseText);
                        $("git_admin_config_templates_list").hide();
                        $("git_admin_config_btn_create").hide();
                        $("git_admin_file_name").hide();
                        $("git_admin_config_edit_area").show();
                        $("git_admin_config_form").show();
                        $("git_admin_config_template_name").show();
                        $("git_admin_config_template_name").textContent =
                            edit_link.readAttribute("data-template-name");
                        $("git_admin_template_id").value =
                            edit_link.readAttribute("data-template-id");
                    },
                });
            });
        });
    })();

    (function viewTemplateConfig() {
        $$(".git_admin_config_view_template").each(function (view_link) {
            view_link.observe("click", function (event) {
                var template_id = view_link.readAttribute("data-template-id"),
                    group_id = $F("project_id"),
                    query =
                        "?group_id=" +
                        group_id +
                        "&action=fetch_git_template&template_id=" +
                        template_id;

                event.stop();
                cleanTemplateForm();

                new Ajax.Request(codendi.git.base_url + query, {
                    onFailure: function () {
                        //eslint-disable-next-line no-alert
                        alert(codendi.locales.git.cannot_get_template);
                    },
                    onSuccess: function (transport) {
                        $("git_admin_config_data").value = JSON.parse(transport.responseText);
                        $("git_admin_config_templates_list").hide();
                        $("git_admin_config_btn_create").hide();
                        $("git_admin_file_name").hide();
                        $("git_admin_save_button").hide();
                        $("git_admin_config_delete").hide();
                        $("git_admin_config_data_label").hide();
                        $("git_admin_config_edit_area").show();
                        $("git_admin_config_form").show();
                        $("git_admin_config_template_name").show();
                        $("git_admin_config_template_name").textContent =
                            view_link.readAttribute("data-template-name");
                    },
                });
            });
        });
    })();

    function cleanTemplateForm() {
        $("git_admin_config_data").value = "";
        $("git_admin_file_name").value = "";
        $("git_admin_template_id").value = "";
        $("git_admin_config_template_name").textContent = "";
        $("git_admin_config_data_label").show();
        $("git_admin_save_button").show();
        $("git_admin_config_delete").show();
        $$("#git_admin_config_form select").each(function (selectbox) {
            selectbox.selectedIndex = 0;
        });
    }

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

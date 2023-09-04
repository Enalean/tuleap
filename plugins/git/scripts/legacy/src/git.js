/* global
    codendi:readonly
    Template: readonly
    $:readonly
    codendi:readonly
    tuleap:readonly
    $F:readonly
    PeriodicalExecuter:readonly
    Ajax:readonly
    $$:readonly
*/

codendi.git = codendi.git || {};
codendi.git.base_url = "/plugins/git/";

document.observe("dom:loaded", function () {
    var fork_repositories_prefix = $("fork_repositories_prefix");

    if (fork_repositories_prefix) {
        var fork_destination = $("fork_destination");
        var fork_path = $("fork_repositories_path");
        var submit = fork_repositories_prefix.up("form").down("input[type=submit]");

        var tpl = new Template("<div>#{dest}/#{path}#{repo}</div>");

        //eslint-disable-next-line no-inner-declarations
        function getPreviewUpdater(table, previewPos) {
            table
                .down("thead > tr > td", previewPos)
                .update(
                    '<label style="font-weight: bold;">' + codendi.locales.git.preview + "</label>",
                );
            var preview = new Element("div", {
                style: "color: #999; border-bottom: 1px solid #EEE; margin-bottom:0.5em; padding-bottom:0.5em;",
            });
            table.down("tbody > tr > td", previewPos).insert({ top: preview });

            function getForkDestination() {
                if (fork_destination === null || fork_destination.disabled) {
                    return $F("fork_repositories_prefix");
                } else {
                    return fork_destination.options[fork_destination.selectedIndex].title;
                }
            }
            return function (periodicalExecuter) {
                // On form submission, stop periodical executer so button stay
                // disabled.
                if (submitted === true) {
                    periodicalExecuter.stop();
                    return;
                }
                var tplVars = {
                    path: "",
                    repo: "...",
                    dest: tuleap.escaper.html(getForkDestination()),
                };
                if (
                    (fork_destination === null || fork_destination.disabled) &&
                    $F("fork_repositories_path").strip()
                ) {
                    tplVars["path"] = tuleap.escaper.html(
                        $F("fork_repositories_path").strip() + "/",
                    );
                }
                var reposList = $("fork_repositories_repo");
                if (reposList.selectedIndex >= 0) {
                    submit.enable();
                    preview.update("");
                    for (
                        var repoIndex = 0, len = reposList.options.length;
                        repoIndex < len;
                        ++repoIndex
                    ) {
                        if (reposList.options[repoIndex].selected) {
                            tplVars.repo = tuleap.escaper.html(reposList.options[repoIndex].text);
                            preview.insert(tpl.evaluate(tplVars));
                        }
                    }
                } else {
                    submit.disable();
                    preview.update(tpl.evaluate(tplVars));
                }
            };
        }

        // Keep status of the submitted form
        var submitted = false;
        var table = fork_repositories_prefix.up("table");

        if (table !== undefined) {
            new PeriodicalExecuter(getPreviewUpdater(table, 3), 0.5);
        }

        // On fork, disable submit button
        submit.up("form").observe("submit", function () {
            submit.disable();
            submitted = true;
        });

        //eslint-disable-next-line no-inner-declarations
        function toggleDestination() {
            var optionBox = $("choose_project");
            if (optionBox !== null && optionBox.checked) {
                fork_destination.enable();
                fork_path.disable();
                fork_path.placeholder = codendi.locales.git.path_placeholder_disabled;
                fork_path.title = codendi.locales.git.path_placeholder_disabled;
            } else {
                disabledForkDestination();
                fork_path.enable();
                fork_path.placeholder = codendi.locales.git.path_placeholder_enabled;
                fork_path.title = codendi.locales.git.path_placeholder_enabled;
            }
        }

        //eslint-disable-next-line no-inner-declarations
        function disabledForkDestination() {
            if (fork_destination !== null) {
                fork_destination.disable();
            }
        }

        if ($("choose_project") && $("choose_personal")) {
            disabledForkDestination();
            toggleDestination();
            $("choose_project").observe("change", toggleDestination);
            $("choose_personal").observe("change", toggleDestination);
            $("choose_project").observe("click", toggleDestination);
            $("choose_personal").observe("click", toggleDestination);
        }
    }

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
                        alert(codendi.locales["git"].cannot_get_gerrit_config);
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
                        alert(codendi.locales["git"].cannot_get_template);
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
                        alert(codendi.locales["git"].cannot_get_template);
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
                        alert(codendi.locales["git"].cannot_get_template);
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

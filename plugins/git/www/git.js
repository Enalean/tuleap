codendi.git = codendi.git || {};
codendi.git.base_url = '/plugins/git/';

document.observe('dom:loaded', function () {
    var fork_repositories_prefix = $('fork_repositories_prefix');

    // About the read-only add-on
    (function ($) {
        var element = '<span class="add-on"><span class="label">read-only</span></span>';
        $(element).insertBefore($('#plugin_git_clone_field'));
        displayReadOnly = function (transport) {
            if ($('.gerrit_url').length > 0 && transport !== "gerrit") {
                $('#plugin_git_clone_url_group').addClass('plugin_git_clone_field-read-only');
            } else {
                $('#plugin_git_clone_url_group').removeClass('plugin_git_clone_field-read-only');
            }
        }
    })(jQuery);

    // Update clone url field value according to selected protocol
    $$('.plugin_git_transport').each(function (radio) {
       radio.observe('click', function (event) {
           var url = radio.readAttribute('data-url');
           var transport = event.target.innerHTML;
           $('plugin_git_clone_field').value = url;
           $$('.plugin_git_example_url').invoke('update', url);
           displayReadOnly(transport);
       });
    });

    if (fork_repositories_prefix) {
        var fork_destination = $('fork_destination');
        var fork_path        = $('fork_repositories_path');
        var submit = fork_repositories_prefix.up('form').down('input[type=submit]');

        var tpl = new Template('<div>#{dest}/#{path}#{repo}</div>');

        function getPreviewUpdater(table, previewPos) {
            table.down('thead > tr > td', previewPos).update('<label style="font-weight: bold;">'+ codendi.locales.git.preview +'</label>');
            var preview = new Element('div', {
                    style: 'color: #999; border-bottom: 1px solid #EEE; margin-bottom:0.5em; padding-bottom:0.5em;'
            });
            table.down('tbody > tr > td', previewPos).insert({top: preview});

            function getForkDestination() {
                if (fork_destination.disabled) {
                    return $F('fork_repositories_prefix');
                } else {
                    return fork_destination.options[fork_destination.selectedIndex].title;
                }
            }
            return function(periodicalExecuter) {
                // On form submission, stop periodical executer so button stay
                // disabled.
                if (submitted === true) {
                    periodicalExecuter.stop();
                    return;
                }
                var tplVars = {
                    path: '',
                    repo: '...',
                    dest: getForkDestination()
                };
                if (fork_destination.disabled && $F('fork_repositories_path').strip()) {
                    tplVars['path'] = $F('fork_repositories_path').strip() + '/';
                }
                var reposList = $('fork_repositories_repo');
                if (reposList.selectedIndex >= 0) {
                    submit.enable();
                    preview.update('');
                    for (var repoIndex = 0, len =reposList.options.length ; repoIndex < len ; ++repoIndex) {
                        if (reposList.options[repoIndex].selected) {
                            tplVars.repo = reposList.options[repoIndex].text;
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
        var table     = fork_repositories_prefix.up('table');

        var periodicalExecuter = new PeriodicalExecuter(getPreviewUpdater(table, 3), 0.5);

        // On fork, disable submit button
        submit.up('form').observe('submit', function (event) {
           submit.disable();
           submitted = true;
        });

        function toggleDestination() {
            var optionBox = $('choose_project');
            if (optionBox.checked) {
                fork_destination.enable();
                fork_path.disable();
                fork_path.placeholder = codendi.locales.git.path_placeholder_disabled;
                fork_path.title       = codendi.locales.git.path_placeholder_disabled;
            } else {
                fork_destination.disable();
                fork_path.enable();
                fork_path.placeholder = codendi.locales.git.path_placeholder_enabled;
                fork_path.title       = codendi.locales.git.path_placeholder_enabled;
            }
        }

        fork_destination.disable();
        toggleDestination();
        $('choose_project').observe('change', toggleDestination);
        $('choose_personal').observe('change', toggleDestination);
        $('choose_project').observe('click', toggleDestination);
        $('choose_personal').observe('click', toggleDestination);
    }

    (function toggleContextualHelpForCloneUrl() {
        var handle = $('plugin_git_example-handle');
        if (handle) {
            $('plugin_git_example').hide();
            handle.observe('click', function (evt) {
                $('plugin_git_example').toggle();
            });
        }
    })();

    (function autoSelectGitCloneUrl() {
        var clone_field = $('plugin_git_clone_field');
        if (clone_field) {
            clone_field.observe('click', function (evt) {
                Event.element(evt).select();
            });
        }

    })();

    (function useTemplateConfig() {
        var gerrit_option = $('git_admin_config_from_template');

        if( gerrit_option) {
            gerrit_option.observe('click', function(event) {
                cleanTemplateForm();
                $('git_admin_config_list_area').hide();
                $('git_admin_config_list_area').hide();
                $('git_admin_config_templates_list').hide();
                $('git_admin_config_template_name').hide();
                $('git_admin_config_form').show();
                $('git_admin_template_list_area').show();
                $('git_admin_config_edit_area').show();
                $('git_admin_file_name').show();
                $('git_admin_config_btn_create').removeClassName('open');
                event.stop();
            });
        }
    })();

    (function useGerritConfig() {
        var gerrit_option = $('git_admin_config_from_gerrit');

        if (gerrit_option) {
            gerrit_option.observe('click', function(event) {
                cleanTemplateForm();
                $('git_admin_template_list_area').hide();
                $('git_admin_config_templates_list').hide();
                $('git_admin_config_template_name').hide();
                $('git_admin_config_form').show();
                $('git_admin_config_list_area').show();
                $('git_admin_config_edit_area').show();
                $('git_admin_file_name').show();
                $('git_admin_config_btn_create').removeClassName('open');
                event.stop();
            });
        }
    })();

    (function useEmptyConfig() {
        var gerrit_option = $('git_admin_config_from_scratch');

        if (gerrit_option) {
            gerrit_option.observe('click', function(event) {
                cleanTemplateForm();
                $('git_admin_template_list_area').hide();
                $('git_admin_config_list_area').hide();
                $('git_admin_config_templates_list').hide();
                $('git_admin_config_template_name').hide();
                $('git_admin_config_edit_area').show();
                $('git_admin_config_form').show();
                $('git_admin_file_name').show();
                $('git_admin_config_btn_create').removeClassName('open');
                event.stop();
            });
        }
    })();

    (function loadGerritConfig() {
        var list = $('git_admin_config_list');
        if (list) {
            list.observe('change', function() {
            var remote_repository = $F(list),
                group_id = $F('project_id'),
                query    = '?group_id='+group_id+'&action=fetch_git_config&repo_id='+remote_repository;
            if (remote_repository == '') {
                return;
            }
            new Ajax.Request(codendi.git.base_url + query, {
                onFailure: function() {
                    alert(codendi.locales['git'].cannot_get_gerrit_config)
                },
                onSuccess: function (transport) {
                    $('git_admin_config_data').value = transport.responseText;
                    $('git_admin_config_edit_area').show();
                    }
                });

            });
        }
    })();

    (function loadConfigTemplate() {
        var list = $('git_admin_template_list');
        if (list) {
            list.observe('change', function() {
                var template = $F(list),
                    group_id = $F('project_id'),
                    query    = '?group_id='+group_id+'&action=fetch_git_template&template_id='+template;

                if (template == '') {
                    return;
                }
                new Ajax.Request(codendi.git.base_url + query, {
                    onFailure: function() {
                        alert(codendi.locales['git'].cannot_get_template)
                    },
                    onSuccess: function (transport) {
                        $('git_admin_config_data').value = transport.responseText;
                    }
                });

            });
        }
    })();
    
    (function cancelTemplateConfig() {
        var cancel = $('git_admin_config_cancel');
        if (cancel) {
            cancel.observe('click', function() {
                cleanTemplateForm();
                $('git_admin_template_list_area').hide();
                $('git_admin_config_list_area').hide();
                $('git_admin_config_edit_area').hide();
                $('git_admin_config_form').hide();
                $('git_admin_config_btn_create').show();
                $('git_admin_config_templates_list').show();
            });
        }
    })();

    (function editTemplateConfig() {
        $$('.git_admin_config_edit_template').each(function (edit_link) {
            edit_link.observe('click', function (event) {
                var template_id = edit_link.readAttribute('data-template-id'),
                    group_id = $F('project_id'),
                    query    = '?group_id='+group_id+'&action=fetch_git_template&template_id='+template_id;

                event.stop();
                cleanTemplateForm();
                
                new Ajax.Request(codendi.git.base_url + query, {
                    onFailure: function() {
                        alert(codendi.locales['git'].cannot_get_template)
                    },
                    onSuccess: function (transport) {
                        $('git_admin_config_data').value = transport.responseText;
                        $('git_admin_config_templates_list').hide();
                        $('git_admin_config_btn_create').hide();
                        $('git_admin_file_name').hide();
                        $('git_admin_config_edit_area').show();
                        $('git_admin_config_form').show();
                        $('git_admin_config_template_name').show();
                        $('git_admin_config_template_name').innerHTML = edit_link.readAttribute('data-template-name');
                        $('git_admin_template_id').value = edit_link.readAttribute('data-template-id');
                    }
                });
            });
         });
    })();

    (function viewTemplateConfig() {
            $$('.git_admin_config_view_template').each(function (view_link) {
                view_link.observe('click', function (event) {
                    var template_id = view_link.readAttribute('data-template-id'),
                        group_id = $F('project_id'),
                        query    = '?group_id='+group_id+'&action=fetch_git_template&template_id='+template_id;

                    event.stop();
                    cleanTemplateForm();

                    new Ajax.Request(codendi.git.base_url + query, {
                        onFailure: function() {
                            alert(codendi.locales['git'].cannot_get_template)
                        },
                        onSuccess: function (transport) {
                            $('git_admin_config_data').value = transport.responseText;
                            $('git_admin_config_templates_list').hide();
                            $('git_admin_config_btn_create').hide();
                            $('git_admin_file_name').hide();
                            $('git_admin_save_button').hide();
                            $('git_admin_config_data_label').hide();
                            $('git_admin_config_edit_area').show();
                            $('git_admin_config_form').show();
                            $('git_admin_config_template_name').show();
                            $('git_admin_config_template_name').innerHTML = view_link.readAttribute('data-template-name');
                        }
                    });
                });
             });
        })();

    function cleanTemplateForm() {
        $('git_admin_config_data').value = '';
        $('git_admin_file_name').value = '';
        $('git_admin_template_id').value = '';
        $('git_admin_config_template_name').innerHTML = '';
        $('git_admin_config_data_label').show();
        $('git_admin_save_button').show();
        $$('#git_admin_config_form select').each(function(selectbox) {
            selectbox.selectedIndex = 0;
        });
    }

        $('gerrit_past_project_delete').hide();
        $('gerrit_past_project_delete_plugin_diasabled').hide();
        $('gerrit_url').observe('change', toggleMigrateDeleteRemote);

    function toggleMigrateDeleteRemote() {
        var should_delete  = $('gerrit_url').options[$('gerrit_url').selectedIndex].readAttribute('data-repo-delete'),
            plugin_enabled = $('gerrit_url').options[$('gerrit_url').selectedIndex].readAttribute('data-repo-delete-plugin-enabled');

        if (should_delete == 0) {
            $('gerrit_past_project_delete_plugin_diasabled').hide();
            $('gerrit_past_project_delete').hide();
            $('migrate_access_right').show();
            $('action').value = 'migrate_to_gerrit';
        } else if (should_delete == 1 && plugin_enabled == 1) {
            $('gerrit_past_project_delete_plugin_diasabled').hide();
            $('gerrit_past_project_delete').show();
            $('migrate_access_right').hide();
            $('action').value = 'delete_gerrit_project';
        } else if (should_delete == 1 && plugin_enabled == 0) {
            $('gerrit_past_project_delete_plugin_diasabled').show();
            $('gerrit_past_project_delete').hide();
            $('migrate_access_right').hide();
            $('action').value = 'migrate_to_gerrit';
        }
    }

});


document.observe('dom:loaded', function () {
    var repoDesc = $('repo_desc')
      , fork_repositories_prefix = $('fork_repositories_prefix');

    if ( repoDesc ) {
        var span = new Element('span').update( repoDesc.value.escapeHTML() );
        repoDesc.insert({before:span});
        repoDesc.hide();
        var link = new Element('a',{href:'#'}).update( new Element('img', {src:'/themes/common/images/ic/edit.png',alt:'edit'}) );
        link.observe('click', function (evt) {
            span.hide();
            link.hide();
            repoDesc.show();
            evt.stop();
        });
        span.insert({after:link});
    }
    
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

        function toggleDestination(evt) {
            var optionBox = Event.element(evt);
            if (optionBox.id == "choose_project" && optionBox.checked) {
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
        $('choose_project').observe('change', toggleDestination); 
        $('choose_personal').observe('change', toggleDestination);
        $('choose_project').observe('click', toggleDestination); 
        $('choose_personal').observe('click', toggleDestination);
    }
} );


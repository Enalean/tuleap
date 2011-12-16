document.observe('dom:loaded', function () {
    var repoDesc = $('repo_desc')
      , fork_repositories_prefix = $('fork_repositories_prefix');

    if ( repoDesc ) {
        var span = new Element('span').update( repoDesc.value );
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
        var tpl = new Template('<div>' + $F('fork_repositories_prefix') + '/#{path}#{repo}</div>');
        var table = fork_repositories_prefix.up('table');
        table.down('thead > tr > td', 1).insert({ after: '<td><label style="font-weight: bold;">Preview</label></td>'});
        var preview = new Element('td', {
            style: 'color: #999'
        });
        table.down('tbody > tr > td', 1).insert({ after: preview });
        
        new PeriodicalExecuter(function () {
            var p = {
                path: $F('fork_repositories_path').strip() ? $F('fork_repositories_path').strip() + '/' : '',
                repo: '...'
            };
            if ($('fork_repositories_repo').selectedIndex >= 0) {
                preview.update('');
                for (var i = 0, len = $('fork_repositories_repo').options.length ; i < len ; ++i) {
                    if ($('fork_repositories_repo').options[i].selected) {
                        p.repo = $('fork_repositories_repo').options[i].text;
                        preview.insert(tpl.evaluate(p));
                    }
                }
            } else {
                preview.update(tpl.evaluate(p));
            }
        }, 0.5);
    }
} );
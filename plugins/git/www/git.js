document.observe('dom:loaded', function () {
    var repoDesc = $('repo_desc');
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
    
    if ($('eg_path') && $('eg_repo')) {
        new PeriodicalExecuter(function () {
            $('eg_path').update($F('fork_repositories_path').strip() ? $F('fork_repositories_path').strip() + '/' : '');
            $('eg_repo').update($F('fork_repositories_repo')[0] || '...');
        }, 1);
    }
} );
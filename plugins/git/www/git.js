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
} );
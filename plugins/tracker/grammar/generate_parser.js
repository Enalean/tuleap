var fs = require('fs');
var pegjs = require('pegjs');
var phppegjs = require('php-pegjs');

fs.readFile('tql.pegjs', function (err, data) {
    if (err) throw err;
    var parser = pegjs.buildParser(
        data.toString(),
        {
            cache: true,
            plugins: [phppegjs],
            phppegjs: {
                    parserNamespace: 'Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar',
                    parserClassName: 'Parser'
            }
        }
    );

    /*
     * If there is a syntax error in the parsed input, the generate parser need to access to a private attribute from a
     * closure. However this is not compatible with PHP5.3, as stated in the PHP documentation:
     *
     * ---8<---
     * As of PHP 5.4.0, when declared in the context of a class, the current class is automatically bound to it,
     * making $this available inside of the function's scope. If this automatic binding of the current class is not wanted,
     * then static anonymous functions may be used instead.
     *
     * http://php.net/manual/en/functions.anonymous.php
     * --->8---
     *
     * Therefore we need to hotfix the generated code to be compatible with PHP5.3
     */
    parser = parser.replace(/(\s+)private \$input( +)= "";/, '$1public  $input$2= "";');

    fs.writeFile('../include/Tracker/Report/Query/Advanced/Grammar/Parser.php', parser);
});

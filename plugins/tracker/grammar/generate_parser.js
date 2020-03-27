/* eslint-env node */

var fs = require("fs");
var pegjs = require("pegjs");
var phppegjs = require("php-pegjs");

fs.readFile("tql.pegjs", function (err, data) {
    if (err) {
        throw err;
    }
    var parser = pegjs.buildParser(data.toString(), {
        cache: true,
        plugins: [phppegjs],
        phppegjs: {
            parserNamespace: "Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar",
            parserClassName: "Parser",
        },
    });

    fs.writeFile("../include/Tracker/Report/Query/Advanced/Grammar/Parser.php", parser, function (
        err
    ) {
        if (err) {
            throw err;
        }
    });
});

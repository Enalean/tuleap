/* eslint-env node */

const fs = require("fs");
const pegjs = require("pegjs");
const phpegjs = require("phpegjs");

fs.readFile("tql.pegjs", function (err, data) {
    if (err) {
        throw err;
    }
    const parser = pegjs.generate(data.toString(), {
        cache: true,
        plugins: [phpegjs],
        phppegjs: {
            parserNamespace: "Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar",
            parserClassName: "Parser",
        },
    });

    fs.writeFile(
        "../include/Tracker/Report/Query/Advanced/Grammar/Parser.php",
        parser,
        function (err) {
            if (err) {
                throw err;
            }
        }
    );
});

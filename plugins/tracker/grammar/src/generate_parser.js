/* eslint-env node */

const path = require("path");
const fs = require("fs");
const pegjs = require("pegjs");
const phpegjs = require("phpegjs");

fs.readFile(path.join(__dirname, "tql.pegjs"), function (err, data) {
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

    const output_dir = path.join(
        __dirname,
        "../backend-assets/Tracker/Report/Query/Advanced/Grammar/",
    );
    fs.mkdirSync(output_dir, { recursive: true });
    fs.writeFile(output_dir + "Parser.php", parser, function (err) {
        if (err) {
            throw err;
        }
    });
});

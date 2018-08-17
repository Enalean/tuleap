const gulp = require("gulp");
const gettext = require("gulp-angular-gettext");
const path = require("path");

const templates_with_translated_strings_glob = "src/**/*.tpl.html";
const javascript_with_translated_strings_glob = "src/**/*.js";

gulp.task("watch", function() {
    return gulp.watch(
        [templates_with_translated_strings_glob, javascript_with_translated_strings_glob],
        { cwd: path.resolve("./angular-artifact-modal") },
        ["gettext-extract"]
    );
});

gulp.task("gettext-extract", function() {
    return gulp
        .src([templates_with_translated_strings_glob, javascript_with_translated_strings_glob], {
            cwd: path.resolve("./angular-artifact-modal"),
            cwdbase: true
        })
        .pipe(
            gettext.extract("template.pot", {
                lineNumbers: false
            })
        )
        .pipe(gulp.dest("angular-artifact-modal/po/"));
});

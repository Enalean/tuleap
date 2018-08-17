var gulp = require("gulp");
var gettext = require("gulp-angular-gettext");

var templates_with_translated_strings_glob = "src/app/**/*.tpl.html";
var javascript_with_translated_strings_glob = "src/app/**/*.js";

gulp.task("watch", function() {
    return gulp.watch(
        [templates_with_translated_strings_glob, javascript_with_translated_strings_glob],
        ["gettext-extract"]
    );
});

gulp.task("gettext-extract", function() {
    return gulp
        .src([templates_with_translated_strings_glob, javascript_with_translated_strings_glob], {
            base: "."
        })
        .pipe(
            gettext.extract("template.pot", {
                lineNumbers: false
            })
        )
        .pipe(gulp.dest("po/"));
});

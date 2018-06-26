const gulp = require("gulp");
const gettext = require("gulp-angular-gettext");

const templates_with_translated_strings_glob = "angular/src/app/**/*.tpl.html";
const javascript_with_translated_strings_glob = "angular/src/app/**/*.js";

gulp.task("watch", function() {
    return gulp.watch(
        [templates_with_translated_strings_glob, javascript_with_translated_strings_glob],
        ["gettext-extract"]
    );
});

gulp.task("gettext-extract", function() {
    return gulp
        .src([templates_with_translated_strings_glob, javascript_with_translated_strings_glob], {
            base: "angular"
        })
        .pipe(
            gettext.extract("template.pot", {
                lineNumbers: false
            })
        )
        .pipe(gulp.dest("angular/po/"));
});

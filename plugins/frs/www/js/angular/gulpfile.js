var path = require("path");
var gulp = require("gulp"); // eslint-disable-line import/no-extraneous-dependencies
var sass = require("gulp-sass"); // eslint-disable-line import/no-extraneous-dependencies

var all_scss_glob = "src/app/**/*.scss";
var main_scss_file = "src/app/tuleap-frs.scss";
var build_dir = path.resolve(__dirname, "../../assets");
var assets_glob = "src/assets/*";

gulp.task("watch", function() {
    gulp.watch(all_scss_glob, ["sass-dev"]);
});

gulp.task("build", ["copy-assets", "sass-prod"]);

gulp.task("sass-dev", function() {
    return gulp
        .src(main_scss_file)
        .pipe(
            sass({
                sourceMap: true,
                sourceMapEmbed: true,
                outputStyle: "expanded"
            })
        )
        .pipe(gulp.dest(build_dir));
});

gulp.task("sass-prod", function() {
    return gulp
        .src(main_scss_file)
        .pipe(
            sass({
                sourceMap: false,
                outputStyle: "compressed"
            })
        )
        .pipe(gulp.dest(build_dir));
});

gulp.task("copy-assets", function() {
    return gulp.src([assets_glob]).pipe(gulp.dest(build_dir));
});

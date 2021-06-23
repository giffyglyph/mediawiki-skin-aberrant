'use strict';

let gulp = require('gulp');
let del = require('del');
let path = require('path');
let using = require('gulp-using');
let sass = require('gulp-sass');

const DIST = "dist";
const SRC = "src";

/*
 * Purge all built content.
 */
gulp.task('purge', function() {
	return del(path.join(DIST, "*"));
});

/*
 * Build and deploy all translations.
 */
gulp.task('build-translations', function() {
	return gulp.src(path.join(SRC, 'translations/**/*.json'), { base: path.join(SRC, 'translations') })
	.pipe(using())
	.pipe(gulp.dest(path.join(DIST, 'i18n')));
});

/*
 * Build and deploy all templates.
 */
gulp.task('build-templates', function() {
	return gulp.src(path.join(SRC, 'templates/**/*.php'), { base: path.join(SRC, 'templates') })
	.pipe(using())
	.pipe(gulp.dest(path.join(DIST, 'includes')));
});

/*
 * Build and deploy all stylesheets.
 */
gulp.task('build-stylesheets', function() {
	return gulp.src(path.join(SRC, 'stylesheets/**/*.scss'), { base: path.join(SRC, 'stylesheets') })
	.pipe(using())
	.pipe(sass())
	.pipe(gulp.dest(path.join(DIST, 'resources/css')));
});

/*
 * Build and deploy skin manifest details.
 */
gulp.task('build-manifest', function() {
	return gulp.src(path.join(SRC, 'skin.json'), { base: path.join(SRC) })
	.pipe(using())
	.pipe(gulp.dest(path.join(DIST)));
});

/*
 * Build and deploy all images.
 */
gulp.task('build-images', function() {
	return gulp.src(path.join(SRC, 'images/**/*.*'), { base: path.join(SRC, 'images') })
	.pipe(using())
	.pipe(gulp.dest(path.join(DIST, 'resources/img')));
});

/*
 * Watch folders for any changes.
 */
gulp.task('watch', function() {
	gulp.watch(SRC + '/translations/**/*.json', gulp.series(['build-translations']));
	gulp.watch(SRC + '/templates/**/*.php', gulp.series(['build-templates']));
	gulp.watch(SRC + '/stylesheets/**/*.scss', gulp.series(['build-stylesheets']));
	gulp.watch(SRC + '/skin.json', gulp.series(['build-manifest']));
	gulp.watch(SRC + '/images/**/*.*', gulp.series(['build-images']));
});

/*
 * Build project.
 */
gulp.task('build', gulp.series('purge', 'build-translations', 'build-templates', 'build-stylesheets', 'build-images', 'build-manifest'));
gulp.task('build-and-watch', gulp.series('build', 'watch'));

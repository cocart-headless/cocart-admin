/**
 * Build automation scripts.
 *
 * @package CoCart
 */

module.exports = function(grunt) {
	'use strict';

	var sass = require( 'node-sass' );

	require( 'load-grunt-tasks' )( grunt );

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON( 'package.json' ),

		// Setting folder templates.
		dirs: {
			css: 'assets/css',
			scss: 'src/scss',
			js: 'assets/js',
		},

		// Update developer dependencies
		devUpdate: {
			packages: {
				options: {
					packageJson: null,
					packages: {
						devDependencies: true,
						dependencies: false
					},
					reportOnlyPkgs: [],
					reportUpdated: false,
					semver: true,
					updateType: 'force'
				}
			}
		},

		// Minify JavaScript
		uglify: {
			options: {
				compress: {
					global_defs: {
						"EO_SCRIPT_DEBUG": false
					},
					dead_code: true
				},
				banner: '/*! <%= pkg.title %> v<%= pkg.version %> <%= grunt.template.today("dddd dS mmmm yyyy HH:MM:ss TT Z") %> */'
			},
			build: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/admin',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.js %>/admin',
					ext: '.min.js'
				}]
			}
		},

		// Check for Javascript errors.
		jshint: {
			options: {
				reporter: require( 'jshint-stylish' ),
				globals: {
					"EO_SCRIPT_DEBUG": false,
				},
				'-W099': true, // Mixed spaces and tabs
				'-W083': true, // Fix functions within loop
				'-W082': true, // Declarations should not be placed in blocks
				'-W020': true, // Read only - error when assigning EO_SCRIPT_DEBUG a value.
			},
			all: [
				'<%= dirs.js %>/admin/*.js',
				'!<%= dirs.js %>/admin/*.min.js'
			]
		},

		// SASS to CSS
		sass: {
			options: {
				implementation: sass,
				sourcemap: 'none'
			},
			dist: {
				files: {
					'<%= dirs.css %>/admin/cocart.css' : '<%= dirs.scss %>/admin/admin.scss',
					'<%= dirs.css %>/admin/cocart-setup.css' : '<%= dirs.scss %>/admin/cocart-setup.scss',
					'<%= dirs.css %>/admin/plugin-search.css' : '<%= dirs.scss %>/admin/plugin-search.scss'
				}
			}
		},

		// Generate RTL .css files.
		rtlcss: {
			dist: {
				expand: true,
				src: [
					'<%= dirs.css %>/admin/*.css',
					'!<%= dirs.css %>/admin/*-rtl.css',
					'!<%= dirs.css %>/admin/*.min.css'
				],
				ext: '-rtl.css'
			}
		},

		// Post CSS
		postcss: {
			options: {
				processors: [
					require( 'autoprefixer' )
				]
			},
			dist: {
				expand: true,
				src: [
					'!<%= dirs.css %>/admin/*.min.css',
					'<%= dirs.css %>/admin/*.css'
				],
				ext: '.css'
			}
		},

		// Minify CSS
		cssmin: {
			options: {
				processImport: false,
				roundingPrecision: -1,
				shorthandCompacting: false
			},
			target: {
				files: [{
					expand: true,
					cwd: '<%= dirs.css %>/admin',
					src: [
						'!*.min.css',
						'*.css',
					],
					dest: '<%= dirs.css %>/admin',
					ext: '.min.css'
				}]
			}
		},

		// Watch for changes made.
		watch: {
			postcss: {
				files: [
					'!<%= dirs.css %>/admin/*.min.css',
					'<%= dirs.css %>/admin/*.css'
				],
				tasks: ['postcss'],
				options: {
					interrupt: true
				}
			},
			css: {
				files: [
					'<%= dirs.scss %>/*.scss',
					'<%= dirs.scss %>/admin/*.scss',
				],
				tasks: ['css']
			},
			js: {
				files: [
					'<%= dirs.js %>/admin/*.js',
					'!<%= dirs.js %>/admin/*.min.js'
				],
				tasks: ['jshint']
			},
		},

		// Check for Sass errors with "stylelint"
		stylelint: {
			options: {
				configFile: '.stylelintrc'
			},
			all: [
				'<%= dirs.scss %>/**/*.scss',
			]
		},

		// Bump version numbers (replace with version in package.json)
		replace: {
			package: {
				src: [
					'load-package.php',
				],
				overwrite: true,
				replacements: [
					{
						from: /@version .*$/m,
						to: "@version <%= pkg.version %>"
					},
				]
			}
		},

	});

	// Set the default grunt command to run test cases.
	grunt.registerTask( 'default', [ 'test' ] );

	// Checks for developer dependencies updates.
	grunt.registerTask( 'check', [ 'devUpdate' ] );

	// Checks for errors.
	grunt.registerTask( 'test', [ 'stylelint', 'jshint' ] );

	// Build CSS ONLY!
	grunt.registerTask( 'css', [ 'stylelint', 'sass', 'rtlcss', 'postcss', 'cssmin' ] );

	// Build JS ONLY!
	grunt.registerTask( 'js', [ 'jshint', 'uglify' ] );

	// Update version of plugin.
	grunt.registerTask( 'version', [ 'replace:package' ] );

	// Build Plugin.
	grunt.registerTask( 'build', [ 'version', 'css', 'js' ] );

	// Register Watcher Tasks.
	grunt.registerTask( 'watch-css', ['watch:css'] );
	grunt.registerTask( 'watch-postcss', ['watch:postcss'] );
	grunt.registerTask( 'watch-js', ['watch:js'] );

};

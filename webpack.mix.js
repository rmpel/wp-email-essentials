const mix = require('laravel-mix'),
  fs = require('fs');

/**
 * Get all files from specific directory.
 * @param {string} dir path to file from root.
 * @return {string[]}
 */
const getFiles = function (dir) {
  return fs.readdirSync(dir).filter(file => {
    return fs.statSync(`${dir}/${file}`).isFile();
  });
};

/**
 * Get all directories from specific directory.
 * @param dir
 * @return {string[]}
 */
const getDirectories = function (dir) {
  return fs.readdirSync(dir).filter(file => {
    return fs.statSync(`${dir}/${file}`).isDirectory();
  });
};


/**
 * Loop files to handle.
 *
 * @param {string} folder name of the folder to scan.
 * @param {string} outputFolder name of the folder to output.
 */
const mixFiles = (folder, outputFolder = folder) => {

  // Apply mix to all scripts and styles in the root directory.
  getFiles(`assets/${folder}`).forEach((filepath) => {
    // Skip all files starting with a dot.
    if ('.' !== filepath.charAt(0) && '_' !== filepath.charAt(0)) {

      if ('scripts' === folder || folder.includes('scripts/')) {
        mix.js(`assets/${folder}/${filepath}`, outputFolder);
      }

      if ('styles' === folder || folder.includes('styles/')) {
        mix.sass(`assets/${folder}/${filepath}`, outputFolder);
      }

      if ('images' === folder || 'fonts' === folder) {
        mix.copy(`assets/${folder}/${filepath}`, `${process.env.MIX_BUILD_DIR}/${folder}`);
      }
    }
  });
};

// Loop through directories to build.
getDirectories('assets').forEach((dir) => mixFiles(dir));

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Sage application. By default, we are compiling the Sass file
 | for your application, as well as bundling up your JS files.
 |
 */

mix
  .setPublicPath('./public')
  .options({
    processCssUrls: false,
    autoprefixer: { remove: false },
  })
  .sourceMaps(false, 'source-map')
  .version();

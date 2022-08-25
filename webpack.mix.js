// webpack.mix.js

let mix = require('laravel-mix');
let webpack = require('webpack');
let tailwindcss = require('tailwindcss');
require('laravel-mix-polyfill');

mix.setPublicPath("src/Resources/public");
mix.sass('assets/sass/manager.scss', 'style/style.css');
mix.js('assets/js/app.js', 'js').vue();
mix.copyDirectory('assets/dist', 'src/Resources/public/dist');
mix.polyfill();

mix.options({
    postCss: [ tailwindcss('./tailwind.config.js') ],
})

mix.webpackConfig({
    output: {
        publicPath: 'bundles/easymedia/',
    },
    plugins: [
        // fix ReferenceError: Buffer/process is not defined
        new webpack.ProvidePlugin({
            process : 'process/browser',
            Buffer  : ['buffer', 'Buffer']
        })
    ]
})

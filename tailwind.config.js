module.exports = {
    purge: [
        './src/**/*.html.twig',
        './src/**/*.html.twig',
        './src/**/*.html',
        './src/**/*.vue',
        './assets/**/*.vue',
        './assets/**/*.scss',
        './assets/**/*.js',
        './src/**/*.jsx',
    ],
    darkMode: false, // or 'media' or 'class'
    theme: {
        extend: {
            colors: {
                'theme': '#eceff1',
                'theme-5': '#dee3e6',
                'theme-10': '#d0d7dc',
                'theme-15': '#c2ccd2',
                'theme-25': '#a7b5be',
                'theme-50': '#657a89',
                'theme-60': '#50626d',
                'theme-70': '#3c4952',
                'theme-75': '#323d44',
            },
            zIndex: {
                '-1': '-1',
                '1': '1',
                '2': '2',
                '3': '3',
            },
            transitionDuration: {
                '0': '0ms',
                '400': '400ms',
            },
            cursor: {
                copy: 'copy',
            }
        },
    },
    variants: {},
    plugins: [],
}

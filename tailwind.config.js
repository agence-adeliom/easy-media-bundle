module.exports = {
    important: ".media-manager",
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
                'body': 'var(--body-bg)',
                'header': 'var(--sidebar-bg)',
                'color': 'var(--text-primary-color)',
                'color-secondary': 'var(--text-secondary-color)',
                'active': 'var(--sidebar-menu-active-item-bg)',
                'color-active': 'var(--sidebar-menu-active-item-color)',
                'theme': 'var(--sidebar-bg)',
                'theme-5': 'var(--sidebar-border-color)',
                'primary': 'var(--color-primary)',
                'warning': 'var(--color-warning)',
                'danger': 'var(--color-danger)',
                'link': 'var(--color-info)',
                'success': 'var(--color-success)',
                'dark': '#404040',
                'theme-10': '#d0d7dc',
                'theme-15': '#c2ccd2',
                'theme-25': '#a7b5be',
                'theme-50': '#657a89',
                'theme-60': '#50626d',
                'theme-70': '#3c4952',
                'theme-75': 'var(--sidebar-bg)',
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

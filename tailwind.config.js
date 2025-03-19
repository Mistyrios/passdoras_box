/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: 'class',
    content: [
        './assets/**/*.js',
        './templates/**/*.html.twig',
        "./src/Form/*.php",
    ],
    safeList: ['row_attr'],
    theme: {
        extend: {},
    },
    plugins: [],
}

import forms from '@tailwindcss/forms';

const colors = require('tailwindcss/colors');

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
  ],

  theme: {
    colors: {
      transparent: 'transparent',
      current: 'currentColor',
      'primary': {
        300: '#639be9',
        400: '#5893e7',
        500: '#4d8ce6',
        600: '#3c81e3',
      },
      'secondary': '#3f3f3f',
      'success': {
        500: '#44cf6e',
        600: '#0cb54f',
      },
      'error': colors.red,
      'warning': colors.orange,
      'info': colors.blue,
      'light-base': {
        50: '#ffffff',
        100: '#fcfcfc',
        200: '#f6f7f8',
        300: '#ebedf0',
        400: '#e2e5e9',
        500: '#d8d9da',
        600: '#afb5bd',
        700: '#6e767e',
        800: '#6d757d',
        900: '#555e68',
        950: '#222222',
      },
      'base': {
        50: '#dadada',
        100: '#bec6cf',
        200: '#a8afb8',
        300: '#a5adb5',
        400: '#536170',
        500: '#35393e',
        600: '#34383b',
        700: '#383c43',
        800: '#282c34',
        900: '#1c2127',
        950: '#181c20',
      },
    },
    extend: {},
  },

  plugins: [forms],
}


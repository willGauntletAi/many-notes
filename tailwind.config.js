import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
  ],

  theme: {
    colors: {
      transparent: 'transparent',
      current: 'currentColor',
      'blue': {
        '50': '#639BE9',
        '100': '#5893E7',
        '200': '#4D8CE6',
        '300': '#3C81E3',
      },
      'gray': '#3F3F3F',
      'light-base': {
        '50': '#FFFFFF',
        '100': '#FCFCFC',
        '200': '#F6F7F8',
        '300': '#EBEDF0',
        '400': '#E2E5E9',
        '500': '#D8D9DA',
        '600': '#AFB5BD',
        '700': '#6E767E',
        '800': '#6D757D',
        '900': '#555E68',
        '950': '#222222',
      },
      'base': {
        '50': '#DADADA',
        '100': '#BEC6CF',
        '200': '#A8AFB8',
        '300': '#A5ADB5',
        '400': '#536170',
        '500': '#35393E',
        '600': '#34383B',
        '700': '#383C43',
        '800': '#282C34',
        '900': '#1C2127',
        '950': '#181C20',
      },
    },
    extend: {},
  },

  plugins: [forms],
}


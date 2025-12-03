/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        'moov-orange': {
          DEFAULT: '#FF6B00',
          light: '#FF8C42',
          dark: '#E55A00',
        },
      },
    },
  },
  plugins: [],
}

import type { Config } from 'tailwindcss'

const config: Config = {
  content: [
    './src/pages/**/*.{js,ts,jsx,tsx,mdx}',
    './src/components/**/*.{js,ts,jsx,tsx,mdx}',
    './src/app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      colors: {
        cosmic: {
          50:  '#f0f0ff',
          100: '#e0e0ff',
          200: '#c4c4ff',
          300: '#a0a0ff',
          400: '#7b7bff',
          500: '#6060f0',
          600: '#4848d8',
          700: '#3535b8',
          800: '#232394',
          900: '#141478',
        },
        nebula: {
          pink:   '#f472b6',
          purple: '#a855f7',
          blue:   '#3b82f6',
          cyan:   '#06b6d4',
          green:  '#10b981',
        },
        space: {
          dark:    '#03030f',
          darker:  '#06061a',
          mid:     '#0d0d2b',
          light:   '#141438',
        },
      },
      backgroundImage: {
        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
        'gradient-conic':  'conic-gradient(from 180deg at 50% 50%, var(--tw-gradient-stops))',
        'stellar-gradient': 'linear-gradient(135deg, #03030f 0%, #0d0d2b 50%, #1a0a2e 100%)',
      },
      animation: {
        'float':       'float 6s ease-in-out infinite',
        'pulse-slow':  'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'twinkle':     'twinkle 3s ease-in-out infinite',
        'orbit':       'orbit 20s linear infinite',
        'glow':        'glow 2s ease-in-out infinite alternate',
      },
      keyframes: {
        float: {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%':      { transform: 'translateY(-20px)' },
        },
        twinkle: {
          '0%, 100%': { opacity: '1', transform: 'scale(1)' },
          '50%':      { opacity: '0.3', transform: 'scale(0.8)' },
        },
        orbit: {
          from: { transform: 'rotate(0deg) translateX(120px) rotate(0deg)' },
          to:   { transform: 'rotate(360deg) translateX(120px) rotate(-360deg)' },
        },
        glow: {
          from: { boxShadow: '0 0 10px #6060f0, 0 0 20px #6060f0' },
          to:   { boxShadow: '0 0 20px #a855f7, 0 0 40px #a855f7' },
        },
      },
      fontFamily: {
        space: ['var(--font-space)', 'system-ui', 'sans-serif'],
        mono:  ['var(--font-mono)', 'monospace'],
      },
    },
  },
  plugins: [],
}

export default config

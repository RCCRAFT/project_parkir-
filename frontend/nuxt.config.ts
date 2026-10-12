import tailwindcss from "@tailwindcss/vite";

export default defineNuxtConfig({
  compatibilityDate: "2026-07-23",

  future: {
    compatibilityVersion: 4,
  },
  vite: {
    plugins: [
      tailwindcss()
    ]
  },
  css: [
    "~/assets/css/main.css"
  ],
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || "http://127.0.0.1:8000/api"
    }
  }
});
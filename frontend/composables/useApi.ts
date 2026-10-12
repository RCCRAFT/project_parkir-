import axios from "axios";

export const useApi = () => {
  const api = axios.create({
    baseURL: "http://127.0.0.1:8000/api",
    headers: {
      Accept: "application/json",
    },
  });

  // Pasang token otomatis di setiap request
  api.interceptors.request.use((config) => {
    if (import.meta.client) {
      const token = localStorage.getItem("token");

      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
    }

    return config;
  });

  // Kalau token ditolak (401), paksa logout
  api.interceptors.response.use(
    (response) => response,
    async (err) => {
      const url: string = err.config?.url || "";
      const isAuthRequest =
        url.includes("/login") || url.includes("/logout");

      if (err.response?.status === 401 && !isAuthRequest) {
        if (import.meta.client) {
          localStorage.removeItem("token");
          localStorage.removeItem("user");
        }

        await navigateTo("/");
      }

      return Promise.reject(err);
    }
  );

  return api;
};
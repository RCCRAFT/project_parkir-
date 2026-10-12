<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useApi } from "~/composables/useApi";

/* =========================================
   FORM
========================================= */

const email = ref("");
const password = ref("");

const emailInput = ref<HTMLInputElement | null>(null);
const passwordInput = ref<HTMLInputElement | null>(null);

const loading = ref(false);
const error = ref("");
const showPassword = ref(false);

/* =========================================
   ROLE
========================================= */

const selectedRole = ref<"super_admin" | "petugas" | "">("");

const selectRole = (role: "super_admin" | "petugas") => {
  selectedRole.value = role;
  error.value = "";
};

/* =========================================
   API
========================================= */

const api = useApi();

/* =========================================
   BERSIHKAN SESI
========================================= */

const hapusSesi = () => {
  localStorage.removeItem("token");
  localStorage.removeItem("user");
};

/* =========================================
   SUDAH LOGIN? LANGSUNG REDIRECT
========================================= */

onMounted(async () => {
  try {
    const token = localStorage.getItem("token");
    const savedUser = localStorage.getItem("user");

    if (!token || !savedUser) {
      return;
    }

    const role = String(JSON.parse(savedUser)?.role || "")
      .toLowerCase()
      .trim();

    if (role === "super_admin") {
      await navigateTo("/admin");
    } else if (role === "petugas") {
      await navigateTo("/petugas");
    } else {
      hapusSesi();
    }
  } catch {
    hapusSesi();
  }
});

/* =========================================
   LOGIN
========================================= */

const login = async () => {
  error.value = "";

  if (!selectedRole.value) {
    error.value = "Silakan pilih jenis akun terlebih dahulu.";
    return;
  }

  // Ambil nilai langsung dari input (membantu autofill Chrome)
  email.value = emailInput.value?.value.trim() || "";
  password.value = passwordInput.value?.value || "";

  if (!email.value || !password.value) {
    error.value = "Email dan password wajib diisi.";
    return;
  }

  loading.value = true;

  try {
    const response = await api.post("/login", {
      email: email.value,
      password: password.value,
      role: selectedRole.value,
    });

    const { token, user } = response.data;

    if (!token || !user?.role) {
      hapusSesi();
      error.value = "Respons server tidak lengkap. Coba lagi.";
      return;
    }

    const role = String(user.role).toLowerCase().trim();

    if (role !== "super_admin" && role !== "petugas") {
      hapusSesi();
      error.value = "Role akun tidak dikenali.";
      return;
    }

    localStorage.setItem("token", token);
    localStorage.setItem("user", JSON.stringify(user));

    await navigateTo(role === "super_admin" ? "/admin" : "/petugas");
  } catch (err: any) {
    console.error("LOGIN ERROR:", err);

    const status = err.response?.status;
    const message = err.response?.data?.message;

    // Error validasi Laravel (422)
    const validationErrors = err.response?.data?.errors as
      | Record<string, string[]>
      | undefined;

    const firstKey = validationErrors
      ? Object.keys(validationErrors)[0]
      : undefined;

    if (status === 401) {
      error.value =
        message || "Email, password, atau jenis akun salah.";
    } else if (status === 403) {
      // Termasuk pesan "Akun kamu nonaktif..."
      error.value =
        message || "Akun tidak memiliki akses ke halaman ini.";
    } else if (validationErrors && firstKey) {
      error.value =
        validationErrors[firstKey]?.[0] || "Data login tidak valid.";
    } else if (message) {
      error.value = message;
    } else {
      error.value =
        "Tidak dapat terhubung ke server. Pastikan Laravel sedang berjalan.";
    }
  } finally {
    loading.value = false;
  }
};

/* =========================================
   FORGOT PASSWORD
========================================= */

const forgotPassword = () => {
  navigateTo("/lupapassword");
};
</script>


<template>

  <div class="login-page">

    <!-- =====================================
         BACKGROUND DECORATION
    ====================================== -->

    <div class="background-grid"></div>

    <div class="background-glow glow-one"></div>

    <div class="background-glow glow-two"></div>

    <div class="background-shape shape-one"></div>

    <div class="background-shape shape-two"></div>

    <div class="background-shape shape-three"></div>


    <!-- =====================================
         LOGIN CARD
    ====================================== -->

    <div class="login-card">


      <!-- ===================================
           HEADER
      ==================================== -->

      <div class="login-header">

        <div class="header-top-line"></div>


        <div class="logo">

          <span>P</span>

        </div>


        <h1>
          PARKIR
        </h1>


        <p class="location">
          PLAZA ANDALAS
        </p>


        <div class="line"></div>


        <h2>
          SISTEM INFORMASI PARKIR
        </h2>


        <div class="header-status">

          <span class="status-dot"></span>

          SYSTEM ONLINE

        </div>

      </div>


      <!-- ===================================
           FORM AREA
      ==================================== -->

      <div class="form-container">


        <div class="welcome-area">

          <div>

            <h3>
              Selamat Datang
            </h3>

            <p class="subtitle">
              Masuk untuk mengakses sistem parkir
            </p>

          </div>

          <div class="welcome-line"></div>

        </div>


        <!-- =================================
             ROLE SELECTOR
        ================================== -->

        <div class="role-section">

          <label class="role-label">
            MASUK SEBAGAI
          </label>


          <div class="role-options">


            <!-- SUPER ADMIN -->

            <button
              type="button"
              class="role-card"
              :class="{
                active:
                  selectedRole === 'super_admin'
              }"
              @click="
                selectRole('super_admin')
              "
            >

              <div class="role-icon admin-icon">
                👑
              </div>


              <div class="role-info">

                <strong>
                  Super Admin
                </strong>

                <span>
                  Akses pengelolaan sistem
                </span>

              </div>


              <div
                v-if="
                  selectedRole ===
                  'super_admin'
                "
                class="check"
              >
                ✓
              </div>

            </button>


            <!-- PETUGAS -->

            <button
              type="button"
              class="role-card"
              :class="{
                active:
                  selectedRole === 'petugas'
              }"
              @click="
                selectRole('petugas')
              "
            >

              <div class="role-icon officer-icon">
                🛡️
              </div>


              <div class="role-info">

                <strong>
                  Petugas
                </strong>

                <span>
                  Akses operasional parkir
                </span>

              </div>


              <div
                v-if="
                  selectedRole ===
                  'petugas'
                "
                class="check"
              >
                ✓
              </div>

            </button>

          </div>

        </div>


        <!-- =================================
             ERROR
        ================================== -->

        <transition name="error">

          <div
            v-if="error"
            class="error-message"
          >

            <span class="error-icon">
              !
            </span>

            <span>
              {{ error }}
            </span>

          </div>

        </transition>


        <!-- =================================
             EMAIL
        ================================== -->

        <div class="form-group">

          <label>
            EMAIL
          </label>


          <div class="input-wrapper">

            <span class="input-icon">
              ✉
            </span>


            <input
              ref="emailInput"
              v-model="email"
              type="email"
              placeholder="Masukkan email"
              autocomplete="email"
              @keyup.enter="login"
            />

          </div>

        </div>


        <!-- =================================
             PASSWORD
        ================================== -->

        <div class="form-group">

          <label>
            PASSWORD
          </label>


          <div class="input-wrapper">

            <span class="input-icon">
              🔒
            </span>


            <input
              ref="passwordInput"
              v-model="password"
              :type="
                showPassword
                  ? 'text'
                  : 'password'
              "
              placeholder="Masukkan password"
              autocomplete="current-password"
              @keyup.enter="login"
            />


            <!-- SHOW / HIDE -->

            <button
              type="button"
              class="password-button"
              :aria-label="
                showPassword
                  ? 'Sembunyikan password'
                  : 'Tampilkan password'
              "
              @click="
                showPassword =
                  !showPassword
              "
            >

              {{
                showPassword
                  ? "◉"
                  : "◌"
              }}

            </button>

          </div>

        </div>


        <!-- =================================
             FORGOT PASSWORD
        ================================== -->

        <div class="forgot-wrapper">

          <button
            type="button"
            class="forgot-button"
            @click="forgotPassword"
          >

            <span class="forgot-icon">
              ↗
            </span>

            <span>
              Forgot Password?
            </span>

          </button>

        </div>


        <!-- =================================
             LOGIN BUTTON
        ================================== -->

        <button
          type="button"
          class="login-button"
          :disabled="loading"
          @click="login"
        >

          <span
            v-if="!loading"
            class="login-button-content"
          >

            <span>
              MASUK KE SISTEM
            </span>

            <span class="arrow">
              →
            </span>

          </span>


          <span
            v-else
            class="loading-content"
          >

            <span class="spinner"></span>

            Memproses...

          </span>

        </button>


        <!-- =================================
             SECURITY INFO
        ================================== -->

        <div class="security-info">

          <span class="security-icon">
            🔐
          </span>

          <span>
            Akses aman & terlindungi
          </span>

        </div>


        <!-- =================================
             FOOTER
        ================================== -->

        <div class="login-footer">

          <span class="footer-line"></span>

          <span>
            E-PARKIR
          </span>

          <span class="footer-line"></span>

        </div>


        <p class="copyright">
          © 2026 Sistem Informasi Parkir
        </p>

      </div>

    </div>

  </div>

</template>


<style scoped>

/* =========================================
   RESET
========================================= */

* {
  box-sizing: border-box;
}


/* =========================================
   PAGE
========================================= */

.login-page {

  min-height: 100vh;

  width: 100%;

  display: flex;

  justify-content: center;

  align-items: center;

  position: relative;

  overflow: hidden;

  padding: 30px 20px;

  background:
    radial-gradient(
      circle at 15% 15%,
      rgba(45, 212, 191, 0.08),
      transparent 30%
    ),
    radial-gradient(
      circle at 85% 85%,
      rgba(59, 130, 246, 0.12),
      transparent 32%
    ),
    linear-gradient(
      135deg,
      #070b14 0%,
      #0b1220 42%,
      #111d32 72%,
      #172a45 100%
    );

  font-family:
    Inter,
    ui-sans-serif,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;

}


/* =========================================
   BACKGROUND GRID
========================================= */

.background-grid {

  position: absolute;

  inset: 0;

  pointer-events: none;

  opacity: 0.22;

  background-image:
    linear-gradient(
      rgba(255,255,255,0.025) 1px,
      transparent 1px
    ),
    linear-gradient(
      90deg,
      rgba(255,255,255,0.025) 1px,
      transparent 1px
    );

  background-size: 45px 45px;

}


/* =========================================
   BACKGROUND GLOW
========================================= */

.background-glow {

  position: absolute;

  border-radius: 50%;

  filter: blur(10px);

  pointer-events: none;

}

.glow-one {

  width: 420px;

  height: 420px;

  top: -230px;

  left: -160px;

  background:
    rgba(
      37,
      99,
      235,
      0.18
    );

}

.glow-two {

  width: 360px;

  height: 360px;

  right: -180px;

  bottom: -170px;

  background:
    rgba(
      14,
      165,
      233,
      0.13
    );

}


/* =========================================
   DECORATIVE SHAPES
========================================= */

.background-shape {

  position: absolute;

  border-radius: 50%;

  pointer-events: none;

  border:
    1px solid
    rgba(
      255,
      255,
      255,
      0.025
    );

}

.shape-one {

  width: 520px;

  height: 520px;

  left: -300px;

  top: -220px;

}

.shape-two {

  width: 620px;

  height: 620px;

  right: -350px;

  bottom: -350px;

}

.shape-three {

  width: 180px;

  height: 180px;

  right: 9%;

  top: 8%;

  background:
    rgba(
      255,
      255,
      255,
      0.015
    );

}


/* =========================================
   LOGIN CARD
========================================= */

.login-card {

  width: 100%;

  max-width: 440px;

  position: relative;

  z-index: 5;

  overflow: hidden;

  border-radius: 20px;

  background:
    rgba(
      13,
      20,
      34,
      0.97
    );

  border:
    1px solid
    rgba(
      148,
      163,
      184,
      0.15
    );

  box-shadow:
    0 35px 90px
    rgba(
      0,
      0,
      0,
      0.48
    ),
    0 0 0 1px
    rgba(
      255,
      255,
      255,
      0.015
    );

}


/* =========================================
   HEADER
========================================= */

.login-header {

  position: relative;

  overflow: hidden;

  text-align: center;

  color: white;

  padding:
    32px 25px 27px;

  background:
    radial-gradient(
      circle at 50% -40%,
      rgba(
        59,
        130,
        246,
        0.28
      ),
      transparent 55%
    ),
    linear-gradient(
      145deg,
      #111d31 0%,
      #172a46 55%,
      #0e182a 100%
    );

  border-bottom:
    1px solid
    rgba(
      255,
      255,
      255,
      0.07
    );

}

.header-top-line {

  position: absolute;

  top: 0;

  left: 0;

  right: 0;

  height: 2px;

  background:
    linear-gradient(
      90deg,
      transparent,
      #3b82f6,
      #38bdf8,
      #3b82f6,
      transparent
    );

}


/* =========================================
   LOGO
========================================= */

.logo {

  width: 54px;

  height: 54px;

  margin:
    0 auto 11px;

  display: flex;

  align-items: center;

  justify-content: center;

  border-radius: 14px;

  background:
    linear-gradient(
      145deg,
      rgba(
        59,
        130,
        246,
        0.25
      ),
      rgba(
        30,
        64,
        175,
        0.15
      )
    );

  border:
    1px solid
    rgba(
      96,
      165,
      250,
      0.35
    );

  box-shadow:
    0 8px 25px
    rgba(
      37,
      99,
      235,
      0.18
    );

}

.logo span {

  color: #dbeafe;

  font-size: 24px;

  font-weight: 900;

  letter-spacing: -1px;

}


/* =========================================
   HEADER TEXT
========================================= */

.login-header h1 {

  margin: 0;

  color: #f8fafc;

  font-size: 23px;

  font-weight: 900;

  letter-spacing: 3px;

}

.login-header .location {

  margin:
    4px 0 14px;

  color: #94a3b8;

  font-size: 9px;

  font-weight: 800;

  letter-spacing: 2px;

}

.line {

  width: 48px;

  height: 2px;

  margin:
    0 auto 12px;

  border-radius: 20px;

  background:
    linear-gradient(
      90deg,
      #2563eb,
      #38bdf8
    );

}

.login-header h2 {

  margin: 0;

  color: #cbd5e1;

  font-size: 10px;

  font-weight: 700;

  letter-spacing: 1.4px;

}


/* =========================================
   SYSTEM STATUS
========================================= */

.header-status {

  display: inline-flex;

  align-items: center;

  gap: 6px;

  margin-top: 13px;

  padding:
    5px 9px;

  border-radius: 20px;

  color: #86efac;

  background:
    rgba(
      34,
      197,
      94,
      0.07
    );

  border:
    1px solid
    rgba(
      34,
      197,
      94,
      0.14
    );

  font-size: 7px;

  font-weight: 800;

  letter-spacing: 1px;

}

.status-dot {

  width: 5px;

  height: 5px;

  border-radius: 50%;

  background: #4ade80;

  box-shadow:
    0 0 8px
    rgba(
      74,
      222,
      128,
      0.8
    );

}


/* =========================================
   FORM CONTAINER
========================================= */

.form-container {

  padding:
    28px 30px 23px;

}


/* =========================================
   WELCOME
========================================= */

.welcome-area {

  width: 100%;

  margin-bottom: 22px;

  text-align: center;

}

.form-container h3 {

  margin: 0;

  color: #f1f5f9;

  font-size: 23px;

  font-weight: 800;

  letter-spacing: -0.5px;

  text-align: center;

}

.subtitle {

  margin:
    6px 0 0;

  color: #64748b;

  font-size: 10px;

  font-weight: 500;

  text-align: center;

}

.welcome-line {

  width: 55px;

  height: 2px;

  margin:
    14px auto 0;

  border-radius: 20px;

  background:
    linear-gradient(
      90deg,
      #2563eb,
      #38bdf8
    );

  box-shadow:
    0 0 10px
    rgba(
      37,
      99,
      235,
      0.18
    );

}



/* =========================================
   ROLE SECTION
========================================= */

.role-section {

  margin-bottom: 19px;

}

.role-label {

  display: block;

  margin-bottom: 8px;

  color: #94a3b8;

  font-size: 9px;

  font-weight: 800;

  letter-spacing: 0.8px;

}

.role-options {

  display: flex;

  flex-direction: column;

  gap: 8px;

}


/* =========================================
   ROLE CARD
========================================= */

.role-card {

  width: 100%;

  min-height: 61px;

  position: relative;

  display: flex;

  align-items: center;

  padding:
    10px 12px;

  border:
    1px solid
    rgba(
      148,
      163,
      184,
      0.14
    );

  border-radius: 11px;

  background:
    linear-gradient(
      135deg,
      rgba(
        30,
        41,
        59,
        0.75
      ),
      rgba(
        15,
        23,
        42,
        0.9
      )
    );

  color: white;

  text-align: left;

  cursor: pointer;

  transition:
    transform 0.2s ease,
    border-color 0.2s ease,
    background 0.2s ease,
    box-shadow 0.2s ease;

}

.role-card:hover {

  transform:
    translateY(-1px);

  border-color:
    rgba(
      96,
      165,
      250,
      0.35
    );

  background:
    linear-gradient(
      135deg,
      rgba(
        30,
        64,
        105,
        0.5
      ),
      rgba(
        15,
        23,
        42,
        0.95
      )
    );

}

.role-card.active {

  border-color:
    rgba(
      59,
      130,
      246,
      0.65
    );

  background:
    linear-gradient(
      135deg,
      rgba(
        30,
        64,
        110,
        0.65
      ),
      rgba(
        15,
        32,
        58,
        0.95
      )
    );

  box-shadow:
    0 0 0 1px
    rgba(
      59,
      130,
      246,
      0.08
    ),
    0 8px 25px
    rgba(
      15,
      23,
      42,
      0.25
    );

}


/* =========================================
   ROLE ICON
========================================= */

.role-icon {

  width: 40px;

  height: 40px;

  flex-shrink: 0;

  display: flex;

  align-items: center;

  justify-content: center;

  margin-right: 11px;

  border-radius: 10px;

  background:
    rgba(
      255,
      255,
      255,
      0.06
    );

  border:
    1px solid
    rgba(
      255,
      255,
      255,
      0.06
    );

  font-size: 17px;

}

.admin-icon {

  box-shadow:
    inset 0 0 15px
    rgba(
      251,
      191,
      36,
      0.05
    );

}

.officer-icon {

  box-shadow:
    inset 0 0 15px
    rgba(
      59,
      130,
      246,
      0.08
    );

}


/* =========================================
   ROLE INFO
========================================= */

.role-info {

  display: flex;

  flex-direction: column;

  gap: 3px;

}

.role-info strong {

  color: #e2e8f0;

  font-size: 11px;

  font-weight: 800;

}

.role-info span {

  color: #64748b;

  font-size: 9px;

}


/* =========================================
   CHECK
========================================= */

.check {

  position: absolute;

  right: 12px;

  width: 21px;

  height: 21px;

  display: flex;

  align-items: center;

  justify-content: center;

  border-radius: 50%;

  background:
    linear-gradient(
      135deg,
      #2563eb,
      #0ea5e9
    );

  color: white;

  font-size: 10px;

  font-weight: 900;

  box-shadow:
    0 4px 12px
    rgba(
      37,
      99,
      235,
      0.35
    );

}


/* =========================================
   ERROR
========================================= */

.error-message {

  display: flex;

  align-items: center;

  gap: 9px;

  margin-bottom: 16px;

  padding:
    10px 12px;

  border-radius: 9px;

  background:
    rgba(
      127,
      29,
      29,
      0.18
    );

  border:
    1px solid
    rgba(
      248,
      113,
      113,
      0.2
    );

  color: #fca5a5;

  font-size: 10px;

  font-weight: 600;

}

.error-icon {

  width: 18px;

  height: 18px;

  flex-shrink: 0;

  display: flex;

  align-items: center;

  justify-content: center;

  border-radius: 50%;

  background: #dc2626;

  color: white;

  font-size: 10px;

  font-weight: 900;

}


/* =========================================
   ERROR TRANSITION
========================================= */

.error-enter-active,
.error-leave-active {

  transition:
    opacity 0.2s ease,
    transform 0.2s ease;

}

.error-enter-from,
.error-leave-to {

  opacity: 0;

  transform:
    translateY(-5px);

}


/* =========================================
   FORM GROUP
========================================= */

.form-group {

  margin-bottom: 16px;

}

.form-group label {

  display: block;

  margin-bottom: 7px;

  color: #94a3b8;

  font-size: 9px;

  font-weight: 800;

  letter-spacing: 0.8px;

}


/* =========================================
   INPUT
========================================= */

.input-wrapper {

  position: relative;

  width: 100%;

}

.input-icon {

  position: absolute;

  left: 13px;

  top: 50%;

  z-index: 1;

  transform:
    translateY(-50%);

  color: #64748b;

  font-size: 13px;

  opacity: 0.8;

}

.input-wrapper input {

  width: 100%;

  height: 45px;

  padding:
    0 43px
    0 38px;

  outline: none;

  border:
    1px solid
    rgba(
      148,
      163,
      184,
      0.15
    );

  border-radius: 9px;

  background:
    rgba(
      15,
      23,
      42,
      0.8
    );

  color: #e2e8f0;

  font-size: 11px;

  transition:
    border-color 0.2s ease,
    background 0.2s ease,
    box-shadow 0.2s ease;

}

.input-wrapper input::placeholder {

  color: #475569;

}

.input-wrapper input:hover {

  border-color:
    rgba(
      148,
      163,
      184,
      0.25
    );

}

.input-wrapper input:focus {

  border-color:
    rgba(
      59,
      130,
      246,
      0.7
    );

  background:
    rgba(
      15,
      23,
      42,
      0.95
    );

  box-shadow:
    0 0 0 3px
    rgba(
      59,
      130,
      246,
      0.08
    );

}


/* =========================================
   PASSWORD BUTTON
========================================= */

.password-button {

  position: absolute;

  right: 10px;

  top: 50%;

  transform:
    translateY(-50%);

  width: 27px;

  height: 27px;

  display: flex;

  align-items: center;

  justify-content: center;

  border: none;

  border-radius: 6px;

  background: transparent;

  color: #64748b;

  cursor: pointer;

  font-size: 15px;

  transition:
    color 0.2s ease,
    background 0.2s ease;

}

.password-button:hover {

  color: #93c5fd;

  background:
    rgba(
      59,
      130,
      246,
      0.08
    );

}


/* =========================================
   FORGOT PASSWORD
========================================= */

.forgot-wrapper {

  display: flex;

  justify-content: flex-end;

  margin-top: -5px;

  margin-bottom: 18px;

}

.forgot-button {

  display: inline-flex;

  align-items: center;

  gap: 5px;

  padding: 4px 2px;

  border: none;

  background: transparent;

  color: #60a5fa;

  font-size: 10px;

  font-weight: 700;

  cursor: pointer;

  transition:
    color 0.2s ease,
    transform 0.2s ease;

}

.forgot-button:hover {

  color: #93c5fd;

  transform:
    translateX(-1px);

}

.forgot-icon {

  display: inline-flex;

  font-size: 11px;

}


/* =========================================
   LOGIN BUTTON
========================================= */

.login-button {

  width: 100%;

  height: 45px;

  border: none;

  border-radius: 9px;

  background:
    linear-gradient(
      135deg,
      #2563eb 0%,
      #1d4ed8 45%,
      #0369a1 100%
    );

  color: white;

  font-size: 10px;

  font-weight: 900;

  letter-spacing: 1px;

  cursor: pointer;

  box-shadow:
    0 10px 25px
    rgba(
      37,
      99,
      235,
      0.22
    );

  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    filter 0.2s ease;

}

.login-button:hover:not(:disabled) {

  transform:
    translateY(-2px);

  filter:
    brightness(1.08);

  box-shadow:
    0 14px 30px
    rgba(
      37,
      99,
      235,
      0.32
    );

}

.login-button:active:not(:disabled) {

  transform:
    translateY(0);

}

.login-button:disabled {

  opacity: 0.6;

  cursor: not-allowed;

}


/* =========================================
   LOGIN BUTTON CONTENT
========================================= */

.login-button-content {

  display: flex;

  align-items: center;

  justify-content: center;

  gap: 10px;

}

.arrow {

  font-size: 15px;

  line-height: 1;

  transition:
    transform 0.2s ease;

}

.login-button:hover
.arrow {

  transform:
    translateX(3px);

}


/* =========================================
   LOADING
========================================= */

.loading-content {

  display: flex;

  align-items: center;

  justify-content: center;

  gap: 8px;

}

.spinner {

  width: 14px;

  height: 14px;

  border:
    2px solid
    rgba(
      255,
      255,
      255,
      0.25
    );

  border-top-color: white;

  border-radius: 50%;

  animation:
    spin 0.7s linear infinite;

}

@keyframes spin {

  to {

    transform:
      rotate(360deg);

  }

}


/* =========================================
   SECURITY INFO
========================================= */

.security-info {

  display: flex;

  align-items: center;

  justify-content: center;

  gap: 6px;

  margin-top: 15px;

  color: #475569;

  font-size: 8px;

  font-weight: 600;

}

.security-icon {

  font-size: 9px;

  opacity: 0.7;

}


/* =========================================
   FOOTER
========================================= */

.login-footer {

  display: flex;

  align-items: center;

  gap: 10px;

  margin-top: 20px;

  color: #475569;

  font-size: 7px;

  font-weight: 800;

  letter-spacing: 1.5px;

}

.footer-line {

  height: 1px;

  flex: 1;

  background:
    linear-gradient(
      90deg,
      transparent,
      rgba(
        148,
        163,
        184,
        0.12
      )
    );

}

.footer-line:last-child {

  background:
    linear-gradient(
      90deg,
      rgba(
        148,
        163,
        184,
        0.12
      ),
      transparent
    );

}

.copyright {

  text-align: center;

  margin:
    7px 0 0;

  color: #334155;

  font-size: 7px;

}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 500px) {

  .login-page {

    padding: 18px;

  }

  .login-card {

    max-width: 400px;

    border-radius: 17px;

  }

  .login-header {

    padding:
      28px 20px 25px;

  }

  .form-container {

    padding:
      25px 22px 20px;

  }

}


@media (max-height: 700px) {

  .login-page {

    align-items: flex-start;

    padding-top: 20px;

    padding-bottom: 20px;

    overflow-y: auto;

  }

}

</style>

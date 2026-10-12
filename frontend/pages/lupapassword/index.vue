<script setup lang="ts">
import { ref } from "vue";
import axios from "axios";

const config = useRuntimeConfig();

const api = axios.create({
  baseURL: config.public.apiBase,
});

const step = ref(1);

const email = ref("");
const phone = ref("");
const otp = ref("");
const newPassword = ref("");
const confirmPassword = ref("");

const loading = ref(false);
const error = ref("");
const success = ref("");

const showPassword = ref(false);
const showConfirmPassword = ref(false);



const clearMessage = () => {
  error.value = "";
  success.value = "";
};

const sendOtp = async () => {
  clearMessage();

  if (!email.value.trim()) {
    error.value = "Email wajib diisi.";
    return;
  }

  if (!phone.value.trim()) {
    error.value = "Nomor telepon wajib diisi.";
    return;
  }

  loading.value = true;

  try {
    const response = await api.post("/forgot-password/send-otp", {
      email: email.value.trim(),
      phone: phone.value.trim(),
    });

    success.value =
      response.data?.message ||
      "Kode OTP berhasil dikirim ke email.";

    step.value = 2;
  } catch (err: any) {
    console.error("SEND OTP ERROR:", err);

    error.value =
      err.response?.data?.message ||
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.errors?.phone?.[0] ||
      "Gagal mengirim kode OTP.";
  } finally {
    loading.value = false;
  }
};

const verifyOtp = async () => {
  clearMessage();

  if (!otp.value.trim()) {
    error.value = "Kode OTP wajib diisi.";
    return;
  }

  if (otp.value.trim().length !== 6) {
    error.value = "Kode OTP harus terdiri dari 6 angka.";
    return;
  }

  loading.value = true;

  try {
    const response = await api.post("/forgot-password/verify-otp", {
      email: email.value.trim(),
      phone: phone.value.trim(),
      otp: otp.value.trim(),
    });

    success.value =
      response.data?.message ||
      "Kode OTP berhasil diverifikasi.";

    step.value = 3;
  } catch (err: any) {
    console.error("VERIFY OTP ERROR:", err);

    error.value =
      err.response?.data?.message ||
      "Kode OTP tidak valid atau sudah kedaluwarsa.";
  } finally {
    loading.value = false;
  }
};

const resetPassword = async () => {
  clearMessage();

  if (!newPassword.value) {
    error.value = "Password baru wajib diisi.";
    return;
  }

  if (newPassword.value.length < 8) {
    error.value = "Password minimal 8 karakter.";
    return;
  }

  if (!confirmPassword.value) {
    error.value = "Konfirmasi password wajib diisi.";
    return;
  }

  if (newPassword.value !== confirmPassword.value) {
    error.value = "Konfirmasi password tidak cocok.";
    return;
  }

  loading.value = true;

  try {
    const response = await api.post("/forgot-password/reset", {
      email: email.value.trim(),
      phone: phone.value.trim(),
      otp: otp.value.trim(),
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    });

    success.value =
      response.data?.message ||
      "Password berhasil diubah.";

    setTimeout(() => {
      navigateTo("/");
    }, 1500);
  } catch (err: any) {
    console.error("RESET PASSWORD ERROR:", err);

    error.value =
      err.response?.data?.message ||
      err.response?.data?.errors?.password?.[0] ||
      "Gagal mengubah password.";
  } finally {
    loading.value = false;
  }
};

const backStep = () => {
  clearMessage();

  if (step.value > 1) {
    step.value--;
  }
};

const backToLogin = () => {
  navigateTo("/");
};
</script>

<template>
  <div class="login-page">
    <div class="background-grid"></div>
    <div class="background-glow glow-one"></div>
    <div class="background-glow glow-two"></div>
    <div class="background-shape shape-one"></div>
    <div class="background-shape shape-two"></div>
    <div class="background-shape shape-three"></div>

    <div class="login-card">
      <div class="login-header">
        <div class="header-top-line"></div>

        <div class="logo">
          <span>P</span>
        </div>

        <h1>PARKIR</h1>
        <p class="location">PLAZA ANDALAS</p>

        <div class="line"></div>

        <h2>SISTEM INFORMASI PARKIR</h2>

        <div class="header-status">
          <span class="status-dot"></span>
          SYSTEM ONLINE
        </div>
      </div>

      <div class="form-container">
        <div class="welcome-area">
          <div>
            <h3>Lupa Password</h3>
            <p class="subtitle">
              Reset password akun sistem parkir
            </p>
          </div>

          <div class="welcome-line"></div>
        </div>

        <div class="steps">
          <div :class="['step-item', { active: step >= 1 }]">
            <span>1</span>
            <small>Data Akun</small>
          </div>

          <div class="step-line"></div>

          <div :class="['step-item', { active: step >= 2 }]">
            <span>2</span>
            <small>OTP</small>
          </div>

          <div class="step-line"></div>

          <div :class="['step-item', { active: step >= 3 }]">
            <span>3</span>
            <small>Password</small>
          </div>
        </div>

        <transition name="fade">
          <div v-if="error" class="message error-message">
            <span class="message-icon">!</span>
            <span>{{ error }}</span>
          </div>
        </transition>

        <transition name="fade">
          <div v-if="success" class="message success-message">
            <span class="message-icon">✓</span>
            <span>{{ success }}</span>
          </div>
        </transition>

        <!-- STEP 1 -->
        <div v-if="step === 1">
          <div class="form-group">
            <label>EMAIL</label>

            <div class="input-wrapper">
              <span class="input-icon">✉</span>

              <input
                v-model="email"
                type="email"
                placeholder="Masukkan email"
                autocomplete="email"
                @keyup.enter="sendOtp"
              />
            </div>
          </div>

          <div class="form-group">
            <label>NOMOR TELEPON</label>

            <div class="input-wrapper">
              <span class="input-icon">☎</span>

              <input
                v-model="phone"
                type="tel"
                placeholder="Masukkan nomor telepon"
                autocomplete="tel"
                @keyup.enter="sendOtp"
              />
            </div>
          </div>

          <button
            type="button"
            class="login-button"
            :disabled="loading"
            @click="sendOtp"
          >
            <span v-if="!loading" class="login-button-content">
              <span>KIRIM KODE OTP</span>
              <span class="arrow">→</span>
            </span>

            <span v-else class="loading-content">
              <span class="spinner"></span>
              Mengirim...
            </span>
          </button>
        </div>

        <!-- STEP 2 -->
        <div v-else-if="step === 2">
          <div class="otp-info">
            Masukkan 6 digit kode OTP yang dikirim ke email
            <strong>{{ email }}</strong>.
          </div>

          <div class="form-group">
            <label>KODE OTP</label>

            <div class="input-wrapper">
              <span class="input-icon">#</span>

              <input
                v-model="otp"
                type="text"
                inputmode="numeric"
                maxlength="6"
                placeholder="Masukkan 6 digit OTP"
                autocomplete="one-time-code"
                @input="otp = otp.replace(/\D/g, '')"
                @keyup.enter="verifyOtp"
              />
            </div>
          </div>

          <button
            type="button"
            class="login-button"
            :disabled="loading"
            @click="verifyOtp"
          >
            <span v-if="!loading" class="login-button-content">
              <span>VERIFIKASI OTP</span>
              <span class="arrow">→</span>
            </span>

            <span v-else class="loading-content">
              <span class="spinner"></span>
              Memverifikasi...
            </span>
          </button>

          <button
            type="button"
            class="secondary-button"
            :disabled="loading"
            @click="backStep"
          >
            ← KEMBALI
          </button>
        </div>

        <!-- STEP 3 -->
        <div v-else>
          <div class="form-group">
            <label>PASSWORD BARU</label>

            <div class="input-wrapper">
              <span class="input-icon">🔒</span>

              <input
                v-model="newPassword"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Masukkan password baru"
                autocomplete="new-password"
                @keyup.enter="resetPassword"
              />

              <button
                type="button"
                class="password-button"
                @click="showPassword = !showPassword"
              >
                {{ showPassword ? "◉" : "◌" }}
              </button>
            </div>
          </div>

          <div class="form-group">
            <label>KONFIRMASI PASSWORD</label>

            <div class="input-wrapper">
              <span class="input-icon">🔒</span>

              <input
                v-model="confirmPassword"
                :type="showConfirmPassword ? 'text' : 'password'"
                placeholder="Ulangi password baru"
                autocomplete="new-password"
                @keyup.enter="resetPassword"
              />

              <button
                type="button"
                class="password-button"
                @click="
                  showConfirmPassword = !showConfirmPassword
                "
              >
                {{ showConfirmPassword ? "◉" : "◌" }}
              </button>
            </div>
          </div>

          <button
            type="button"
            class="login-button"
            :disabled="loading"
            @click="resetPassword"
          >
            <span v-if="!loading" class="login-button-content">
              <span>UBAH PASSWORD</span>
              <span class="arrow">→</span>
            </span>

            <span v-else class="loading-content">
              <span class="spinner"></span>
              Menyimpan...
            </span>
          </button>

          <button
            type="button"
            class="secondary-button"
            :disabled="loading"
            @click="backStep"
          >
            ← KEMBALI
          </button>
        </div>

        <div class="back-login">
          <button type="button" @click="backToLogin">
            ← Kembali ke halaman login
          </button>
        </div>

        <div class="security-info">
          <span class="security-icon">🔐</span>
          <span>Akses aman & terlindungi</span>
        </div>

        <div class="login-footer">
          <span class="footer-line"></span>
          <span>E-PARKIR</span>
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
* {
  box-sizing: border-box;
}

.login-page {
  min-height: 100vh;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 30px 15px;
  position: relative;
  overflow: hidden;
  background:
    radial-gradient(circle at 20% 20%, rgba(0, 183, 255, 0.12), transparent 35%),
    radial-gradient(circle at 80% 80%, rgba(0, 119, 255, 0.12), transparent 35%),
    linear-gradient(135deg, #06111f, #081a2e 50%, #04101d);
  color: #fff;
}

.background-grid {
  position: absolute;
  inset: 0;
  opacity: 0.12;
  background-image:
    linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
  background-size: 40px 40px;
}

.background-glow {
  position: absolute;
  width: 350px;
  height: 350px;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
}

.glow-one {
  top: -150px;
  left: -100px;
  background: rgba(0, 174, 255, 0.16);
}

.glow-two {
  bottom: -180px;
  right: -100px;
  background: rgba(0, 89, 255, 0.14);
}

.background-shape {
  position: absolute;
  border: 1px solid rgba(80, 190, 255, 0.1);
  transform: rotate(45deg);
}

.shape-one {
  width: 150px;
  height: 150px;
  top: 10%;
  left: 8%;
}

.shape-two {
  width: 100px;
  height: 100px;
  bottom: 15%;
  right: 12%;
}

.shape-three {
  width: 60px;
  height: 60px;
  top: 20%;
  right: 15%;
}

.login-card {
  width: 100%;
  max-width: 520px;
  position: relative;
  z-index: 2;
  overflow: hidden;
  border: 1px solid rgba(86, 183, 255, 0.18);
  border-radius: 24px;
  background: rgba(7, 19, 34, 0.94);
  box-shadow:
    0 30px 80px rgba(0, 0, 0, 0.45),
    inset 0 1px 0 rgba(255,255,255,.05);
  backdrop-filter: blur(20px);
}

.login-header {
  text-align: center;
  padding: 28px 30px 22px;
  background: linear-gradient(
    180deg,
    rgba(18, 46, 76, 0.8),
    rgba(7, 19, 34, 0.15)
  );
}

.header-top-line {
  width: 70px;
  height: 3px;
  margin: 0 auto 20px;
  border-radius: 10px;
  background: linear-gradient(90deg, #00b7ff, #4d8dff);
}

.logo {
  width: 54px;
  height: 54px;
  margin: 0 auto 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(78, 191, 255, 0.45);
  border-radius: 14px;
  background: rgba(0, 164, 255, 0.1);
  color: #67d4ff;
  font-size: 27px;
  font-weight: 800;
}

.login-header h1 {
  margin: 0;
  letter-spacing: 5px;
  font-size: 28px;
  font-weight: 800;
}

.location {
  margin: 5px 0 15px;
  color: #70a6c7;
  font-size: 11px;
  letter-spacing: 4px;
}

.line {
  width: 120px;
  height: 1px;
  margin: 0 auto 14px;
  background: linear-gradient(90deg, transparent, #3f9ed0, transparent);
}

.login-header h2 {
  margin: 0;
  color: #a9c8dd;
  font-size: 11px;
  letter-spacing: 2px;
  font-weight: 600;
}

.header-status {
  margin-top: 15px;
  color: #6fd5a3;
  font-size: 10px;
  letter-spacing: 2px;
}

.status-dot {
  display: inline-block;
  width: 7px;
  height: 7px;
  margin-right: 7px;
  border-radius: 50%;
  background: #43d78d;
  box-shadow: 0 0 12px rgba(67, 215, 141, .8);
}

.form-container {
  padding: 28px 32px 25px;
}

.welcome-area {
  display: flex;
  align-items: end;
  justify-content: space-between;
  margin-bottom: 22px;
}

.welcome-area h3 {
  margin: 0;
  font-size: 23px;
}

.subtitle {
  margin: 6px 0 0;
  color: #7893a8;
  font-size: 12px;
}

.welcome-line {
  width: 55px;
  height: 3px;
  margin-bottom: 5px;
  border-radius: 5px;
  background: #159ed8;
}

.steps {
  display: flex;
  align-items: flex-start;
  margin-bottom: 25px;
}

.step-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 7px;
  color: #526b7d;
  font-size: 10px;
  text-align: center;
}

.step-item span {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #31495b;
  border-radius: 50%;
  background: #0b1a2a;
  font-size: 12px;
  font-weight: 700;
}

.step-item.active {
  color: #72d5ff;
}

.step-item.active span {
  border-color: #159ed8;
  background: rgba(21, 158, 216, .15);
  box-shadow: 0 0 16px rgba(21, 158, 216, .2);
}

.step-line {
  flex: 1;
  height: 1px;
  margin-top: 15px;
  background: #263d50;
}

.form-group {
  margin-bottom: 17px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #82a2b8;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.5px;
}

.input-wrapper {
  position: relative;
}

.input-icon {
  position: absolute;
  top: 50%;
  left: 14px;
  z-index: 1;
  transform: translateY(-50%);
  color: #5f94b4;
  font-size: 15px;
}

.input-wrapper input {
  width: 100%;
  height: 50px;
  padding: 0 45px;
  border: 1px solid #22394d;
  border-radius: 12px;
  outline: none;
  background: rgba(3, 13, 24, .8);
  color: #fff;
  font-size: 13px;
  transition: .2s;
}

.input-wrapper input::placeholder {
  color: #50687a;
}

.input-wrapper input:focus {
  border-color: #159ed8;
  box-shadow: 0 0 0 3px rgba(21, 158, 216, .1);
}

.password-button {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  border: 0;
  background: transparent;
  color: #6e9bb5;
  cursor: pointer;
  font-size: 16px;
}

.login-button {
  width: 100%;
  min-height: 52px;
  margin-top: 5px;
  border: 0;
  border-radius: 12px;
  background: linear-gradient(135deg, #0b87bd, #1761c7);
  color: #fff;
  cursor: pointer;
  box-shadow: 0 10px 25px rgba(16, 112, 190, .2);
  transition: .2s;
}

.login-button:hover:not(:disabled) {
  transform: translateY(-1px);
  filter: brightness(1.08);
}

.login-button:disabled {
  opacity: .6;
  cursor: not-allowed;
}

.login-button-content,
.loading-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 1.5px;
}

.arrow {
  font-size: 20px;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,.35);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .7s linear infinite;
}

.secondary-button {
  width: 100%;
  min-height: 45px;
  margin-top: 10px;
  border: 1px solid #29465d;
  border-radius: 11px;
  background: rgba(13, 32, 49, .6);
  color: #8bb1c8;
  cursor: pointer;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
}

.secondary-button:hover:not(:disabled) {
  border-color: #159ed8;
  color: #fff;
}

.secondary-button:disabled {
  opacity: .5;
  cursor: not-allowed;
}

.message {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 17px;
  padding: 11px 13px;
  border-radius: 10px;
  font-size: 11px;
  line-height: 1.5;
}

.error-message {
  border: 1px solid rgba(255, 85, 85, .25);
  background: rgba(180, 30, 30, .1);
  color: #ff9d9d;
}

.success-message {
  border: 1px solid rgba(64, 210, 139, .25);
  background: rgba(34, 170, 105, .1);
  color: #7ce0ae;
}

.message-icon {
  width: 20px;
  height: 20px;
  flex: 0 0 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: rgba(255,255,255,.08);
  font-weight: 800;
}

.otp-info {
  margin-bottom: 18px;
  padding: 12px 14px;
  border-left: 3px solid #159ed8;
  border-radius: 7px;
  background: rgba(21, 158, 216, .07);
  color: #82a3b8;
  font-size: 11px;
  line-height: 1.6;
}

.otp-info strong {
  color: #b9ddf1;
}

.back-login {
  margin-top: 18px;
  text-align: center;
}

.back-login button {
  border: 0;
  background: transparent;
  color: #6491aa;
  cursor: pointer;
  font-size: 11px;
}

.back-login button:hover {
  color: #71c8ef;
}

.security-info {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 7px;
  margin-top: 22px;
  color: #587387;
  font-size: 10px;
}

.login-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 22px;
  color: #4c687a;
  font-size: 9px;
  letter-spacing: 3px;
}

.footer-line {
  width: 45px;
  height: 1px;
  background: #243a4c;
}

.copyright {
  margin: 9px 0 0;
  text-align: center;
  color: #3d5566;
  font-size: 9px;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity .2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 600px) {
  .login-page {
    padding: 15px 10px;
  }

  .login-card {
    border-radius: 18px;
  }

  .login-header {
    padding: 24px 20px 18px;
  }

  .form-container {
    padding: 24px 20px;
  }

  .login-header h1 {
    font-size: 24px;
  }

  .steps small {
    font-size: 8px;
  }
}
</style>

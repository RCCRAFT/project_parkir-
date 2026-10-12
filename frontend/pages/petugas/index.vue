<script setup lang="ts">

import { ref, onMounted } from "vue";
import axios from "axios";

/* =====================================================
   SIDEBAR
===================================================== */

const sidebarOpen = ref(true);

/* =====================================================
   USER
===================================================== */

const user = ref({
  name: "Petugas",
  role: "petugas",
});

/* =====================================================
   DATA DASHBOARD
===================================================== */

const totalMember = ref(0);
const kendaraanMasuk = ref(0);
const pendapatan = ref(0);
const totalTransaksi = ref(0);
const loading = ref(true);

/* =====================================================
   API
===================================================== */

const api = axios.create({
  baseURL: "http://127.0.0.1:8000/api",
  headers: {
    Accept: "application/json",
  },
});

/* =====================================================
   FORMAT RUPIAH
===================================================== */

const formatRupiah = (value: number) => {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(value || 0);
};

/* =====================================================
   AMBIL DATA DASHBOARD
===================================================== */

const loadDashboard = async () => {

  loading.value = true;

  try {

    const token = localStorage.getItem("token");

    const headers: Record<string, string> = {};

    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    /* =================================================
       AMBIL MEMBER
    ================================================= */

    try {

      const memberResponse = await api.get("/member", {
        headers,
      });

      let members = memberResponse.data;

      if (members?.data) {
        members = members.data;
      }

      if (Array.isArray(members)) {
        totalMember.value = members.length;
      } else {
        totalMember.value = 0;
      }

    } catch (error) {

      console.error(
        "Gagal mengambil data member:",
        error
      );

      totalMember.value = 0;
    }

    /* =================================================
       AMBIL TIKET PARKIR
    ================================================= */

    try {

      const tiketResponse = await api.get("/tiket", {
        headers,
      });

      let tiket = tiketResponse.data;

      if (tiket?.data) {
        tiket = tiket.data;
      }

      if (Array.isArray(tiket)) {

        kendaraanMasuk.value = tiket.filter(
          (item: any) => {

            const status = String(
              item?.status || ""
            )
              .trim()
              .toLowerCase();

            return status === "masuk";
          }
        ).length;

      } else {

        kendaraanMasuk.value = 0;
      }

    } catch (error) {

      console.error(
        "Gagal mengambil data tiket parkir:",
        error
      );

      kendaraanMasuk.value = 0;
    }

    /* =================================================
       AMBIL TRANSAKSI
    ================================================= */

    try {

      const transaksiResponse = await api.get(
        "/transaksi",
        {
          headers,
        }
      );

      let transaksi = transaksiResponse.data;

      if (transaksi?.data) {
        transaksi = transaksi.data;
      }

      if (Array.isArray(transaksi)) {

        totalTransaksi.value =
          transaksi.length;

        pendapatan.value =
          transaksi.reduce(
            (
              total: number,
              item: any
            ) => {

              const bayar = Number(
                item?.total_bayar || 0
              );

              return total + bayar;

            },
            0
          );

      } else {

        totalTransaksi.value = 0;
        pendapatan.value = 0;
      }

    } catch (error) {

      console.error(
        "Gagal mengambil data transaksi:",
        error
      );

      totalTransaksi.value = 0;
      pendapatan.value = 0;
    }

  } catch (error) {

    console.error(
      "Gagal memuat dashboard:",
      error
    );

  } finally {

    loading.value = false;
  }
};

/* =====================================================
   NAVIGASI
===================================================== */

const goTo = async (path: string) => {
  await navigateTo(path);
};

/* =====================================================
   LOGOUT
===================================================== */

const logout = async () => {

  try {

    const token =
      localStorage.getItem("token");

    if (token) {

      await api.post(
        "/logout",
        {},
        {
          headers: {
            Authorization:
              `Bearer ${token}`,
          },
        }
      );
    }

  } catch (error) {

    console.error(
      "Logout API gagal:",
      error
    );

  } finally {

    localStorage.removeItem("token");
    localStorage.removeItem("user");

    await navigateTo("/");
  }
};

/* =====================================================
   ON MOUNTED
===================================================== */

onMounted(async () => {

  try {

    const savedUser =
      localStorage.getItem("user");

  if (savedUser) {
  const data = JSON.parse(savedUser);

  user.value = {
    name:
      data?.name &&
      data.name.length > 1
        ? data.name
        : "Petugas",

    role:
      data?.role || "petugas",
  };
}

  } catch (error) {

    console.error(
      "Gagal membaca data user:",
      error
    );
  }

  await loadDashboard();
});

</script>


<template>

  <div class="petugas-dashboard">

    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside
      class="sidebar"
      :class="{ collapsed: !sidebarOpen }"
    >

      <!-- LOGO -->

      <div class="logo-area">

        <div class="logo-icon">
          P
        </div>

        <div
          v-if="sidebarOpen"
          class="logo-text"
        >

          <h1>
            PARKIR
          </h1>

          <span>
            PLAZA ANDALAS
          </span>

        </div>

      </div>


      <!-- PROFILE -->

      <div
        v-if="sidebarOpen"
        class="profile"
      >

        <div class="profile-avatar">

          {{
            user.name
              ? user.name
                  .substring(0, 1)
                  .toUpperCase()
              : "P"
          }}

        </div>

        <div class="profile-info">

          <strong>
            {{ user.name }}
          </strong>

          <small>
            ● Online
          </small>

        </div>

      </div>


      <!-- NAVIGATION -->

      <nav class="navigation">

        <div
          v-if="sidebarOpen"
          class="menu-title"
        >
          MENU UTAMA
        </div>


        <!-- DASHBOARD -->

        <button
          type="button"
          class="nav-item active"
          @click="goTo('/petugas')"
        >

          <span class="nav-icon">
            ▦
          </span>

          <span v-if="sidebarOpen">
            Dashboard
          </span>

        </button>


        <!-- MEMBER -->

        <button
          type="button"
          class="nav-item"
          @click="goTo('/petugas/member')"
        >

          <span class="nav-icon">
            ♙
          </span>

          <span v-if="sidebarOpen">
            Kelola Member
          </span>

        </button>


        <!-- TRANSAKSI -->

        <button
          type="button"
          class="nav-item"
          @click="goTo('/petugas/transaksi')"
        >

          <span class="nav-icon">
            ▤
          </span>

          <span v-if="sidebarOpen">
            Kelola Transaksi
          </span>

        </button>


        <!-- SCAN KELUAR -->

        <button
          type="button"
          class="nav-item"
          @click="goTo('/petugas/scan-keluar')"
        >

          <span class="nav-icon">
            ⇥
          </span>

          <span v-if="sidebarOpen">
            Scan Keluar
          </span>

        </button>


        <!-- LAPORAN -->

        <button
          type="button"
          class="nav-item"
          @click="goTo('/petugas/laporan')"
        >

          <span class="nav-icon">
            ▥
          </span>

          <span v-if="sidebarOpen">
            Laporan
          </span>

        </button>

      </nav>


      <!-- SIDEBAR BOTTOM -->

      <div class="sidebar-bottom">

        <div
          v-if="sidebarOpen"
          class="system-info"
        >

          <div class="info-small-icon">
            ✓
          </div>

          <div>

            <strong>
              Sistem Informasi
            </strong>

            <small>
              Panel Petugas Parkir
            </small>

          </div>

        </div>


        <!-- LOGOUT -->

        <button
          type="button"
          class="logout-button"
          @click="logout"
        >

          <span>
            ⇥
          </span>

          <span v-if="sidebarOpen">
            Logout
          </span>

        </button>

      </div>

    </aside>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="main">


      <!-- =================================================
           TOPBAR
      ================================================== -->

      <header class="topbar">

        <button
          type="button"
          class="toggle-button"
          @click="
            sidebarOpen = !sidebarOpen
          "
        >
          ☰
        </button>


        <div class="breadcrumb">

          <span>
            PARKIR
          </span>

          <b>
            /
          </b>

          <strong>
            Dashboard Petugas
          </strong>

        </div>


        <div class="top-right">

          <div class="system-online">

            <span></span>

            Sistem Online

          </div>


          <div class="date">

            {{
              new Date().toLocaleDateString(
                "id-ID",
                {
                  day: "2-digit",
                  month: "long",
                  year: "numeric",
                }
              )
            }}

          </div>


          <div class="top-avatar">

            {{
              user.name
                ? user.name
                    .substring(0, 1)
                    .toUpperCase()
                : "P"
            }}

          </div>

        </div>

      </header>


      <!-- =================================================
           CONTENT
      ================================================== -->

      <section class="content">


        <!-- WELCOME -->

        <div class="welcome">

          <p class="eyebrow">
            DASHBOARD PETUGAS
          </p>

          <h2>
            Selamat Datang,
            {{ user.name }}
          </h2>

          <p class="welcome-text">
            Pantau aktivitas parkir dan kelola
            transaksi dengan mudah dari satu tempat.
          </p>

        </div>


        <!-- =================================================
             HERO
        ================================================== -->

        <section class="hero">

          <div class="hero-content">

            <p class="hero-label">
              PARKIR PLAZA ANDALAS
            </p>

            <h1>
              Parking Control
              <br />
              Center
            </h1>

            <p>
              Pantau member, kendaraan,
              transaksi, pembayaran,
              dan aktivitas parkir dari
              satu dashboard.
            </p>

          </div>


          <div class="hero-icon">
            P
          </div>

        </section>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="stats-grid">


          <!-- TOTAL MEMBER -->

          <div class="stat-card">

            <div class="stat-top">

              <div class="stat-icon dark">
                ♙
              </div>

              <span>
                Aktif
              </span>

            </div>

            <p class="stat-label">
              Total Member
            </p>

            <h3 v-if="!loading">
              {{ totalMember }}
            </h3>

            <h3 v-else>
              ...
            </h3>

            <small>
              Member terdaftar
            </small>

          </div>


          <!-- KENDARAAN -->

          <div class="stat-card">

            <div class="stat-top">

              <div class="stat-icon silver">
                🚗
              </div>

              <span>
                Saat ini
              </span>

            </div>

            <p class="stat-label">
              Kendaraan Masuk
            </p>

            <h3 v-if="!loading">
              {{ kendaraanMasuk }}
            </h3>

            <h3 v-else>
              ...
            </h3>

            <small>
              Kendaraan masih parkir
            </small>

          </div>


          <!-- PENDAPATAN -->

          <div class="stat-card">

            <div class="stat-top">

              <div class="stat-icon money">
                Rp
              </div>

              <span>
                Total
              </span>

            </div>

            <p class="stat-label">
              Pendapatan
            </p>

            <h3
              v-if="!loading"
              class="money-text"
            >
              {{ formatRupiah(pendapatan) }}
            </h3>

            <h3
              v-else
              class="money-text"
            >
              ...
            </h3>

            <small>
              Total pembayaran
            </small>

          </div>


          <!-- TRANSAKSI -->

          <div class="stat-card">

            <div class="stat-top">

              <div class="stat-icon transaction">
                #
              </div>

              <span>
                Total
              </span>

            </div>

            <p class="stat-label">
              Transaksi
            </p>

            <h3 v-if="!loading">
              {{ totalTransaksi }}
            </h3>

            <h3 v-else>
              ...
            </h3>

            <small>
              Transaksi tersimpan
            </small>

          </div>

        </div>


        <!-- =================================================
             QUICK ACCESS
        ================================================== -->

        <div class="section-title">

          <span>
            MENU UTAMA
          </span>

          <h3>
            Akses Cepat
          </h3>

        </div>


        <div class="quick-grid">


          <!-- MEMBER -->

          <button
            type="button"
            class="quick-card"
            @click="
              goTo('/petugaspm/member/')
            "
          >

            <div class="quick-icon dark">
              ♙
            </div>

            <div>

              <h3>
                Kelola Member
              </h3>

              <p>
                Kelola data member parkir.
              </p>

              <span>
                Kelola Member →
              </span>

            </div>

          </button>


          <!-- SCAN -->

          <button
            type="button"
            class="quick-card"
            @click="
              goTo('/petugas/scan-keluar')
            "
          >

            <div class="quick-icon green">
              ⇥
            </div>

            <div>

              <h3>
                Scan Keluar
              </h3>

              <p>
                Proses kendaraan keluar dan pembayaran.
              </p>

              <span>
                Scan Keluar →
              </span>

            </div>

          </button>


          <!-- LAPORAN -->

          <button
            type="button"
            class="quick-card"
            @click="
              goTo('/petugas/laporan')
            "
          >

            <div class="quick-icon silver">
              ▥
            </div>

            <div>

              <h3>
                Laporan Parkir
              </h3>

              <p>
                Lihat laporan dan aktivitas parkir.
              </p>

              <span>
                Lihat Laporan →
              </span>

            </div>

          </button>

        </div>


        <!-- =================================================
             INFORMATION
        ================================================== -->

        <section class="information-panel">

          <div class="information-left">

            <div class="information-icon">
              i
            </div>

            <div>

              <strong>
                Informasi Sistem
              </strong>

              <p>
                Gunakan menu Scan Keluar untuk
                memproses kendaraan keluar serta pembayaran.
              </p>

            </div>

          </div>

        </section>


      </section>

    </main>

  </div>

</template>


<style scoped>

/* =========================================================
   GLOBAL
========================================================= */

* {
  box-sizing: border-box;
}

.petugas-dashboard {
  min-height: 100vh;

  display: flex;

  background:
    linear-gradient(
      135deg,
      #e2e8f0 0%,
      #f8fafc 45%,
      #cbd5e1 100%
    );

  color: #0f172a;

  font-family:
    Inter,
    ui-sans-serif,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {
  width: 275px;

  min-height: 100vh;

  background:
    linear-gradient(
      180deg,
      #020617 0%,
      #111827 52%,
      #1e293b 100%
    );

  color: white;

  display: flex;

  flex-direction: column;

  padding: 22px 15px;

  transition:
    width 0.25s ease;

  flex-shrink: 0;

  box-shadow:
    8px 0 30px
    rgba(15, 23, 42, 0.16);
}

.sidebar.collapsed {
  width: 82px;
}


/* =========================================================
   LOGO
========================================================= */

.logo-area {
  display: flex;

  align-items: center;

  gap: 12px;

  padding:
    5px 10px 25px;
}

.logo-icon {
  width: 55px;
  height: 55px;

  border-radius: 15px;

  background:
    linear-gradient(
      135deg,
      #ffffff,
      #94a3b8
    );

  color: #020617;

  display: flex;

  align-items: center;

  justify-content: center;

  font-size: 19px;

  font-weight: 950;

  flex-shrink: 0;

  box-shadow:
    0 8px 20px
    rgba(255,255,255,0.08);
}

.logo-text h1 {
  margin: 0;

  font-size: 21px;

  font-weight: 950;

  letter-spacing: 1.5px;
}

.logo-text span {
  display: block;

  margin-top: 4px;

  color: #94a3b8;

  font-size: 9px;

  font-weight: 800;

  letter-spacing: 1.5px;
}


/* =========================================================
   PROFILE
========================================================= */

.profile {
  background:
    rgba(255,255,255,0.05);

  border:
    1px solid
    rgba(255,255,255,0.08);

  border-radius: 15px;

  padding: 14px;

  display: flex;

  align-items: center;

  gap: 12px;

  margin-bottom: 25px;

  backdrop-filter:
    blur(12px);
}

.profile-avatar {
  width: 49px;
  height: 49px;

  border-radius: 50%;

  background:
    linear-gradient(
      135deg,
      #f8fafc,
      #64748b
    );

  color: #0f172a;

  display: flex;

  justify-content: center;

  align-items: center;

  font-size: 16px;

  font-weight: 900;

  flex-shrink: 0;
}

.profile-info strong {
  display: block;

  font-size: 14px;

  font-weight: 850;
}

.profile-info small {
  display: block;

  color: #22c55e;

  font-size: 10px;

  margin-top: 4px;
}


/* =========================================================
   NAVIGATION
========================================================= */

.navigation {
  flex: 1;
}

.menu-title {
  color: #64748b;

  font-size: 9px;

  font-weight: 900;

  letter-spacing: 1.7px;

  margin:
    25px 13px 12px;
}

.nav-item {
  width: 100%;

  border: 0;

  background: transparent;

  color: #94a3b8;

  border-radius: 11px;

  padding: 12px;

  display: flex;

  align-items: center;

  gap: 13px;

  cursor: pointer;

  text-align: left;

  margin-bottom: 5px;

  transition:
    0.2s;

  font-size: 13px;

  font-weight: 650;
}

.nav-item:hover {
  background:
    rgba(255,255,255,0.07);

  color: white;

  transform:
    translateX(2px);
}

.nav-item.active {
  background:
    linear-gradient(
      135deg,
      #ffffff,
      #cbd5e1
    );

  color: #0f172a;

  box-shadow:
    0 8px 22px
    rgba(255,255,255,0.10);
}

.nav-icon {
  width: 27px;

  text-align: center;

  font-size: 17px;

  flex-shrink: 0;
}


/* =========================================================
   SIDEBAR BOTTOM
========================================================= */

.sidebar-bottom {
  border-top:
    1px solid
    rgba(255,255,255,0.08);

  padding-top: 15px;
}

.system-info {
  display: flex;

  align-items: center;

  gap: 9px;

  padding: 10px;

  margin-bottom: 8px;
}

.info-small-icon {
  width: 30px;
  height: 30px;

  border-radius: 9px;

  background:
    rgba(255,255,255,0.07);

  color: #cbd5e1;

  display: flex;

  align-items: center;

  justify-content: center;

  font-weight: 900;

  font-size: 12px;
}

.system-info strong {
  display: block;

  font-size: 10px;

  font-weight: 800;
}

.system-info small {
  display: block;

  color: #64748b;

  font-size: 8px;

  margin-top: 2px;
}

.logout-button {
  width: 100%;

  border: 0;

  background: transparent;

  color: #f87171;

  display: flex;

  align-items: center;

  gap: 12px;

  padding: 11px;

  border-radius: 9px;

  cursor: pointer;

  font-weight: 700;

  font-size: 12px;

  transition: 0.2s;
}

.logout-button:hover {
  background:
    rgba(248,113,113,0.08);
}


/* =========================================================
   MAIN
========================================================= */

.main {
  flex: 1;

  min-width: 0;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {
  height: 70px;

  background:
    rgba(255,255,255,0.94);

  backdrop-filter:
    blur(16px);

  border-bottom:
    1px solid #cbd5e1;

  display: flex;

  align-items: center;

  padding:
    0 32px;

  gap: 18px;

  box-shadow:
    0 5px 20px
    rgba(15,23,42,0.05);
}

.toggle-button {
  width: 40px;
  height: 40px;

  border:
    1px solid #cbd5e1;

  background:
    #f8fafc;

  border-radius: 10px;

  cursor: pointer;

  font-size: 18px;

  color: #334155;

  transition: 0.2s;
}

.toggle-button:hover {
  background: #e2e8f0;

  transform:
    scale(1.03);
}

.breadcrumb {
  display: flex;

  align-items: center;

  gap: 8px;

  font-size: 13px;
}

.breadcrumb span {
  color: #94a3b8;

  font-weight: 800;
}

.breadcrumb b {
  color: #cbd5e1;
}

.breadcrumb strong {
  color: #0f172a;

  font-weight: 850;
}

.top-right {
  margin-left: auto;

  display: flex;

  align-items: center;

  gap: 17px;
}

.system-online {
  color: #475569;

  font-size: 11px;

  font-weight: 750;
}

.system-online span {
  display: inline-block;

  width: 8px;
  height: 8px;

  background: #22c55e;

  border-radius: 50%;

  margin-right: 6px;

  box-shadow:
    0 0 0 4px
    rgba(34,197,94,0.10);
}

.date {
  color: #64748b;

  font-size: 11px;
}

.top-avatar {
  width: 39px;
  height: 39px;

  border-radius: 50%;

  background:
    linear-gradient(
      135deg,
      #0f172a,
      #64748b
    );

  color: white;

  display: flex;

  justify-content: center;

  align-items: center;

  font-size: 11px;

  font-weight: 850;
}


/* =========================================================
   CONTENT
========================================================= */

.content {
  width: 100%;

  max-width: 1500px;

  margin: 0 auto;

  padding:
    38px 42px;
}


/* =========================================================
   WELCOME
========================================================= */

.welcome {
  margin-bottom: 25px;
}

.eyebrow {
  color: #64748b;

  font-size: 9px;

  font-weight: 950;

  letter-spacing: 1.8px;

  margin:
    0 0 9px;
}

.welcome h2 {
  margin: 0;

  font-size: 30px;

  font-weight: 900;

  color: #020617;

  letter-spacing: -0.8px;
}

.welcome-text {
  color: #64748b;

  margin:
    8px 0 0;

  font-size: 13px;
}


/* =========================================================
   HERO
========================================================= */

.hero {
  min-height: 200px;

  background:
    linear-gradient(
      120deg,
      #020617 0%,
      #1e293b 50%,
      #64748b 100%
    );

  border-radius: 20px;

  padding:
    32px 38px;

  display: flex;

  align-items: center;

  justify-content: space-between;

  overflow: hidden;

  margin-bottom: 28px;

  box-shadow:
    0 15px 35px
    rgba(15,23,42,0.18);

  position: relative;
}

.hero::before {
  content: "";

  position: absolute;

  width: 350px;
  height: 350px;

  right: -100px;
  top: -170px;

  border-radius: 50%;

  border:
    1px solid
    rgba(255,255,255,0.12);
}

.hero::after {
  content: "";

  position: absolute;

  width: 250px;
  height: 250px;

  right: 80px;
  bottom: -190px;

  border-radius: 50%;

  border:
    1px solid
    rgba(255,255,255,0.08);
}

.hero-content {
  color: white;

  min-width: 0;

  position: relative;

  z-index: 2;
}

.hero-label {
  margin:
    0 0 7px;

  font-size: 9px;

  font-weight: 900;

  letter-spacing: 1.6px;

  color: #cbd5e1;
}

.hero h1 {
  margin: 0;

  font-size: 30px;

  line-height: 1.18;

  font-weight: 900;

  letter-spacing: -0.5px;
}

.hero p:last-child {
  margin:
    9px 0 0;

  font-size: 12px;

  max-width: 560px;

  line-height: 1.6;

  color: #cbd5e1;
}

.hero-icon {
  width: 90px;
  height: 90px;

  border-radius: 24px;

  background:
    linear-gradient(
      135deg,
      rgba(255,255,255,0.18),
      rgba(255,255,255,0.05)
    );

  border:
    1px solid
    rgba(255,255,255,0.16);

  display: flex;

  align-items: center;

  justify-content: center;

  color: white;

  font-size: 35px;

  font-weight: 950;

  margin-right: 25px;

  flex-shrink: 0;

  position: relative;

  z-index: 2;
}


/* =========================================================
   STATISTICS
========================================================= */

.stats-grid {
  display: grid;

  grid-template-columns:
    repeat(4, 1fr);

  gap: 20px;

  margin-bottom: 32px;
}

.stat-card {
  background:
    linear-gradient(
      145deg,
      #ffffff 0%,
      #f8fafc 55%,
      #e2e8f0 100%
    );

  border:
    1px solid #ffffff;

  border-radius: 17px;

  padding: 21px;

  min-height: 185px;

  box-shadow:
    0 7px 20px
    rgba(15,23,42,0.08);

  transition:
    transform 0.2s,
    box-shadow 0.2s;
}

.stat-card:hover {
  transform:
    translateY(-3px);

  box-shadow:
    0 14px 28px
    rgba(15,23,42,0.12);
}

.stat-top {
  display: flex;

  justify-content: space-between;

  align-items: center;

  margin-bottom: 20px;
}

.stat-top > span {
  color: #64748b;

  font-size: 10px;

  font-weight: 800;
}

.stat-icon {
  width: 45px;
  height: 45px;

  border-radius: 12px;

  display: flex;

  align-items: center;

  justify-content: center;

  font-size: 15px;

  font-weight: 900;
}

.stat-icon.dark {
  background:
    #0f172a;

  color: white;
}

.stat-icon.silver {
  background:
    #e2e8f0;

  color: #334155;
}

.stat-icon.money {
  background:
    #f1f5f9;

  color: #0f172a;

  font-size: 11px;
}

.stat-icon.transaction {
  background:
    #cbd5e1;

  color: #0f172a;
}

.stat-label {
  color: #64748b;

  margin:
    0 0 7px;

  font-size: 11px;
}

.stat-card h3 {
  margin: 0;

  color: #020617;

  font-size: 26px;

  font-weight: 950;

  letter-spacing: -0.5px;
}

.stat-card h3.money-text {
  font-size: 21px;
}

.stat-card small {
  display: block;

  color: #94a3b8;

  font-size: 9px;

  margin-top: 9px;
}


/* =========================================================
   SECTION TITLE
========================================================= */

.section-title {
  margin-bottom: 15px;
}

.section-title span {
  display: block;

  color: #64748b;

  font-size: 9px;

  font-weight: 950;

  letter-spacing: 1.5px;

  margin-bottom: 4px;
}

.section-title h3 {
  margin: 0;

  font-size: 19px;

  font-weight: 850;

  color: #0f172a;
}


/* =========================================================
   QUICK MENU
========================================================= */

.quick-grid {
  display: grid;

  grid-template-columns:
    repeat(4, 1fr);

  gap: 18px;

  margin-bottom: 22px;
}

.quick-card {
  border:
    1px solid #e2e8f0;

  background:
    linear-gradient(
      145deg,
      #ffffff,
      #f8fafc
    );

  border-radius: 16px;

  padding: 20px;

  display: flex;

  align-items: flex-start;

  gap: 14px;

  text-align: left;

  cursor: pointer;

  transition:
    0.2s;

  box-shadow:
    0 4px 12px
    rgba(15,23,42,0.05);
}

.quick-card:hover {
  transform:
    translateY(-3px);

  border-color:
    #94a3b8;

  box-shadow:
    0 12px 24px
    rgba(15,23,42,0.10);
}

.quick-icon {
  width: 43px;
  height: 43px;

  border-radius: 11px;

  display: flex;

  align-items: center;

  justify-content: center;

  font-size: 15px;

  flex-shrink: 0;

  font-weight: 900;
}

.quick-icon.dark {
  background:
    #0f172a;

  color: white;
}

.quick-icon.silver {
  background:
    #e2e8f0;

  color: #334155;
}

.quick-icon.green {
  background:
    #dcfce7;

  color: #166534;
}

.quick-card h3 {
  margin:
    1px 0 5px;

  color: #0f172a;

  font-size: 13px;

  font-weight: 850;
}

.quick-card p {
  margin: 0;

  color: #64748b;

  font-size: 9px;

  line-height: 1.5;
}

.quick-card span {
  display: inline-block;

  margin-top: 10px;

  color: #334155;

  font-size: 9px;

  font-weight: 900;
}


/* =========================================================
   INFORMATION
========================================================= */

.information-panel {
  background:
    linear-gradient(
      135deg,
      #ffffff,
      #f1f5f9
    );

  border:
    1px solid #ffffff;

  border-radius: 16px;

  padding:
    18px 21px;

  box-shadow:
    0 6px 18px
    rgba(15,23,42,0.05);
}

.information-left {
  display: flex;

  align-items: center;

  gap: 12px;
}

.information-icon {
  width: 35px;
  height: 35px;

  border-radius: 10px;

  background:
    #e2e8f0;

  color: #334155;

  display: flex;

  align-items: center;

  justify-content: center;

  font-weight: 950;

  flex-shrink: 0;
}

.information-panel strong {
  display: block;

  font-size: 11px;

  font-weight: 850;
}

.information-panel p {
  margin:
    4px 0 0;

  color: #64748b;

  font-size: 9px;

  line-height: 1.5;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1200px) {

  .stats-grid {
    grid-template-columns:
      repeat(2, 1fr);
  }

  .quick-grid {
    grid-template-columns:
      repeat(2, 1fr);
  }

}


@media (max-width: 900px) {

  .sidebar {
    width: 230px;
  }

  .content {
    padding:
      25px 20px;
  }

  .hero h1 {
    font-size: 25px;
  }

}


@media (max-width: 650px) {

  .sidebar {
    width: 82px;
  }

  .logo-text,
  .profile,
  .menu-title,
  .nav-item span:not(.nav-icon),
  .system-info,
  .logout-button span:last-child {
    display: none;
  }

  .topbar {
    padding:
      0 15px;
  }

  .breadcrumb,
  .system-online,
  .date {
    display: none;
  }

  .content {
    padding:
      20px 15px;
  }

  .welcome h2 {
    font-size: 23px;
  }

  .hero {
    padding:
      25px;
  }

  .hero h1 {
    font-size: 22px;
  }

  .hero-icon {
    display: none;
  }

  .stats-grid,
  .quick-grid {
    grid-template-columns:
      1fr;
  }

}

</style>
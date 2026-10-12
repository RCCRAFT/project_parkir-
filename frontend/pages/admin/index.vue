<script setup lang="ts">
import { ref, onMounted } from "vue";

const sidebarOpen = ref(true);

// =========================================================
// USER
// =========================================================
const user = ref({
  name: "Super Admin",
  role: "super_admin",
});

onMounted(() => {
  user.value = {
    name: "Super Admin",
    role: "super_admin",
  };
});

// =========================================================
// NAVIGASI
// =========================================================
const goTo = async (path: string) => {
  await navigateTo(path);
};

// =========================================================
// LOGOUT
// =========================================================
const logout = () => {
  // Hapus data login
  localStorage.removeItem("token");
  localStorage.removeItem("user");

  sessionStorage.removeItem("token");
  sessionStorage.removeItem("user");

  // Kembali ke halaman login
  window.location.href = "/";
};
</script>

<template>
  <div class="admin-dashboard">

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
          <h1>PARKIR</h1>

          <span>
            SISTEM INFORMASI PARKIR
          </span>
        </div>

      </div>


      <!-- PROFILE -->
      <div
        v-if="sidebarOpen"
        class="profile"
      >

        <div class="profile-avatar">
          SA
        </div>

        <div class="profile-info">

          <strong>
            {{ user.name }}
          </strong>

          <small>
            Administrator Sistem
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


        <!-- =================================================
             DASHBOARD
        ================================================== -->
        <button
          type="button"
          class="nav-item active"
          @click="goTo('/admin/admin')"
        >

          <span class="nav-icon">
            ⌂
          </span>

          <span v-if="sidebarOpen">
            Dashboard
          </span>

        </button>


        <!-- =================================================
             KELOLA PETUGAS
        ================================================== -->
        <button
          type="button"
          class="nav-item"
          @click="goTo('/admin/petugas')"
        >

          <span class="nav-icon">
            ♙
          </span>

          <span v-if="sidebarOpen">
            Kelola Petugas
          </span>

        </button>


        <!-- =================================================
             LAPORAN ADMIN
        ================================================== -->
        <button
          type="button"
          class="nav-item"
          @click="goTo('/admin/laporan')"
        >

          <span class="nav-icon">
            ▤
          </span>

          <span v-if="sidebarOpen">
            Laporan
          </span>

        </button>

      </nav>


      <!-- =====================================================
           SIDEBAR BOTTOM
      ====================================================== -->
      <div class="sidebar-bottom">

        <div
          v-if="sidebarOpen"
          class="system-info"
        >

          <div class="info-small-icon">
            !
          </div>

          <div>

            <strong>
              Sistem Informasi
            </strong>

            <small>
              Panel Super Admin
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
            ↪
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

      <!-- ===================================================
           TOPBAR
      ==================================================== -->
      <header class="topbar">

        <button
          type="button"
          class="toggle-button"
          @click="sidebarOpen = !sidebarOpen"
        >
          ☰
        </button>


        <!-- BREADCRUMB -->
        <div class="breadcrumb">

          <span>
            PARKIR
          </span>

          <b>
            /
          </b>

          <strong>
            Dashboard
          </strong>

        </div>


        <!-- TOP RIGHT -->
        <div class="top-right">

          <div class="system-online">

            <span></span>

            Sistem Online

          </div>

          <div class="top-avatar">
            SA
          </div>

        </div>

      </header>


      <!-- =====================================================
           CONTENT
      ====================================================== -->
      <section class="content">

        <!-- WELCOME -->
        <div class="welcome">

          <p class="eyebrow">
            DASHBOARD
          </p>

          <h2>
            Selamat Datang, Super Admin
          </h2>

          <p class="welcome-text">
            Kelola petugas, pantau sistem, dan lihat laporan
            parkir dari satu tempat.
          </p>

        </div>


        <!-- =================================================
             HERO
        ================================================== -->
        <section class="hero">

          <div class="hero-content">

            <p class="hero-label">
              SISTEM INFORMASI PARKIR
            </p>

            <h1>
              Dashboard Super Admin
            </h1>

            <p>
              Kelola petugas, pantau aktivitas sistem,
              dan akses laporan parkir dengan mudah.
            </p>

          </div>


          <div class="hero-icon">
            ⚙
          </div>

        </section>


        <!-- =================================================
             SECTION TITLE
        ================================================== -->
        <div class="section-title">

          <span>
            MENU UTAMA
          </span>

          <h3>
            Akses Sistem
          </h3>

        </div>


        <!-- =================================================
             MENU GRID
        ================================================== -->
        <div class="menu-grid">


          <!-- =================================================
               KELOLA PETUGAS
          ================================================== -->
          <button
            type="button"
            class="menu-card"
            @click="goTo('/admin/petugas')"
          >

            <div class="card-icon blue">
              ♙
            </div>

            <div class="card-content">

              <h3>
                Kelola Petugas
              </h3>

              <p>
                Kelola data petugas parkir,
                akun dan informasi pengguna.
              </p>

              <span class="card-link">
                Kelola Petugas
                <b>
                  →
                </b>
              </span>

            </div>

          </button>


          <!-- =================================================
               LAPORAN ADMIN
          ================================================== -->
          <button
            type="button"
            class="menu-card"
            @click="goTo('/admin/laporan')"
          >

            <div class="card-icon green">
              ▤
            </div>

            <div class="card-content">

              <h3>
                Laporan
              </h3>

              <p>
                Lihat data dan laporan aktivitas
                parkir secara keseluruhan.
              </p>

              <span class="card-link">
                Lihat Laporan
                <b>
                  →
                </b>
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
              !
            </div>

            <div>

              <strong>
                Informasi Sistem
              </strong>

              <p>
                Gunakan menu Kelola Petugas untuk
                menambahkan, mengubah, atau menghapus
                data petugas parkir.
              </p>

            </div>

          </div>

        </section>

      </section>

    </main>

  </div>
</template>


<style scoped>

* {
  box-sizing: border-box;
}


/* =========================================================
   DASHBOARD
========================================================= */

.admin-dashboard {
  min-height: 100vh;

  display: flex;

  background: #f4f6f9;

  color: #172033;

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
   SIDEBAR  (disamakan dengan halaman petugas)
========================================================= */

.sidebar {
  width: 275px;

  min-height: 100vh;

  background: #151923;

  color: white;

  display: flex;

  flex-direction: column;

  padding: 20px;

  flex-shrink: 0;

  transition: width 0.25s ease;
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

  padding: 5px 10px 25px;
}

.logo-icon {
  width: 52px;
  height: 52px;

  border-radius: 14px;

  background: #4f6de7;

  display: flex;

  align-items: center;
  justify-content: center;

  font-size: 18px;

  font-weight: 900;

  flex-shrink: 0;
}

.logo-text h1 {
  margin: 0;

  font-size: 20px;

  font-weight: 900;

  letter-spacing: 1px;
}

.logo-text span {
  display: block;

  margin-top: 3px;

  color: #7f8da9;

  font-size: 8px;

  font-weight: 800;

  letter-spacing: 1px;
}


/* =========================================================
   PROFILE
========================================================= */

.profile {
  background: #1d2330;

  border: 1px solid #2a3140;

  border-radius: 14px;

  padding: 13px;

  display: flex;

  align-items: center;

  gap: 11px;

  margin-bottom: 24px;
}

.profile-avatar {
  width: 48px;
  height: 48px;

  border-radius: 50%;

  background: #647eea;

  display: flex;

  align-items: center;
  justify-content: center;

  font-size: 12px;

  font-weight: 900;

  flex-shrink: 0;
}

.profile-info strong {
  display: block;

  font-size: 13px;

  font-weight: 800;
}

.profile-info small {
  display: block;

  color: #8997b2;

  font-size: 9px;

  margin-top: 4px;
}


/* =========================================================
   NAVIGATION
========================================================= */

.navigation {
  flex: 1;
}

.menu-title {
  color: #71809b;

  font-size: 9px;

  font-weight: 900;

  letter-spacing: 1.5px;

  margin: 22px 12px 11px;
}

.nav-item {
  width: 100%;

  border: 0;

  background: transparent;

  color: #aeb8cb;

  border-radius: 10px;

  padding: 12px;

  display: flex;

  align-items: center;

  gap: 13px;

  cursor: pointer;

  text-align: left;

  margin-bottom: 5px;

  font-size: 12px;

  font-weight: 700;

  transition: 0.2s;
}

.nav-item:hover {
  background: #202632;

  color: white;
}

.nav-item.active {
  background: #4f6de7;

  color: white;

  box-shadow:
    0 8px 20px
    rgba(79, 109, 231, 0.25);
}

.nav-icon {
  width: 25px;

  text-align: center;

  font-size: 16px;

  flex-shrink: 0;
}


/* =========================================================
   SIDEBAR BOTTOM
========================================================= */

.sidebar-bottom {
  border-top: 1px solid #292f3b;

  padding-top: 15px;
}

.system-info {
  display: flex;

  align-items: center;

  gap: 9px;

  padding: 10px;

  margin-bottom: 7px;
}

.info-small-icon {
  width: 30px;
  height: 30px;

  border-radius: 9px;

  background: #222a38;

  color: #91a1bd;

  display: flex;

  align-items: center;
  justify-content: center;

  font-size: 12px;

  font-weight: 900;
}

.system-info strong {
  display: block;

  font-size: 10px;
}

.system-info small {
  display: block;

  color: #71809b;

  font-size: 8px;

  margin-top: 2px;
}


/* =========================================================
   LOGOUT
========================================================= */

.logout-button {
  width: 100%;

  border: 0;

  background: transparent;

  color: #ff6565;

  display: flex;

  align-items: center;

  gap: 11px;

  padding: 11px;

  border-radius: 9px;

  cursor: pointer;

  font-size: 12px;

  font-weight: 800;
}

.logout-button:hover {
  background: #28202a;
}


/* =========================================================
   MAIN
========================================================= */

.main {
  flex: 1;

  min-width: 0;
}


/* =========================================================
   TOPBAR  (disamakan dengan halaman petugas)
========================================================= */

.topbar {
  height: 70px;

  background: white;

  border-bottom: 1px solid #e5e7eb;

  display: flex;

  align-items: center;

  padding: 0 30px;

  gap: 17px;
}

.toggle-button {
  width: 39px;
  height: 39px;

  border: 1px solid #e1e5eb;

  background: white;

  border-radius: 9px;

  color: #64748b;

  cursor: pointer;

  font-size: 17px;
}

.toggle-button:hover {
  background: #f8fafc;
}


/* =========================================================
   BREADCRUMB
========================================================= */

.breadcrumb {
  display: flex;

  align-items: center;

  gap: 8px;

  font-size: 12px;
}

.breadcrumb span {
  color: #9aa5b5;

  font-weight: 800;
}

.breadcrumb b {
  color: #cbd5e1;
}

.breadcrumb strong {
  color: #172033;

  font-weight: 800;
}


/* =========================================================
   TOP RIGHT
========================================================= */

.top-right {
  margin-left: auto;

  display: flex;

  align-items: center;

  gap: 18px;
}

.system-online {
  color: #68778e;

  font-size: 10px;

  font-weight: 700;
}

.system-online span {
  display: inline-block;

  width: 7px;
  height: 7px;

  border-radius: 50%;

  background: #22c55e;

  margin-right: 5px;
}

.top-avatar {
  width: 38px;
  height: 38px;

  border-radius: 50%;

  background: #647eea;

  color: white;

  display: flex;

  align-items: center;
  justify-content: center;

  font-size: 10px;

  font-weight: 900;
}


/* =========================================================
   CONTENT
========================================================= */

.content {
  width: 100%;

  max-width: 1500px;

  margin: 0 auto;

  padding: 35px 42px;
}


/* =========================================================
   WELCOME
========================================================= */

.welcome {
  margin-bottom: 23px;
}

.eyebrow {
  color: #94a3b8;

  font-size: 9px;

  font-weight: 900;

  letter-spacing: 1.5px;

  margin: 0 0 7px;
}

.welcome h2 {
  margin: 0;

  font-size: 29px;

  font-weight: 900;

  color: #172033;

  letter-spacing: -0.7px;
}

.welcome-text {
  color: #718096;

  margin: 7px 0 0;

  font-size: 11px;
}


/* =========================================================
   HERO
========================================================= */

.hero {
  min-height: 155px;

  background:
    linear-gradient(
      110deg,
      #5369db,
      #6878df
    );

  border-radius: 17px;

  padding: 27px 30px;

  display: flex;

  align-items: center;

  justify-content: space-between;

  overflow: hidden;

  margin-bottom: 25px;

  box-shadow:
    0 10px 25px rgba(79, 109, 231, 0.12);
}

.hero-content {
  color: white;

  min-width: 0;
}

.hero-label {
  margin: 0 0 6px;

  font-size: 9px;

  font-weight: 800;

  letter-spacing: 1.4px;

  opacity: 0.75;
}

.hero h1 {
  margin: 0;

  font-size: 25px;

  font-weight: 900;

  letter-spacing: -0.5px;
}

.hero p:last-child {
  margin: 8px 0 0;

  font-size: 11px;

  max-width: 500px;

  line-height: 1.6;

  opacity: 0.8;
}

.hero-icon {
  width: 72px;

  height: 72px;

  border-radius: 20px;

  background: rgba(255, 255, 255, 0.13);

  display: flex;

  align-items: center;

  justify-content: center;

  font-size: 35px;

  color: white;

  margin-right: 20px;

  flex-shrink: 0;
}


/* =========================================================
   SECTION TITLE
========================================================= */

.section-title {
  margin-bottom: 15px;
}

.section-title span {
  display: block;

  color: #94a3b8;

  font-size: 9px;

  font-weight: 900;

  letter-spacing: 1.4px;

  margin-bottom: 4px;
}

.section-title h3 {
  margin: 0;

  font-size: 18px;

  font-weight: 800;
}


/* =========================================================
   MENU GRID
========================================================= */

.menu-grid {
  display: grid;

  grid-template-columns:
    repeat(2, 1fr);

  gap: 18px;

  margin-bottom: 20px;
}


/* =========================================================
   MENU CARD
========================================================= */

.menu-card {
  border: 1px solid #e5e7eb;

  background: white;

  border-radius: 15px;

  padding: 22px;

  display: flex;

  align-items: flex-start;

  gap: 16px;

  text-align: left;

  cursor: pointer;

  transition: 0.2s;

  box-shadow:
    0 2px 6px rgba(15, 23, 42, 0.02);
}

.menu-card:hover {
  transform: translateY(-2px);

  border-color: #c7d2fe;

  box-shadow:
    0 8px 20px rgba(15, 23, 42, 0.06);
}


/* =========================================================
   CARD ICON
========================================================= */

.card-icon {
  width: 46px;

  height: 46px;

  border-radius: 12px;

  display: flex;

  align-items: center;

  justify-content: center;

  font-size: 19px;

  font-weight: 900;

  flex-shrink: 0;
}

.card-icon.blue {
  background: #eef2ff;

  color: #4f46e5;
}

.card-icon.green {
  background: #ecfdf5;

  color: #059669;
}


/* =========================================================
   CARD CONTENT
========================================================= */

.card-content {
  flex: 1;
}

.card-content h3 {
  margin: 1px 0 6px;

  color: #172033;

  font-size: 14px;

  font-weight: 800;
}

.card-content p {
  margin: 0;

  color: #94a3b8;

  font-size: 10px;

  line-height: 1.6;
}

.card-link {
  display: inline-flex;

  align-items: center;

  gap: 7px;

  margin-top: 15px;

  color: #4f6de7;

  font-size: 10px;

  font-weight: 800;
}

.card-link b {
  font-size: 15px;
}


/* =========================================================
   INFORMATION PANEL
========================================================= */

.information-panel {
  background: white;

  border: 1px solid #e5e7eb;

  border-radius: 15px;

  padding: 17px 20px;
}

.information-left {
  display: flex;

  align-items: center;

  gap: 12px;
}

.information-icon {
  width: 34px;

  height: 34px;

  border-radius: 10px;

  background: #fff7ed;

  color: #ea580c;

  display: flex;

  align-items: center;

  justify-content: center;

  font-weight: 900;

  flex-shrink: 0;
}

.information-panel strong {
  display: block;

  font-size: 11px;

  font-weight: 800;
}

.information-panel p {
  margin: 4px 0 0;

  color: #94a3b8;

  font-size: 9px;

  line-height: 1.5;
}


/* =========================================================
   RESPONSIVE  (disamakan dengan halaman petugas)
========================================================= */

@media (max-width: 900px) {

  .menu-grid {
    grid-template-columns: 1fr;
  }

}


@media (max-width: 800px) {

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

  .content {
    padding: 25px 20px;
  }

}


@media (max-width: 600px) {

  .topbar {
    padding: 0 15px;
  }

  .breadcrumb {
    display: none;
  }

  .content {
    padding: 20px 15px;
  }

  .welcome h2 {
    font-size: 24px;
  }

  .hero {
    padding: 22px;
  }

  .hero h1 {
    font-size: 21px;
  }

  .hero-icon {
    display: none;
  }

  .top-right .system-online {
    display: none;
  }

}

</style>
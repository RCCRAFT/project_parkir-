<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import { useApi } from "~/composables/useApi";

/* =========================================================
   SIDEBAR
========================================================= */

const sidebarOpen = ref(true);


/* =========================================================
   API
========================================================= */

const api = useApi();


/* =========================================================
   USER SUPER ADMIN
========================================================= */

const user = ref({
  name: "Super Admin",
  role: "Administrator Sistem",
});


/* =========================================================
   DATA PETUGAS
========================================================= */

const petugas = ref<any[]>([]);

const loading = ref(true);

const search = ref("");

const error = ref("");

const success = ref("");

/* =========================================================
   MODAL
========================================================= */

const showModal = ref(false);

const editingId = ref<number | null>(null);

const saving = ref(false);


/* =========================================================
   FORM
========================================================= */

const form = ref({
  nama: "",
  email: "",
  nomor_hp: "",
  password: "",
  role: "petugas",
  shift: "",
  status: "aktif",
});


/* =========================================================
   SHIFT
========================================================= */

const shiftOptions = [
  {
    value: "pagi",
    label: "Pagi",
    waktu: "06.00 - 14.00",
  },
  {
    value: "siang",
    label: "Siang",
    waktu: "14.00 - 22.00",
  },
  {
    value: "malam",
    label: "Malam",
    waktu: "22.00 - 06.00",
  },
];


/* =========================================================
   TOKEN
========================================================= */

const getConfig = () => {
  const token =
    typeof window !== "undefined"
      ? localStorage.getItem("token")
      : null;

  const config: any = {};

  if (token) {
    config.headers = {
      Authorization: `Bearer ${token}`,
    };
  }

  return config;
};


/* =========================================================
   AMBIL DATA PETUGAS
========================================================= */

const ambilPetugas = async () => {
  loading.value = true;

  error.value = "";

  try {
    const response = await api.get(
      "/petugas",
      getConfig()
    );

    let data = response.data;

    /*
      Jika Laravel mengembalikan:

      {
        data: [...]
      }
    */

    if (data?.data) {
      data = data.data;
    }

    if (Array.isArray(data)) {
      petugas.value = data;
    } else {
      petugas.value = [];
    }

  } catch (err: any) {

    console.error(
      "Gagal mengambil data petugas:",
      err
    );

    petugas.value = [];

    error.value =
      err?.response?.data?.message ||
      "Data petugas gagal dimuat.";

  } finally {

    loading.value = false;

  }
};


/* =========================================================
   DATA FILTER
========================================================= */

const filteredPetugas = computed(() => {

  const keyword =
    search.value
      .trim()
      .toLowerCase();

  if (!keyword) {
    return petugas.value;
  }

  return petugas.value.filter(
    (item: any) => {

      const nama = String(
        item?.nama ||
        item?.name ||
        item?.nama_petugas ||
        ""
      ).toLowerCase();

      const email = String(
        item?.email || ""
      ).toLowerCase();

      const role = String(
        item?.role || ""
      ).toLowerCase();

      const shift = String(
        item?.shift || ""
      ).toLowerCase();

      return (
        nama.includes(keyword) ||
        email.includes(keyword) ||
        role.includes(keyword) ||
        shift.includes(keyword)
      );

    }
  );
});


/* =========================================================
   FORMAT SHIFT
========================================================= */

const formatShift = (shift: any) => {

  const value =
    String(shift || "")
      .toLowerCase();

  const found =
    shiftOptions.find(
      (item) =>
        item.value === value
    );

  if (found) {
    return found.label;
  }

  return shift || "-";
};


/* =========================================================
   WAKTU SHIFT
========================================================= */

const waktuShift = (shift: any) => {

  const value =
    String(shift || "")
      .toLowerCase();

  const found =
    shiftOptions.find(
      (item) =>
        item.value === value
    );

  return found?.waktu || "";
};


/* =========================================================
   FORMAT ROLE
========================================================= */

const formatRole = (role: any) => {

  const value =
    String(role || "")
      .toLowerCase();

  if (
    value === "super_admin" ||
    value === "superadmin"
  ) {
    return "Super Admin";
  }

  if (value === "admin") {
    return "Admin";
  }

  if (value === "petugas") {
    return "Petugas";
  }

  return role || "-";
};


/* =========================================================
   STATUS
========================================================= */

const isAktif = (item: any) => {

  const status =
    String(
      item?.status ||
      "aktif"
    )
      .toLowerCase();

  return (
    status === "aktif" ||
    status === "active" ||
    status === "1"
  );
};


/* =========================================================
   ATURAN HAPUS
========================================================= */

const isAdminRole = (item: any) => {
  const role = String(item?.role || "").toLowerCase();

  return (
    role === "admin" ||
    role === "super_admin" ||
    role === "superadmin"
  );
};

// Mengembalikan alasan kenapa tidak bisa dihapus, atau "" jika boleh dihapus
const alasanTidakBisaHapus = (item: any) => {
  if (isAdminRole(item)) {
    return "Akun admin tidak dapat dihapus.";
  }

  if (isAktif(item)) {
    return "Petugas aktif tidak dapat dihapus. Ubah status menjadi Nonaktif terlebih dahulu melalui Edit.";
  }

  return "";
};

const bisaDihapus = (item: any) => !alasanTidakBisaHapus(item);


/* =========================================================
   BUKA TAMBAH
========================================================= */

const bukaTambah = () => {

  editingId.value = null;

  form.value = {
    nama: "",
    email: "",
    nomor_hp: "",
    password: "",
    role: "petugas",
    shift: "",
    status: "aktif",
  };

  error.value = "";

  success.value = "";

  showModal.value = true;
};


/* =========================================================
   BUKA EDIT
========================================================= */

const bukaEdit = (item: any) => {

  editingId.value =
    item?.id || null;

  form.value = {

    nama:
      item?.nama ||
      item?.name ||
      item?.nama_petugas ||
      "",

    email:
      item?.email ||
      "",

    nomor_hp:
      item?.nomor_hp ||
      item?.no_telp ||
      item?.no_hp ||
      "",

    password:
      "",

    /*
      Halaman ini khusus mengelola
      petugas, jadi role selalu
      dikunci ke "petugas".
    */

    role:
      "petugas",

    shift:
      item?.shift ||
      "",

    status:
      item?.status ||
      "aktif",

  };

  error.value = "";

  success.value = "";

  showModal.value = true;
};


/* =========================================================
   TUTUP MODAL
========================================================= */

const tutupModal = () => {

  if (saving.value) {
    return;
  }

  showModal.value = false;

  editingId.value = null;

};


/* =========================================================
   SIMPAN PETUGAS
========================================================= */

const simpanPetugas = async () => {

  error.value = "";

  success.value = "";

  /* =============================================
     VALIDASI
  ============================================= */

  if (!form.value.nama.trim()) {

    error.value =
      "Nama petugas wajib diisi.";

    return;
  }

  if (!form.value.email.trim()) {

    error.value =
      "Email wajib diisi.";

    return;
  }

  if (!form.value.nomor_hp.trim()) {

    error.value =
      "Nomor telepon wajib diisi.";

    return;
  }

  if (!editingId.value &&
      !form.value.password) {

    error.value =
      "Password wajib diisi.";

    return;
  }

  if (!form.value.shift) {

    error.value =
      "Shift petugas wajib dipilih.";

    return;
  }


  saving.value = true;


  try {

    /* =============================================
       DATA YANG DIKIRIM
    ============================================= */

    const payload: any = {

      nama:
        form.value.nama.trim(),

      email:
        form.value.email.trim(),

      nomor_hp:
        form.value.nomor_hp.trim(),

      /*
        Role selalu dipaksa "petugas"
        untuk halaman ini, apa pun
        isi form.value.role.
      */

      role:
        "petugas",

      shift:
        form.value.shift,

      status:
        form.value.status,

    };


    /*
      Password hanya dikirim
      kalau diisi.
    */

    if (form.value.password) {

      payload.password =
        form.value.password;

    }


    /* =============================================
       EDIT
    ============================================= */

    if (editingId.value) {

      await api.put(
        `/petugas/${editingId.value}`,
        payload,
        getConfig()
      );

      success.value =
        "Data petugas berhasil diperbarui.";

    }

    /* =============================================
       TAMBAH
    ============================================= */

    else {

      await api.post(
        "/petugas",
        payload,
        getConfig()
      );

      success.value =
        "Petugas berhasil ditambahkan.";

    }


    /* =============================================
       REFRESH
    ============================================= */

    await ambilPetugas();


    setTimeout(() => {

      showModal.value = false;

      editingId.value = null;

      success.value = "";

    }, 700);


  } catch (err: any) {

    console.error(
      "Gagal menyimpan petugas:",
      err
    );

    /*
      Ambil pesan error Laravel
    */

    const errors = err?.response?.data?.errors as
      | Record<string, string[]>
      | undefined;

    const firstKey = errors
      ? Object.keys(errors)[0]
      : undefined;

    if (errors && firstKey) {

      error.value =
        errors[firstKey]?.[0] ||
        "Data petugas gagal disimpan.";

    } else {

      error.value =
        err?.response?.data?.message ||
        "Data petugas gagal disimpan.";

    }

  } finally {

    saving.value = false;

  }
};


/* =========================================================
   HAPUS PETUGAS
========================================================= */

const hapusPetugas = async (
  item: any
) => {

  if (!item?.id) {
    return;
  }

  /* ATURAN: cek dulu boleh dihapus atau tidak */
  const alasan = alasanTidakBisaHapus(item);

  if (alasan) {
    success.value = "";
    error.value = alasan;
    return;
  }


  const nama =
    item?.nama ||
    item?.name ||
    item?.nama_petugas ||
    "petugas";


  const yakin =
    window.confirm(
      `Apakah kamu yakin ingin menghapus petugas "${nama}"?`
    );


  if (!yakin) {
    return;
  }


  try {

    error.value = "";

    await api.delete(
      `/petugas/${item.id}`,
      getConfig()
    );


    success.value =
      "Data petugas berhasil dihapus.";


    await ambilPetugas();


    setTimeout(() => {
      success.value = "";
    }, 1500);


  } catch (err: any) {

    console.error(
      "Gagal menghapus petugas:",
      err
    );

    error.value =
      err?.response?.data?.message ||
      "Data petugas gagal dihapus.";

  }
};


/* =========================================================
   NAVIGASI
========================================================= */

const goTo = async (
  path: string
) => {

  await navigateTo(path);

};


/* =========================================================
   KEMBALI
========================================================= */

const kembali = () => {

  navigateTo("/admin");

};


/* =========================================================
   LOGOUT
========================================================= */

const logout = async () => {
  try {
    await api.post("/logout");
  } catch (err) {
    console.error("Logout API gagal:", err);
  } finally {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    await navigateTo("/");
  }
};


/* =========================================================
   MOUNTED
========================================================= */

onMounted(async () => {

  try {

    const savedUser =
      localStorage.getItem("user");

    if (savedUser) {

      const data =
        JSON.parse(savedUser);

      user.value = {

        name:
          data?.name ||
          "Super Admin",

        role:
          data?.role ||
          "Administrator Sistem",

      };

    }

  } catch (err) {

    console.error(
      "Gagal membaca user:",
      err
    );

  }


  await ambilPetugas();

});

</script>


<template>

  <div
    class="admin-page"
  >


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside
      class="sidebar"
      :class="{
        collapsed:
          !sidebarOpen
      }"
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
          SA
        </div>

        <div class="profile-info">

          <strong>
            Super Admin
          </strong>

          <small>
            Administrator Sistem
          </small>

        </div>

      </div>


      <!-- MENU -->

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
          class="nav-item"
          @click="
            goTo('/admin')
          "
        >

          <span class="nav-icon">
            ⌂
          </span>

          <span
            v-if="sidebarOpen"
          >
            Dashboard
          </span>

        </button>


        <!-- KELOLA PETUGAS -->

        <button
          type="button"
          class="nav-item active"
          @click="
            goTo('/admin/petugas')
          "
        >

          <span class="nav-icon">
            ♙
          </span>

          <span
            v-if="sidebarOpen"
          >
            Kelola Petugas
          </span>

        </button>


        <!-- LAPORAN -->

        <button
          type="button"
          class="nav-item"
          @click="
            goTo('/admin/laporan')
          "
        >

          <span class="nav-icon">
            ▤
          </span>

          <span
            v-if="sidebarOpen"
          >
            Laporan
          </span>

        </button>

      </nav>


      <!-- BOTTOM -->

      <div class="sidebar-bottom">

        <div
          v-if="sidebarOpen"
          class="system-info"
        >

          <div class="info-icon">
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


        <button
          type="button"
          class="logout-button"
          @click="logout"
        >

          <span>
            ↪
          </span>

          <span
            v-if="sidebarOpen"
          >
            Logout
          </span>

        </button>

      </div>

    </aside>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="main">


      <!-- TOPBAR -->

      <header class="topbar">

        <div class="top-left">

          <button
            type="button"
            class="toggle-button"
            @click="
              sidebarOpen =
                !sidebarOpen
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
              Kelola Petugas
            </strong>

          </div>

        </div>


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


        <!-- HEADER -->

        <div class="page-header">

          <div>

            <p class="eyebrow">
              MANAJEMEN SISTEM
            </p>

            <h2>
              Kelola Petugas
            </h2>

            <p class="page-description">
              Kelola data akun, role, shift,
              dan status petugas parkir.
            </p>

          </div>


          <button
            type="button"
            class="add-button"
            @click="bukaTambah"
          >

            <span>
              +
            </span>

            Tambah Petugas

          </button>

        </div>


        <!-- SUCCESS -->

        <div
          v-if="success"
          class="alert success"
        >

          <span>
            ✓
          </span>

          {{ success }}

        </div>


        <!-- ERROR -->

        <div
          v-if="error"
          class="alert error"
        >

          <span>
            !
          </span>

          {{ error }}

        </div>


        <!-- =================================================
             STATISTIC
        ================================================== -->

        <div class="stats-grid">


          <div class="stat-card">

            <div class="stat-icon blue">
              ♙
            </div>

            <div>

              <span>
                Total Petugas
              </span>

              <strong>
                {{ petugas.length }}
              </strong>

            </div>

          </div>


          <div class="stat-card">

            <div class="stat-icon green">
              ✓
            </div>

            <div>

              <span>
                Petugas Aktif
              </span>

              <strong>
                {{
                  petugas.filter(
                    (item) =>
                      isAktif(item)
                  ).length
                }}
              </strong>

            </div>

          </div>


          <div class="stat-card">

            <div class="stat-icon orange">
              ◷
            </div>

            <div>

              <span>
                Shift Hari Ini
              </span>

              <strong>
                3
              </strong>

            </div>

          </div>

        </div>


        <!-- =================================================
             TABLE CARD
        ================================================== -->

        <section class="table-card">


          <!-- TABLE HEADER -->

          <div class="table-header">

            <div>

              <p class="table-label">
                DATA PETUGAS
              </p>

              <h3>
                Daftar Petugas
              </h3>

            </div>


            <!-- SEARCH -->

            <div class="search-box">

              <span>
                ⌕
              </span>

              <input
                v-model="search"
                type="text"
                placeholder="Cari petugas..."
              />

            </div>

          </div>


          <!-- LOADING -->

          <div
            v-if="loading"
            class="loading"
          >

            <div class="loading-spinner"></div>

            <p>
              Memuat data petugas...
            </p>

          </div>


          <!-- TABLE -->

          <div
            v-else
            class="table-wrapper"
          >

            <table>

              <thead>

                <tr>

                  <th>
                    NO
                  </th>

                  <th>
                    PETUGAS
                  </th>

                  <th>
                    EMAIL
                  </th>

                  <th>
                    NO. TELEPON
                  </th>

                  <th>
                    ROLE
                  </th>

                  <th>
                    SHIFT
                  </th>

                  <th>
                    STATUS
                  </th>

                  <th>
                    AKSI
                  </th>

                </tr>

              </thead>


              <tbody>

                <!-- DATA -->

                <tr
                  v-for="(
                    item,
                    index
                  ) in filteredPetugas"
                  :key="item.id || index"
                >

                  <!-- NO -->

                  <td>
                    <span class="number">
                      {{ index + 1 }}
                    </span>
                  </td>


                  <!-- PETUGAS -->

                  <td>

                    <div class="petugas-info">

                      <div class="petugas-avatar">

                        {{
                          String(
                            item?.nama ||
                            item?.name ||
                            item?.nama_petugas ||
                            "P"
                          )
                            .charAt(0)
                            .toUpperCase()
                        }}

                      </div>


                      <div>

                        <strong>

                          {{
                            item?.nama ||
                            item?.name ||
                            item?.nama_petugas ||
                            "-"
                          }}

                        </strong>

                        <small>
                          ID #{{ item?.id || "-" }}
                        </small>

                      </div>

                    </div>

                  </td>


                  <!-- EMAIL -->

                  <td>

                    <span class="email">
                      {{
                        item?.email ||
                        "-"
                      }}
                    </span>

                  </td>


                  <!-- NO. TELEPON -->

                  <td>

                    <span class="email">
                      {{
                        item?.nomor_hp ||
                        item?.no_telp ||
                        item?.no_hp ||
                        "-"
                      }}
                    </span>

                  </td>


                  <!-- ROLE -->

                  <td>

                    <span class="role-badge">
                      {{
                        formatRole(
                          item?.role
                        )
                      }}
                    </span>

                  </td>


                  <!-- SHIFT -->

                  <td>

                    <div class="shift-info">

                      <span
                        class="shift-badge"
                        :class="
                          `shift-${String(
                            item?.shift || ''
                          ).toLowerCase()}`
                        "
                      >

                        {{
                          formatShift(
                            item?.shift
                          )
                        }}

                      </span>

                      <small
                        v-if="
                          waktuShift(
                            item?.shift
                          )
                        "
                      >

                        {{
                          waktuShift(
                            item?.shift
                          )
                        }}

                      </small>

                    </div>

                  </td>


                  <!-- STATUS -->

                  <td>

                    <span
                      class="status-badge"
                      :class="
                        isAktif(item)
                          ? 'aktif'
                          : 'nonaktif'
                      "
                    >

                      <span></span>

                      {{
                        isAktif(item)
                          ? "Aktif"
                          : "Nonaktif"
                      }}

                    </span>

                  </td>


                  <!-- AKSI -->

                  <td>

                    <div class="actions">

                      <button
                        type="button"
                        class="action-button edit"
                        title="Edit petugas"
                        @click="
                          bukaEdit(item)
                        "
                      >
                        ✎
                      </button>


                      <button
                        type="button"
                        class="action-button delete"
                        :class="{ disabled: !bisaDihapus(item) }"
                        :disabled="!bisaDihapus(item)"
                        :title="
                          bisaDihapus(item)
                            ? 'Hapus petugas'
                            : alasanTidakBisaHapus(item)
                        "
                        @click="
                          hapusPetugas(item)
                        "
                      >
                        ×
                      </button>

                    </div>

                  </td>

                </tr>


                <!-- KOSONG -->

                <tr
                  v-if="
                    !filteredPetugas.length
                  "
                >

                  <td
                    colspan="8"
                    class="empty"
                  >

                    <div class="empty-icon">
                      ♙
                    </div>

                    <strong>
                      {{
                        search
                          ? "Petugas tidak ditemukan"
                          : "Belum ada data petugas"
                      }}
                    </strong>

                    <p>
                      {{
                        search
                          ? "Coba gunakan kata kunci lain."
                          : "Tambahkan petugas untuk mulai mengelola data."
                      }}
                    </p>

                  </td>

                </tr>

              </tbody>

            </table>

          </div>


        </section>


      </section>

    </main>


    <!-- =====================================================
         MODAL TAMBAH / EDIT
    ====================================================== -->

    <div
      v-if="showModal"
      class="modal-overlay"
      @click.self="tutupModal"
    >

      <div class="modal">


        <!-- MODAL HEADER -->

        <div class="modal-header">

          <div>

            <p>
              {{
                editingId
                  ? "EDIT DATA"
                  : "DATA BARU"
              }}
            </p>

            <h3>
              {{
                editingId
                  ? "Edit Petugas"
                  : "Tambah Petugas"
              }}
            </h3>

          </div>


          <button
            type="button"
            class="close-button"
            @click="tutupModal"
          >
            ×
          </button>

        </div>


        <!-- FORM -->

        <form
          class="form"
          @submit.prevent="
            simpanPetugas
          "
        >


          <!-- NAMA -->

          <div class="form-group">

            <label>
              Nama Petugas
              <span>*</span>
            </label>

            <input
              v-model="form.nama"
              type="text"
              placeholder="Masukkan nama petugas"
            />

          </div>


          <!-- EMAIL -->

          <div class="form-group">

            <label>
              Email
              <span>*</span>
            </label>

            <input
              v-model="form.email"
              type="email"
              placeholder="contoh@email.com"
            />

          </div>


          <!-- NOMOR TELEPON -->

          <div class="form-group">

            <label>
              Nomor Telepon
              <span>*</span>
            </label>

            <input
              v-model="form.nomor_hp"
              type="tel"
              placeholder="08xxxxxxxxxx"
            />

          </div>


          <!-- PASSWORD -->

          <div class="form-group">

            <label>
              Password

              <span
                v-if="!editingId"
              >
                *
              </span>

            </label>

            <input
              v-model="form.password"
              type="password"
              :placeholder="
                editingId
                  ? 'Kosongkan jika tidak diubah'
                  : 'Masukkan password'
              "
            />

            <small
              v-if="editingId"
              class="helper"
            >
              Kosongkan jika password
              tidak ingin diubah.
            </small>

          </div>


          <!-- ROLE -->
          <!--
            Halaman ini khusus mengelola
            petugas, jadi role dikunci
            ke "Petugas" (tidak bisa dipilih).
          -->

          <div class="form-group">

            <label>
              Role
            </label>

            <div class="role-locked">
              Petugas
            </div>

          </div>


          <!-- SHIFT -->

          <div class="form-group">

            <label>
              Shift
              <span>*</span>
            </label>

            <select
              v-model="form.shift"
            >

              <option
                value=""
                disabled
              >
                Pilih Shift
              </option>

              <option
                v-for="shift in shiftOptions"
                :key="shift.value"
                :value="shift.value"
              >

                {{ shift.label }}
                —
                {{ shift.waktu }}

              </option>

            </select>

          </div>


          <!-- STATUS -->

          <div class="form-group">

            <label>
              Status
            </label>

            <select
              v-model="form.status"
            >

              <option value="aktif">
                Aktif
              </option>

              <option value="nonaktif">
                Nonaktif
              </option>

            </select>

          </div>


          <!-- ERROR MODAL -->

          <div
            v-if="error"
            class="modal-error"
          >

            {{ error }}

          </div>


          <!-- BUTTON -->

          <div class="modal-actions">

            <button
              type="button"
              class="cancel-button"
              :disabled="saving"
              @click="tutupModal"
            >
              Batal
            </button>


            <button
              type="submit"
              class="save-button"
              :disabled="saving"
            >

              <span
                v-if="saving"
              >
                Menyimpan...
              </span>

              <span
                v-else
              >
                {{
                  editingId
                    ? "Simpan Perubahan"
                    : "Tambah Petugas"
                }}
              </span>

            </button>

          </div>

        </form>

      </div>

    </div>

  </div>

</template>


<style scoped>

/* =========================================================
   GLOBAL
========================================================= */

* {
  box-sizing: border-box;
}

.admin-page {
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
   SIDEBAR
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

  transition:
    width 0.25s ease;
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
   NAV
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

.info-icon {
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
   TOPBAR
========================================================= */

.topbar {
  height: 70px;

  background: white;

  border-bottom: 1px solid #e5e7eb;

  display: flex;

  align-items: center;

  justify-content: space-between;

  padding: 0 30px;
}

.top-left {
  display: flex;

  align-items: center;

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

.top-right {
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
  width: 7px;
  height: 7px;

  display: inline-block;

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
  max-width: 1500px;

  margin: 0 auto;

  padding: 35px 42px;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {
  display: flex;

  align-items: flex-end;

  justify-content: space-between;

  gap: 20px;

  margin-bottom: 25px;
}

.eyebrow {
  color: #94a3b8;

  font-size: 9px;

  font-weight: 900;

  letter-spacing: 1.5px;

  margin: 0 0 7px;
}

.page-header h2 {
  margin: 0;

  color: #172033;

  font-size: 29px;

  font-weight: 900;

  letter-spacing: -0.7px;
}

.page-description {
  margin: 7px 0 0;

  color: #718096;

  font-size: 11px;
}

.add-button {
  border: 0;

  background: #4f6de7;

  color: white;

  padding: 12px 17px;

  border-radius: 10px;

  display: flex;

  align-items: center;

  gap: 8px;

  cursor: pointer;

  font-size: 11px;

  font-weight: 800;

  box-shadow:
    0 7px 16px
    rgba(79, 109, 231, 0.2);

  transition: 0.2s;
}

.add-button:hover {
  transform: translateY(-1px);

  box-shadow:
    0 10px 22px
    rgba(79, 109, 231, 0.25);
}

.add-button span {
  font-size: 17px;

  line-height: 1;
}


/* =========================================================
   ALERT
========================================================= */

.alert {
  border-radius: 11px;

  padding: 11px 14px;

  display: flex;

  align-items: center;

  gap: 9px;

  margin-bottom: 18px;

  font-size: 10px;

  font-weight: 700;
}

.alert.success {
  background: #ecfdf5;

  color: #047857;

  border: 1px solid #bbf7d0;
}

.alert.error {
  background: #fff1f2;

  color: #be123c;

  border: 1px solid #fecdd3;
}

.alert span {
  font-weight: 900;
}


/* =========================================================
   STATS
========================================================= */

.stats-grid {
  display: grid;

  grid-template-columns:
    repeat(3, 1fr);

  gap: 17px;

  margin-bottom: 22px;
}

.stat-card {
  background: white;

  border: 1px solid #e5e7eb;

  border-radius: 14px;

  padding: 18px;

  display: flex;

  align-items: center;

  gap: 13px;

  box-shadow:
    0 2px 7px
    rgba(15, 23, 42, 0.025);
}

.stat-icon {
  width: 43px;
  height: 43px;

  border-radius: 11px;

  display: flex;

  align-items: center;
  justify-content: center;

  font-size: 16px;

  flex-shrink: 0;
}

.stat-icon.blue {
  background: #eef2ff;

  color: #4f46e5;
}

.stat-icon.green {
  background: #ecfdf5;

  color: #059669;
}

.stat-icon.orange {
  background: #fff7ed;

  color: #ea580c;
}

.stat-card span {
  display: block;

  color: #94a3b8;

  font-size: 9px;

  margin-bottom: 4px;
}

.stat-card strong {
  color: #172033;

  font-size: 22px;

  font-weight: 900;
}


/* =========================================================
   TABLE CARD
========================================================= */

.table-card {
  background: white;

  border: 1px solid #e5e7eb;

  border-radius: 16px;

  overflow: hidden;

  box-shadow:
    0 3px 9px
    rgba(15, 23, 42, 0.025);
}

.table-header {
  padding: 21px 22px;

  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 20px;

  border-bottom: 1px solid #eef0f3;
}

.table-label {
  margin: 0 0 5px;

  color: #94a3b8;

  font-size: 8px;

  font-weight: 900;

  letter-spacing: 1.4px;
}

.table-header h3 {
  margin: 0;

  color: #172033;

  font-size: 17px;

  font-weight: 900;
}


/* =========================================================
   SEARCH
========================================================= */

.search-box {
  width: 250px;

  height: 39px;

  border: 1px solid #e2e6eb;

  border-radius: 9px;

  display: flex;

  align-items: center;

  gap: 8px;

  padding: 0 11px;

  background: #fafbfc;
}

.search-box span {
  color: #9aa6b7;

  font-size: 17px;
}

.search-box input {
  width: 100%;

  border: 0;

  outline: 0;

  background: transparent;

  color: #172033;

  font-size: 10px;
}

.search-box input::placeholder {
  color: #aab3c0;
}


/* =========================================================
   TABLE
========================================================= */

.table-wrapper {
  width: 100%;

  overflow-x: auto;
}

table {
  width: 100%;

  min-width: 980px;

  border-collapse: collapse;
}

thead {
  background: #fafbfc;
}

th {
  padding: 12px 18px;

  color: #8793a5;

  font-size: 8px;

  font-weight: 900;

  letter-spacing: 0.7px;

  text-align: left;

  border-bottom: 1px solid #eef0f3;
}

td {
  padding: 14px 18px;

  border-bottom: 1px solid #f0f2f5;

  color: #465469;

  font-size: 10px;

  vertical-align: middle;
}

tbody tr:hover {
  background: #fbfcfe;
}

tbody tr:last-child td {
  border-bottom: 0;
}

.number {
  color: #94a3b8;

  font-size: 9px;

  font-weight: 700;
}


/* =========================================================
   PETUGAS
========================================================= */

.petugas-info {
  display: flex;

  align-items: center;

  gap: 10px;
}

.petugas-avatar {
  width: 36px;
  height: 36px;

  border-radius: 10px;

  background: #eef2ff;

  color: #4f46e5;

  display: flex;

  align-items: center;
  justify-content: center;

  font-size: 11px;

  font-weight: 900;

  flex-shrink: 0;
}

.petugas-info strong {
  display: block;

  color: #172033;

  font-size: 10px;

  font-weight: 800;
}

.petugas-info small {
  display: block;

  color: #a0aabb;

  font-size: 8px;

  margin-top: 3px;
}

.email {
  color: #66758a;

  font-size: 9px;
}


/* =========================================================
   ROLE
========================================================= */

.role-badge {
  display: inline-flex;

  align-items: center;

  padding: 5px 8px;

  border-radius: 6px;

  background: #f1f5f9;

  color: #64748b;

  font-size: 8px;

  font-weight: 800;
}

.role-locked {
  height: 42px;

  display: flex;

  align-items: center;

  padding: 0 12px;

  border: 1px solid #e2e6eb;

  border-radius: 9px;

  background: #f1f5f9;

  color: #64748b;

  font-size: 10px;

  font-weight: 800;
}


/* =========================================================
   SHIFT
========================================================= */

.shift-info {
  display: flex;

  flex-direction: column;

  align-items: flex-start;

  gap: 4px;
}

.shift-badge {
  display: inline-flex;

  padding: 5px 9px;

  border-radius: 6px;

  font-size: 8px;

  font-weight: 800;
}

.shift-info small {
  color: #9aa6b5;

  font-size: 7px;
}

.shift-pagi {
  background: #fff7ed;

  color: #c2410c;
}

.shift-siang {
  background: #eff6ff;

  color: #2563eb;
}

.shift-malam {
  background: #eef2ff;

  color: #4f46e5;
}


/* =========================================================
   STATUS
========================================================= */

.status-badge {
  display: inline-flex;

  align-items: center;

  gap: 5px;

  padding: 5px 9px;

  border-radius: 6px;

  font-size: 8px;

  font-weight: 800;
}

.status-badge span {
  width: 5px;
  height: 5px;

  border-radius: 50%;
}

.status-badge.aktif {
  background: #ecfdf5;

  color: #059669;
}

.status-badge.aktif span {
  background: #10b981;
}

.status-badge.nonaktif {
  background: #f1f5f9;

  color: #64748b;
}

.status-badge.nonaktif span {
  background: #94a3b8;
}


/* =========================================================
   ACTIONS
========================================================= */

.actions {
  display: flex;

  align-items: center;

  gap: 6px;
}

.action-button {
  width: 30px;
  height: 30px;

  border: 0;

  border-radius: 7px;

  cursor: pointer;

  display: flex;

  align-items: center;
  justify-content: center;

  font-size: 13px;

  transition: 0.2s;
}

.action-button.edit {
  background: #eef2ff;

  color: #4f46e5;
}

.action-button.edit:hover {
  background: #e0e7ff;
}

.action-button.delete {
  background: #fff1f2;

  color: #e11d48;
}

.action-button.delete:hover {
  background: #ffe4e6;
}

.action-button.delete.disabled,
.action-button.delete:disabled {
  background: #f1f5f9;

  color: #cbd5e1;

  cursor: not-allowed;
}

.action-button.delete.disabled:hover,
.action-button.delete:disabled:hover {
  background: #f1f5f9;
}


/* =========================================================
   LOADING
========================================================= */

.loading {
  min-height: 280px;

  display: flex;

  flex-direction: column;

  align-items: center;
  justify-content: center;

  color: #94a3b8;
}

.loading-spinner {
  width: 30px;
  height: 30px;

  border: 3px solid #e5e7eb;

  border-top-color: #4f6de7;

  border-radius: 50%;

  animation:
    spin 0.8s linear infinite;

  margin-bottom: 10px;
}

.loading p {
  margin: 0;

  font-size: 10px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}


/* =========================================================
   EMPTY
========================================================= */

.empty {
  text-align: center;

  padding: 55px 20px !important;
}

.empty-icon {
  width: 48px;
  height: 48px;

  border-radius: 13px;

  background: #eef2ff;

  color: #4f46e5;

  display: flex;

  align-items: center;
  justify-content: center;

  margin: 0 auto 11px;

  font-size: 19px;
}

.empty strong {
  display: block;

  color: #334155;

  font-size: 11px;
}

.empty p {
  color: #94a3b8;

  font-size: 9px;

  margin: 5px 0 0;
}


/* =========================================================
   MODAL
========================================================= */

.modal-overlay {
  position: fixed;

  inset: 0;

  z-index: 100;

  background:
    rgba(15, 23, 42, 0.45);

  display: flex;

  align-items: center;
  justify-content: center;

  padding: 20px;

  backdrop-filter: blur(3px);
}

.modal {
  width: 100%;

  max-width: 510px;

  max-height: 90vh;

  overflow-y: auto;

  background: white;

  border-radius: 17px;

  box-shadow:
    0 25px 70px
    rgba(15, 23, 42, 0.2);
}

.modal-header {
  padding: 21px 23px;

  border-bottom: 1px solid #eef0f3;

  display: flex;

  justify-content: space-between;

  align-items: flex-start;
}

.modal-header p {
  margin: 0 0 5px;

  color: #94a3b8;

  font-size: 8px;

  font-weight: 900;

  letter-spacing: 1.2px;
}

.modal-header h3 {
  margin: 0;

  color: #172033;

  font-size: 19px;

  font-weight: 900;
}

.close-button {
  width: 32px;
  height: 32px;

  border: 0;

  background: #f8fafc;

  color: #64748b;

  border-radius: 8px;

  cursor: pointer;

  font-size: 20px;

  line-height: 1;
}

.close-button:hover {
  background: #f1f5f9;
}


/* =========================================================
   FORM
========================================================= */

.form {
  padding: 22px 23px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;

  color: #334155;

  font-size: 10px;

  font-weight: 800;

  margin-bottom: 7px;
}

.form-group label span {
  color: #ef4444;
}

.form-group input,
.form-group select {
  width: 100%;

  height: 42px;

  border: 1px solid #dfe4ea;

  background: #fafbfc;

  border-radius: 9px;

  outline: none;

  padding: 0 12px;

  color: #172033;

  font-size: 10px;

  transition: 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  border-color: #7185e9;

  background: white;

  box-shadow:
    0 0 0 3px
    rgba(79, 109, 231, 0.08);
}

.helper {
  display: block;

  color: #94a3b8;

  font-size: 8px;

  margin-top: 5px;
}

.modal-error {
  background: #fff1f2;

  border: 1px solid #fecdd3;

  color: #be123c;

  border-radius: 8px;

  padding: 9px 11px;

  font-size: 9px;

  margin-bottom: 15px;
}


/* =========================================================
   MODAL ACTION
========================================================= */

.modal-actions {
  display: flex;

  justify-content: flex-end;

  gap: 9px;

  padding-top: 5px;
}

.cancel-button,
.save-button {
  height: 39px;

  padding: 0 16px;

  border-radius: 8px;

  border: 0;

  cursor: pointer;

  font-size: 10px;

  font-weight: 800;
}

.cancel-button {
  background: #f1f5f9;

  color: #64748b;
}

.cancel-button:hover {
  background: #e2e8f0;
}

.save-button {
  background: #4f6de7;

  color: white;

  min-width: 125px;
}

.save-button:hover {
  background: #405ed8;
}

.save-button:disabled,
.cancel-button:disabled {
  opacity: 0.6;

  cursor: not-allowed;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1000px) {

  .stats-grid {
    grid-template-columns:
      1fr;
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

  .page-header {
    align-items: flex-start;

    flex-direction: column;
  }

  .table-header {
    align-items: flex-start;

    flex-direction: column;
  }

  .search-box {
    width: 100%;
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

  .page-header h2 {
    font-size: 24px;
  }

  .add-button {
    width: 100%;

    justify-content: center;
  }

  .top-right .system-online {
    display: none;
  }

}

</style>
```vue
<script setup lang="ts">
import {
  ref,
  computed,
  onMounted,
  onBeforeUnmount,
} from "vue";
import axios from "axios";

const api = axios.create({
  baseURL: "http://127.0.0.1:8000/api",
  headers: {
    Accept: "application/json",
  },
});

// ======================================================
// STATE
// ======================================================

const activeTab = ref<
  "nonmember" | "member"
>("nonmember");

const transaksi = ref<any[]>([]);
const members = ref<any[]>([]);

const search = ref("");

const loading = ref(false);
const errorMessage = ref("");

const showPaymentModal = ref(false);
const selectedData = ref<any>(null);

const paymentAmount =
  ref<number | null>(null);

const paymentLoading = ref(false);
const paymentError = ref("");

const user = ref({
  name: "Petugas",
  role: "Petugas",
});

// ======================================================
// AUTO REFRESH
// ======================================================

let refreshTimer:
  ReturnType<typeof setInterval> | null =
  null;

const AUTO_REFRESH_TIME = 3000;

// ======================================================
// TOKEN
// ======================================================

const getToken = () => {
  if (process.client) {
    return (
      localStorage.getItem("token") ||
      ""
    );
  }

  return "";
};

const authConfig = () => {
  const token = getToken();

  if (!token) {
    return {};
  }

  return {
    headers: {
      Authorization:
        `Bearer ${token}`,
    },
  };
};

// ======================================================
// FORMAT
// ======================================================

const formatRupiah = (
  value: any
) => {
  const number =
    Number(value ?? 0);

  if (!number) {
    return "Rp 0";
  }

  return new Intl.NumberFormat(
    "id-ID",
    {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0,
    }
  ).format(number);
};

const formatTanggal = (
  value: any
) => {
  if (!value) {
    return "-";
  }

  const date =
    new Date(value);

  if (
    isNaN(
      date.getTime()
    )
  ) {
    return "-";
  }

  return date.toLocaleDateString(
    "id-ID",
    {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    }
  );
};

const formatJam = (
  value: any
) => {
  if (!value) {
    return "-";
  }

  const date =
    new Date(value);

  if (
    isNaN(
      date.getTime()
    )
  ) {
    return "-";
  }

  return date.toLocaleTimeString(
    "id-ID",
    {
      hour: "2-digit",
      minute: "2-digit",
    }
  );
};

// ======================================================
// NORMALISASI DATA TIKET
// ======================================================

const normalizeTiket = (
  item: any
) => {
  /*
   * STATUS
   *
   * Support:
   * - keluar
   * - Keluar
   * - KELUAR
   * - masuk
   */

  const status =
    String(
      item?.status ??
        "masuk"
    )
      .toLowerCase()
      .trim();

  /*
   * PLAT NOMOR
   *
   * Support beberapa nama field:
   * nomor_polisi
   * plat_nomor
   * nomor_plat
   */

  const nomorPolisi =
    item?.nomor_polisi ??
    item?.plat_nomor ??
    item?.nomor_plat ??
    "-";

  const platNomor =
    item?.plat_nomor ??
    item?.nomor_polisi ??
    item?.nomor_plat ??
    "-";

  /*
   * JENIS KENDARAAN
   *
   * INI BAGIAN PENTING.
   *
   * Controller lama lu menyimpan:
   * jenis
   *
   * Controller baru menyimpan:
   * jenis_kendaraan
   *
   * Jadi kita dukung semuanya.
   */

  const jenisKendaraan =
    item?.jenis_kendaraan ??
    item?.jenis ??
    item?.kategori_kendaraan ??
    "-";

  const waktuMasuk =
    item?.waktu_masuk ??
    null;

  const waktuKeluar =
    item?.waktu_keluar ??
    null;

  const tarifPerJam =
    Number(
      item?.tarif_per_jam ??
        0
    );

  const totalBayar =
    Number(
      item?.total_bayar ??
        0
    );

  const uangDibayar =
    Number(
      item?.uang_dibayar ??
        0
    );

  const kembalian =
    Number(
      item?.kembalian ??
        0
    );

  return {
    ...item,

    id:
      item?.id,

    kode_tiket:
      item?.kode_tiket ??
      "-",

    qr_code:
      item?.qr_code ??
      null,

    nomor_polisi:
      nomorPolisi,

    plat_nomor:
      platNomor,

    /*
     * Simpan kedua bentuk.
     *
     * Jadi template bisa membaca
     * jenis_kendaraan tanpa peduli
     * database mengirim "jenis".
     */
    jenis_kendaraan:
      jenisKendaraan,

    jenis:
      item?.jenis ??
      jenisKendaraan,

    tarif_per_jam:
      tarifPerJam,

    total_bayar:
      totalBayar,

    uang_dibayar:
      uangDibayar,

    kembalian:
      kembalian,

    waktu_masuk:
      waktuMasuk,

    waktu_keluar:
      waktuKeluar,

    status,

    sumber:
      "tiket",
  };
};

// ======================================================
// AMBIL MEMBER
// ======================================================

const ambilMember =
  async () => {
    try {
      const response =
        await api.get(
          "/member",
          authConfig()
        );

      const result =
        response.data;

      if (
        Array.isArray(
          result
        )
      ) {
        members.value =
          result;
      } else if (
        Array.isArray(
          result?.data
        )
      ) {
        members.value =
          result.data;
      } else {
        members.value =
          [];
      }
    } catch (
      error
    ) {
      console.error(
        "Gagal mengambil member:",
        error
      );

      members.value =
        [];
    }
  };

// ======================================================
// AMBIL SEMUA TIKET
// ======================================================

const ambilTransaksi =
  async (
    silent = false
  ) => {
    if (!silent) {
      errorMessage.value =
        "";
    }

    try {
      const auth =
        authConfig();

      const response =
        await api.get(
          "/tiket",
          {
            ...auth,

            headers: {
              ...(auth as any)
                ?.headers,

              "Cache-Control":
                "no-cache",

              Pragma:
                "no-cache",
            },

            params: {
              _refresh:
                Date.now(),
            },
          }
        );

      const result =
        response.data;

      let daftarTiket:
        any[] = [];

      /*
       * Support response:
       *
       * [
       *   {...}
       * ]
       *
       * atau:
       *
       * {
       *   success: true,
       *   data: [...]
       * }
       */

      if (
        Array.isArray(
          result
        )
      ) {
        daftarTiket =
          result;
      } else if (
        Array.isArray(
          result?.data
        )
      ) {
        daftarTiket =
          result.data;
      }

      transaksi.value =
        daftarTiket.map(
          normalizeTiket
        );

    } catch (
      error: any
    ) {
      console.error(
        "Gagal mengambil data tiket:",
        error
      );

      if (!silent) {
        transaksi.value =
          [];

        errorMessage.value =
          error?.response
            ?.data
            ?.message ||
          "Data transaksi non-member belum dapat diambil dari Laravel.";
      }
    }
  };

// ======================================================
// LOAD
// ======================================================

const loadData =
  async () => {
    loading.value =
      true;

    errorMessage.value =
      "";

    try {
      await Promise.all([
        ambilTransaksi(),
        ambilMember(),
      ]);
    } finally {
      loading.value =
        false;
    }
  };

// ======================================================
// AUTO REFRESH
// ======================================================

const mulaiAutoRefresh =
  () => {
    if (
      !process.client
    ) {
      return;
    }

    if (refreshTimer) {
      clearInterval(
        refreshTimer
      );
    }

    refreshTimer =
      setInterval(
        async () => {
          if (
            paymentLoading.value ||
            loading.value
          ) {
            return;
          }

          /*
           * Baca ulang dari DATABASE.
           *
           * Jadi setelah Scan Keluar
           * berhasil, perubahan A001
           * akan masuk ke halaman ini.
           */
          await ambilTransaksi(
            true
          );

          await ambilMember();
        },
        AUTO_REFRESH_TIME
      );
  };

const berhentiAutoRefresh =
  () => {
    if (refreshTimer) {
      clearInterval(
        refreshTimer
      );

      refreshTimer =
        null;
    }
  };

// ======================================================
// SEARCH NON MEMBER
// ======================================================

const nonMemberFiltered =
  computed(() => {
    const keyword =
      search.value
        .toLowerCase()
        .trim();

    if (!keyword) {
      return transaksi.value;
    }

    return transaksi.value.filter(
      (item: any) => {
        const kode =
          String(
            item?.kode_tiket ||
              ""
          )
            .toLowerCase();

        const plat =
          String(
            item?.plat_nomor ||
              item?.nomor_polisi ||
              ""
          )
            .toLowerCase();

        const kendaraan =
          String(
            item?.jenis_kendaraan ||
              item?.jenis ||
              ""
          )
            .toLowerCase();

        const status =
          String(
            item?.status ||
              ""
          )
            .toLowerCase();

        return (
          kode.includes(
            keyword
          ) ||
          plat.includes(
            keyword
          ) ||
          kendaraan.includes(
            keyword
          ) ||
          status.includes(
            keyword
          )
        );
      }
    );
  });

// ======================================================
// SEARCH MEMBER
// ======================================================

const memberFiltered =
  computed(() => {
    const keyword =
      search.value
        .toLowerCase()
        .trim();

    if (!keyword) {
      return members.value;
    }

    return members.value.filter(
      (item: any) => {
        const kode =
          String(
            item?.kode_member ||
              ""
          )
            .toLowerCase();

        const nama =
          String(
            item?.nama_member ||
              ""
          )
            .toLowerCase();

        const perusahaan =
          String(
            item?.nama_perusahaan ||
              ""
          )
            .toLowerCase();

        return (
          kode.includes(
            keyword
          ) ||
          nama.includes(
            keyword
          ) ||
          perusahaan.includes(
            keyword
          )
        );
      }
    );
  });

// ======================================================
// CEK EXPIRED (sama dengan halaman Data Member)
// ======================================================

const sudahExpired = (member: any) => {
  if (!member?.tanggal_expired) {
    return false;
  }

  const tanggalExpired = String(
    member.tanggal_expired
  ).substring(0, 10);

  const sekarang = new Date();

  const tahun = sekarang.getFullYear();

  const bulan = String(
    sekarang.getMonth() + 1
  ).padStart(2, "0");

  const hari = String(
    sekarang.getDate()
  ).padStart(2, "0");

  const hariIni = `${tahun}-${bulan}-${hari}`;

  return tanggalExpired < hariIni;
};

// ======================================================
// STATUS MEMBER
// ======================================================

const isMemberLunas = (member: any) => {
  if (!member) {
    return false;
  }

  // Bulan tagihan sudah lewat -> wajib bayar lagi.
  if (sudahExpired(member)) {
    return false;
  }

  const status = String(member?.status || "")
    .toLowerCase()
    .trim();

  const tagihan = Number(member?.total_harga ?? 0);
  const dibayar = Number(member?.jumlah_bayar ?? 0);

  return (
    status === "lunas" ||
    (tagihan > 0 && dibayar >= tagihan)
  );
};

// ======================================================
// STATUS SUDAH KELUAR
// ======================================================

const isTransaksiLunas =
  (
    item: any
  ) => {
    if (!item) {
      return false;
    }

    const status =
      String(
        item?.status ??
          ""
      )
        .toLowerCase()
        .trim();

    const waktuKeluar =
      item?.waktu_keluar ??
      null;

    /*
     * Kalau backend sudah mengubah
     * status menjadi keluar,
     * langsung dianggap keluar.
     *
     * Kalau backend mengisi waktu_keluar,
     * juga dianggap keluar.
     */

    if (
      status ===
        "keluar" ||
      status ===
        "sudah keluar"
    ) {
      return true;
    }

    if (
      waktuKeluar
    ) {
      return true;
    }

    return false;
  };

// ======================================================
// STATUS MASIH PARKIR
// ======================================================

const isTransaksiSedangParkir =
  (
    item: any
  ) => {
    if (!item) {
      return false;
    }

    /*
     * Kalau sudah keluar,
     * jangan pernah dianggap parkir.
     */
    if (
      isTransaksiLunas(
        item
      )
    ) {
      return false;
    }

    const status =
      String(
        item?.status ??
          ""
      )
        .toLowerCase()
        .trim();

    return (
      (
        status ===
          "masuk" ||
        status ===
          "parkir" ||
        status ===
          "masih parkir"
      ) &&
      !item?.waktu_keluar
    );
  };

// ======================================================
// BIAYA NON-MEMBER
// ======================================================

const getBiayaTransaksi =
  (
    item: any
  ) => {
    if (!item) {
      return 0;
    }

    const totalBayar =
      Number(
        item?.total_bayar ??
          0
      );

    if (
      totalBayar >
      0
    ) {
      return totalBayar;
    }

    /*
     * Fallback hitung tarif
     * kalau database belum punya
     * total_bayar.
     */

    if (
      isTransaksiLunas(
        item
      ) &&
      item?.waktu_masuk &&
      item?.waktu_keluar
    ) {
      const waktuMasuk =
        new Date(
          item.waktu_masuk
        );

      const waktuKeluar =
        new Date(
          item.waktu_keluar
        );

      const selisih =
        waktuKeluar.getTime() -
        waktuMasuk.getTime();

      const jam =
        Math.max(
          1,
          Math.ceil(
            selisih /
              (
                1000 *
                60 *
                60
              )
          )
        );

      const tarif =
        Number(
          item?.tarif_per_jam ??
            0
        );

      return (
        jam * tarif
      );
    }

    return 0;
  };

// ======================================================
// STATISTIK
// ======================================================

const totalTransaksi =
  computed(() => {
    return (
      transaksi.value.length +
      members.value.length
    );
  });

const totalBelumBayar =
  computed(() => {
    const nonMemberBelum =
      transaksi.value.filter(
        (item: any) =>
          !isTransaksiLunas(
            item
          )
      ).length;

    const memberBelum =
      members.value.filter(
        (item: any) =>
          !isMemberLunas(
            item
          )
      ).length;

    return (
      nonMemberBelum +
      memberBelum
    );
  });

const totalSudahBayar =
  computed(() => {
    const nonMemberLunas =
      transaksi.value.filter(
        (item: any) =>
          isTransaksiLunas(
            item
          )
      ).length;

    const memberLunas =
      members.value.filter(
        (item: any) =>
          isMemberLunas(
            item
          )
      ).length;

    return (
      nonMemberLunas +
      memberLunas
    );
  });

const totalPendapatan =
  computed(() => {
    const pendapatanNonMember =
      transaksi.value.reduce(
        (
          total: number,
          item: any
        ) => {
          if (
            !isTransaksiLunas(
              item
            )
          ) {
            return total;
          }

          return (
            total +
            Number(
              item?.total_bayar ??
                0
            )
          );
        },
        0
      );

           const pendapatanMember =
      members.value.reduce(
        (total: number, item: any) => {
          if (!isMemberLunas(item)) {
            return total;
          }

          return (
            total +
            Number(item?.jumlah_bayar ?? 0)
          );
        },
        0
      );

    return (
      pendapatanNonMember +
      pendapatanMember
    );
  });

// ======================================================
// MODAL
// ======================================================

const bukaPembayaran =
  (
    item: any,
    type:
      | "nonmember"
      | "member"
  ) => {
    if (
      type ===
        "nonmember" &&
      isTransaksiSedangParkir(
        item
      )
    ) {
      return;
    }

    selectedData.value =
      {
        ...item,
        type,
      };

    paymentAmount.value =
      null;

    paymentError.value =
      "";

    showPaymentModal.value =
      true;
  };

const tutupPembayaran =
  () => {
    showPaymentModal.value =
      false;

    selectedData.value =
      null;

    paymentAmount.value =
      null;

    paymentError.value =
      "";

    paymentLoading.value =
      false;
  };

// ======================================================
// TOTAL TERPILIH
// ======================================================

const selectedTotal =
  computed(() => {
    if (
      !selectedData.value
    ) {
      return 0;
    }

    if (
      selectedData.value
        ?.type ===
      "member"
    ) {
      return Number(
        selectedData.value
          ?.total_harga ??
          0
      );
    }

    return Number(
      selectedData.value
        ?.total_bayar ??
        0
    );
  });

// ======================================================
// KEMBALIAN
// ======================================================

const kembalian =
  computed(() => {
    const bayar =
      Number(
        paymentAmount.value ??
          0
      );

    const total =
      selectedTotal.value;

    if (
      bayar <= total
    ) {
      return 0;
    }

    return (
      bayar - total
    );
  });

// ======================================================
// KEKURANGAN
// ======================================================

const kekurangan =
  computed(() => {
    const bayar =
      Number(
        paymentAmount.value ??
          0
      );

    const total =
      selectedTotal.value;

    if (
      bayar >= total
    ) {
      return 0;
    }

    return (
      total - bayar
    );
  });

// ======================================================
// PROSES PEMBAYARAN
// ======================================================

const prosesPembayaran =
  async () => {
    if (
      !selectedData.value
    ) {
      return;
    }

    const bayar =
      Number(
        paymentAmount.value ??
          0
      );

    const total =
      selectedTotal.value;

    if (
      bayar <= 0
    ) {
      paymentError.value =
        "Masukkan jumlah pembayaran terlebih dahulu.";

      return;
    }

    if (
      total <= 0
    ) {
      paymentError.value =
        "Total pembayaran belum tersedia di database.";

      return;
    }

    if (
      bayar < total
    ) {
      paymentError.value =
        "Jumlah pembayaran masih kurang.";

      return;
    }

    paymentLoading.value =
      true;

    paymentError.value =
      "";

    try {
      // ==================================================
      // MEMBER
      // ==================================================

      if (
        selectedData.value
          ?.type ===
        "member"
      ) {
        await api.put(
          `/member/${selectedData.value.id}/pembayaran`,
          {
            jumlah_bayar:
              bayar,
          },
          authConfig()
        );

        await ambilMember();

        tutupPembayaran();

        activeTab.value =
          "member";

        return;
      }

      // ==================================================
      // CEK DATA TERBARU SEBELUM BAYAR
      // ==================================================

      await ambilTransaksi(
        true
      );

      const tiketTerbaru =
        transaksi.value.find(
          (item: any) =>
            String(
              item?.id
            ) ===
            String(
              selectedData
                .value?.id
            )
        );

      if (
        tiketTerbaru &&
        isTransaksiLunas(
          tiketTerbaru
        )
      ) {
        paymentError.value =
          "Tiket ini sudah diproses dan kendaraan sudah keluar.";

        return;
      }

      // ==================================================
      // DATA KENDARAAN
      // ==================================================

      const jenisKendaraan =
        selectedData.value
          ?.jenis_kendaraan ??
        selectedData.value
          ?.jenis ??
        "-";

      const nomorPolisi =
        selectedData.value
          ?.nomor_polisi ??
        selectedData.value
          ?.plat_nomor ??
        selectedData.value
          ?.nomor_plat ??
        "-";

      // ==================================================
      // PUT KELUAR
      // ==================================================

      const response =
        await api.put(
          `/tiket/${selectedData.value.id}/keluar`,
          {
            jenis_kendaraan:
              jenisKendaraan,

            jenis:
              jenisKendaraan,

            nomor_polisi:
              nomorPolisi,

            nomor_plat:
              nomorPolisi,

            uang_dibayar:
              bayar,

            total_bayar:
              total,

            kembalian:
              Math.max(
                bayar -
                  total,
                0
              ),
          },
          authConfig()
        );

      console.log(
        "RESPON KELUAR:",
        response.data
      );

      // ==================================================
      // AMBIL ULANG DATA DARI DATABASE
      // ==================================================

      await ambilTransaksi(
        true
      );

      /*
       * Cari lagi tiket yang barusan dibayar.
       */
      const tiketSetelahBayar =
        transaksi.value.find(
          (item: any) =>
            String(
              item?.id
            ) ===
            String(
              selectedData
                .value?.id
            )
        );

      /*
       * Kalau backend belum mengubah
       * status keluar, jangan pura-pura sukses.
       */
      if (
        tiketSetelahBayar &&
        !isTransaksiLunas(
          tiketSetelahBayar
        )
      ) {
        paymentError.value =
          "Pembayaran berhasil dikirim, tetapi status tiket di database belum berubah menjadi keluar.";

        return;
      }

      // ==================================================
      // TUTUP MODAL
      // ==================================================

      tutupPembayaran();

      activeTab.value =
        "nonmember";

    } catch (
      error: any
    ) {
      console.error(
        "Pembayaran gagal:",
        error
      );

      paymentError.value =
        error?.response
          ?.data
          ?.message ||
        error?.response
          ?.data
          ?.error ||
        "Pembayaran gagal diproses.";
    } finally {
      paymentLoading.value =
        false;
    }
  };

// ======================================================
// REFRESH MANUAL
// ======================================================

const refreshData =
  async () => {
    await loadData();
  };

// ======================================================
// KEMBALI
// ======================================================

const kembali = () => {
  navigateTo(
    "/petugas"
  );
};

// ======================================================
// MOUNTED
// ======================================================

onMounted(
  async () => {
    try {
      const savedUser =
        localStorage.getItem(
          "user"
        );

      if (savedUser) {
        const parsedUser =
          JSON.parse(
            savedUser
          );

        user.value = {
          name:
            parsedUser.name ||
            parsedUser.nama ||
            "Petugas",

          role:
            parsedUser.role ||
            "Petugas",
        };
      }
    } catch {
      console.warn(
        "User localStorage tidak valid."
      );
    }

    await loadData();

    mulaiAutoRefresh();
  }
);

// ======================================================
// BEFORE UNMOUNT
// ======================================================

onBeforeUnmount(
  () => {
    berhentiAutoRefresh();
  }
);
</script>

<template>
  <div class="page">

    <!-- ==================================================
         TOPBAR
    ================================================== -->

    <header class="topbar">

      <div class="top-left">

        <button
          class="back-button"
          @click="kembali"
        >
          <span class="back-icon">
            ‹
          </span>

          <span>
            Kembali
          </span>
        </button>

        <div
          class="top-divider"
        ></div>

        <div class="title-area">

          <div
            class="title-badge"
          >
            TRANSAKSI
          </div>

          <h1>
            Kelola Transaksi
          </h1>

          <p>
            Kelola pembayaran kendaraan dan tagihan member
          </p>

        </div>

      </div>

      <div class="profile">

        <div class="profile-text">
          <strong>
            {{ user.role }}
          </strong>
        </div>

        <div
          class="profile-avatar"
        >
          {{
            user.name
              .charAt(0)
              .toUpperCase()
          }}
        </div>

      </div>

    </header>

    <!-- ==================================================
         CONTENT
    ================================================== -->

    <main class="content">

      <div class="heading-row">

        <div>

          <div
            class="heading-label"
          >
            ✦ SISTEM PARKIR
          </div>

          <h2>
            Kelola Pembayaran
          </h2>

          <p>
            Pantau transaksi parkir dan proses pembayaran
            sebelum kendaraan keluar.
          </p>

        </div>

        <button
          class="refresh-button"
          @click="refreshData"
          :disabled="loading"
        >

          <span
            class="refresh-icon"
          >
            ↻
          </span>

          {{
            loading
              ? "Memuat..."
              : "Refresh Data"
          }}

        </button>

      </div>

      <!-- ==================================================
           STATISTIK
      ================================================== -->

      <section
        class="stats-grid"
      >

        <div
          class="stat-card stat-pink"
        >

          <div
            class="stat-content"
          >

            <span
              class="stat-label"
            >
              Total Transaksi
            </span>

            <strong>
              {{ totalTransaksi }}
            </strong>

            <small>
              kendaraan + member
            </small>

          </div>

          <div
            class="stat-icon pink-icon"
          >
            ↔
          </div>

        </div>

        <div
          class="stat-card stat-yellow"
        >

          <div
            class="stat-content"
          >

            <span
              class="stat-label"
            >
              Belum Bayar
            </span>

            <strong>
              {{ totalBelumBayar }}
            </strong>

            <small>
              menunggu pembayaran
            </small>

          </div>

          <div
            class="stat-icon yellow-icon"
          >
            ◷
          </div>

        </div>

        <div
          class="stat-card stat-tosca"
        >

          <div
            class="stat-content"
          >

            <span
              class="stat-label"
            >
              Sudah Bayar
            </span>

            <strong>
              {{ totalSudahBayar }}
            </strong>

            <small>
              transaksi selesai
            </small>

          </div>

          <div
            class="stat-icon tosca-icon"
          >
            ✓
          </div>

        </div>

        <div
          class="stat-card stat-cream"
        >

          <div
            class="stat-content"
          >

            <span
              class="stat-label"
            >
              Pendapatan
            </span>

            <strong
              class="income"
            >
              {{
                formatRupiah(
                  totalPendapatan
                )
              }}
            </strong>

            <small>
              dari pembayaran lunas
            </small>

          </div>

          <div
            class="stat-icon income-icon"
          >
            Rp
          </div>

        </div>

      </section>

      <!-- ==================================================
           TABLE
      ================================================== -->

      <section
        class="table-card"
      >

        <div
          class="toolbar"
        >

          <div
            class="tabs"
          >

            <button
              :class="[
                'tab-button',
                {
                  active:
                    activeTab ===
                    'nonmember',
                },
              ]"
              @click="
                activeTab =
                  'nonmember';
                search = '';
              "
            >

              <span
                class="dot pink-dot"
              ></span>

              <span>
                Non-Member
              </span>

              <span
                class="count pink-count"
              >
                {{
                  transaksi.length
                }}
              </span>

            </button>

            <button
              :class="[
                'tab-button',
                {
                  active:
                    activeTab ===
                    'member',
                },
              ]"
              @click="
                activeTab =
                  'member';
                search = '';
              "
            >

              <span
                class="dot tosca-dot"
              ></span>

              <span>
                Member
              </span>

              <span
                class="count tosca-count"
              >
                {{
                  members.length
                }}
              </span>

            </button>

          </div>

          <div
            class="search-box"
          >

            <span
              class="search-icon"
            >
              ⌕
            </span>

            <input
              v-model="search"
              type="text"
              :placeholder="
                activeTab ===
                'nonmember'
                  ? 'Cari kode tiket, plat nomor...'
                  : 'Cari kode, nama member...'
              "
            />

            <button
              v-if="search"
              class="clear-search"
              @click="
                search = ''
              "
            >
              ×
            </button>

          </div>

        </div>

        <!-- ERROR -->

        <div
          v-if="errorMessage"
          class="error-box"
        >

          <span
            class="error-icon"
          >
            !
          </span>

          {{
            errorMessage
          }}

        </div>

        <!-- ==================================================
             NON MEMBER
        ================================================== -->

        <div
          v-if="
            activeTab ===
            'nonmember'
          "
          class="table-wrapper"
        >

          <table>

            <thead>

              <tr>
                <th>NO</th>
                <th>KODE TIKET</th>
                <th>PLAT NOMOR</th>
                <th>KENDARAAN</th>
                <th>MASUK</th>
                <th>KELUAR</th>
                <th>TOTAL</th>
                <th>STATUS</th>
                <th>AKSI</th>
              </tr>

            </thead>

            <tbody>

              <tr
                v-for="(
                  item,
                  index
                ) in nonMemberFiltered"
                :key="item.id"
              >

                <td
                  class="number-cell"
                >
                  {{
                    index + 1
                  }}
                </td>

                <td>

                  <span
                    class="ticket-code"
                  >
                    {{
                      item.kode_tiket
                    }}
                  </span>

                </td>

                <td>

                  <strong
                    class="plate"
                  >
                    {{
                      item.plat_nomor ||
                      item.nomor_polisi ||
                      item.nomor_plat ||
                      "-"
                    }}
                  </strong>

                </td>

                <td>

                  <span
                    class="vehicle-text"
                  >
                    {{
                      item.jenis_kendaraan ||
                      item.jenis ||
                      "-"
                    }}
                  </span>

                </td>

                <td>

                  <div
                    class="datetime"
                  >

                    <strong>
                      {{
                        formatJam(
                          item.waktu_masuk
                        )
                      }}
                    </strong>

                    <span>
                      {{
                        formatTanggal(
                          item.waktu_masuk
                        )
                      }}
                    </span>

                  </div>

                </td>

                <td>

                  <div
                    class="datetime"
                  >

                    <strong>
                      {{
                        item.waktu_keluar
                          ? formatJam(
                              item.waktu_keluar
                            )
                          : "-"
                      }}
                    </strong>

                    <span>
                      {{
                        item.waktu_keluar
                          ? formatTanggal(
                              item.waktu_keluar
                            )
                          : "Masih parkir"
                      }}
                    </span>

                  </div>

                </td>

                <td>

                  <strong
                    class="money"
                  >
                    {{
                      getBiayaTransaksi(
                        item
                      ) > 0
                        ? formatRupiah(
                            getBiayaTransaksi(
                              item
                            )
                          )
                        : "-"
                    }}
                  </strong>

                </td>

                <td>

                  <!-- SUDAH KELUAR -->

                  <span
                    v-if="
                      isTransaksiLunas(
                        item
                      )
                    "
                    class="status success"
                  >

                    <span
                      class="status-dot"
                    ></span>

                    Sudah Keluar

                  </span>

                  <!-- MASIH PARKIR -->

                  <span
                    v-else
                    class="status waiting"
                  >

                    <span
                      class="status-dot"
                    ></span>

                    Masih Parkir

                  </span>

                </td>

                <td>

                  <span
                    v-if="
                      isTransaksiSedangParkir(
                        item
                      )
                    "
                    class="waiting-text"
                  >
                    Scan Keluar
                  </span>

                  <span
                    v-else-if="
                      isTransaksiLunas(
                        item
                      )
                    "
                    class="paid-text"
                  >
                    ✓ Selesai
                  </span>

                  <span
                    v-else
                    class="waiting-text"
                  >
                    Menunggu
                  </span>

                </td>

              </tr>

              <!-- EMPTY -->

              <tr
                v-if="
                  !loading &&
                  nonMemberFiltered.length ===
                    0
                "
              >

                <td
                  colspan="9"
                  class="empty-cell"
                >

                  <div
                    class="empty-state"
                  >

                    <div
                      class="empty-icon pink-empty"
                    >
                      ♡
                    </div>

                    <strong>
                      Belum ada transaksi non-member
                    </strong>

                    <span>
                      Data tiket kendaraan akan muncul
                      di halaman ini.
                    </span>

                  </div>

                </td>

              </tr>

              <!-- LOADING -->

              <tr
                v-if="loading"
              >

                <td
                  colspan="9"
                  class="empty-cell"
                >

                  <div
                    class="empty-state"
                  >

                    <div
                      class="loader"
                    ></div>

                    <strong>
                      Memuat data...
                    </strong>

                  </div>

                </td>

              </tr>

            </tbody>

          </table>

        </div>

        <!-- ==================================================
             MEMBER
        ================================================== -->

        <div
          v-else
          class="table-wrapper"
        >

          <table>

            <thead>

              <tr>
                <th>NO</th>
                <th>KODE MEMBER</th>
                <th>NAMA MEMBER</th>
                <th>PERUSAHAAN</th>
                <th>TAGIHAN</th>
                <th>DIBAYAR</th>
                <th>BERLAKU</th>
                <th>STATUS</th>
                <th>AKSI</th>
              </tr>

            </thead>

            <tbody>

              <tr
                v-for="(
                  item,
                  index
                ) in memberFiltered"
                :key="item.id"
              >

                <td
                  class="number-cell"
                >
                  {{
                    index + 1
                  }}
                </td>

                <td>

                  <span
                    class="member-code"
                  >
                    {{
                      item.kode_member ||
                      "-"
                    }}
                  </span>

                </td>

                <td>

                  <strong
                    class="member-name"
                  >
                    {{
                      item.nama_member ||
                      "-"
                    }}
                  </strong>

                </td>

                <td>

                  <span
                    class="company-text"
                  >
                    {{
                      item.nama_perusahaan ||
                      "-"
                    }}
                  </span>

                </td>

                <td>

                  <strong
                    class="money"
                  >
                    {{
                      formatRupiah(
                        item.total_harga
                      )
                    }}
                  </strong>

                </td>

                <td>

                  <strong
                    :class="
                      isMemberLunas(
                        item
                      )
                        ? 'money paid-money'
                        : 'money'
                    "
                  >
                    {{
                      formatRupiah(
                        item.jumlah_bayar
                      )
                    }}
                  </strong>

                </td>

                <td>

                  <div
                    class="datetime"
                  >

                    <strong>
                      {{
                        formatTanggal(
                          item.tanggal_expired
                        )
                      }}
                    </strong>

                    <span>
                      {{
                        item.bulan_tagihan ||
                        "-"
                      }}
                    </span>

                  </div>

                </td>

                <td>

                  <span
                    v-if="
                      isMemberLunas(
                        item
                      )
                    "
                    class="status success"
                  >

                    <span
                      class="status-dot"
                    ></span>

                    Lunas

                  </span>

                  <span
                    v-else
                    class="status waiting"
                  >

                    <span
                      class="status-dot"
                    ></span>

                    Belum Bayar

                  </span>

                </td>

                <td>

                  <button
                    v-if="
                      !isMemberLunas(
                        item
                      )
                    "
                    class="pay-button"
                    @click="
                      bukaPembayaran(
                        item,
                        'member'
                      )
                    "
                  >
                    Bayar
                  </button>

                  <span
                    v-else
                    class="paid-text"
                  >
                    ✓ Lunas
                  </span>

                </td>

              </tr>

              <!-- EMPTY -->

              <tr
                v-if="
                  !loading &&
                  memberFiltered.length ===
                    0
                "
              >

                <td
                  colspan="9"
                  class="empty-cell"
                >

                  <div
                    class="empty-state"
                  >

                    <div
                      class="empty-icon tosca-empty"
                    >
                      ♡
                    </div>

                    <strong>
                      Data member tidak ditemukan
                    </strong>

                    <span>
                      Coba gunakan kata pencarian lain.
                    </span>

                  </div>

                </td>

              </tr>

              <!-- LOADING -->

              <tr
                v-if="loading"
              >

                <td
                  colspan="9"
                  class="empty-cell"
                >

                  <div
                    class="empty-state"
                  >

                    <div
                      class="loader"
                    ></div>

                    <strong>
                      Memuat data...
                    </strong>

                  </div>

                </td>

              </tr>

            </tbody>

          </table>

        </div>

      </section>

    </main>

    <!-- ==================================================
         MODAL PEMBAYARAN
    ================================================== -->

    <div
      v-if="
        showPaymentModal
      "
      class="modal-overlay"
      @click.self="
        tutupPembayaran
      "
    >

      <div class="modal">

        <div
          class="modal-header"
        >

          <div>

            <span
              class="modal-kicker"
            >
              {{
                selectedData?.type ===
                "member"
                  ? "TAGIHAN MEMBER"
                  : "PEMBAYARAN PARKIR"
              }}
            </span>

            <h3>
              {{
                selectedData?.type ===
                "member"
                  ? "Pembayaran Bulanan"
                  : "Pembayaran Kendaraan"
              }}
            </h3>

          </div>

          <button
            class="close-button"
            @click="
              tutupPembayaran
            "
          >
            ×
          </button>

        </div>

        <div
          class="modal-body"
        >

          <div
            class="payment-info"
          >

            <div
              class="payment-info-row"
            >

              <span>
                {{
                  selectedData?.type ===
                  "member"
                    ? "Kode Member"
                    : "Kode Tiket"
                }}
              </span>

              <strong>
                {{
                  selectedData?.type ===
                  "member"
                    ? selectedData?.kode_member
                    : selectedData?.kode_tiket
                }}
              </strong>

            </div>

            <div
              v-if="
                selectedData?.type ===
                'member'
              "
              class="payment-info-row"
            >

              <span>
                Nama Member
              </span>

              <strong>
                {{
                  selectedData?.nama_member ||
                  "-"
                }}
              </strong>

            </div>

            <div
              v-else
              class="payment-info-row"
            >

              <span>
                Plat Nomor
              </span>

              <strong>
                {{
                  selectedData?.plat_nomor ||
                  selectedData?.nomor_polisi ||
                  selectedData?.nomor_plat ||
                  "-"
                }}
              </strong>

            </div>

            <div
              v-if="
                selectedData?.type ===
                  'nonmember'
              "
              class="payment-info-row"
            >

              <span>
                Kendaraan
              </span>

              <strong>
                {{
                  selectedData?.jenis_kendaraan ||
                  selectedData?.jenis ||
                  "-"
                }}
              </strong>

            </div>

          </div>

          <div
            class="total-box"
          >

            <div
              class="total-info"
            >

              <span>
                Total Tagihan
              </span>

              <small>
                Jumlah yang harus dibayar
              </small>

            </div>

            <strong>
              {{
                formatRupiah(
                  selectedTotal
                )
              }}
            </strong>

          </div>

          <label
            class="input-label"
          >
            Jumlah Pembayaran
          </label>

          <div
            class="payment-input"
          >

            <span>
              Rp
            </span>

            <input
              v-model.number="
                paymentAmount
              "
              type="number"
              min="0"
              placeholder="0"
            />

          </div>

          <div
            v-if="
              paymentAmount &&
              kekurangan > 0
            "
            class="warning-payment"
          >

            <span
              class="warning-icon"
            >
              !
            </span>

            <div>

              <strong>
                Pembayaran kurang
              </strong>

              <small>
                Kurang
                {{
                  formatRupiah(
                    kekurangan
                  )
                }}
              </small>

            </div>

          </div>

          <div
            v-if="
              paymentAmount &&
              kembalian > 0
            "
            class="change-box"
          >

            <div>

              <span>
                Kembalian
              </span>

              <small>
                Uang yang harus dikembalikan
              </small>

            </div>

            <strong>
              {{
                formatRupiah(
                  kembalian
                )
              }}
            </strong>

          </div>

          <div
            v-if="paymentError"
            class="modal-error"
          >

            <span>
              !
            </span>

            {{
              paymentError
            }}

          </div>

        </div>

        <div
          class="modal-footer"
        >

          <button
            class="cancel-button"
            @click="
              tutupPembayaran
            "
          >
            Batal
          </button>

          <button
            class="confirm-button"
            :disabled="
              paymentLoading ||
              !paymentAmount ||
              paymentAmount <
                selectedTotal
            "
            @click="
              prosesPembayaran
            "
          >

            {{
              paymentLoading
                ? "Memproses..."
                : "Konfirmasi Pembayaran"
            }}

          </button>

        </div>

      </div>

    </div>

  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.page {
  --bg-0: #0a0e16;
  --bg-1: #0e1420;
  --surface: #121a28;
  --surface-2: #17202f;
  --surface-3: #1c2636;

  --border: #232f42;
  --border-soft: #1b2536;

  --text: #e7ecf5;
  --text-dim: #8fa0b8;
  --text-faint: #5c6c85;

  --blue: #3d8bff;
  --cyan: #22d3ee;
  --violet: #7c6bff;
  --teal: #2dd4bf;
  --amber: #f5b23c;
  --rose: #fb5b7c;

  --grad-primary:
    linear-gradient(
      135deg,
      #3d8bff 0%,
      #22d3ee 100%
    );

  --grad-violet:
    linear-gradient(
      135deg,
      #7c6bff 0%,
      #3d8bff 100%
    );

  --grad-teal:
    linear-gradient(
      135deg,
      #2dd4bf 0%,
      #22a3d8 100%
    );

  --grad-amber:
    linear-gradient(
      135deg,
      #f5b23c 0%,
      #f08a3c 100%
    );
}

.page {
  min-height: 100vh;

  background:
    radial-gradient(
      circle at 10% 0%,
      rgba(
        61,
        139,
        255,
        0.13
      ),
      transparent 32%
    ),
    radial-gradient(
      circle at 92% 8%,
      rgba(
        124,
        107,
        255,
        0.12
      ),
      transparent 30%
    ),
    radial-gradient(
      circle at 85% 92%,
      rgba(
        34,
        211,
        238,
        0.08
      ),
      transparent 32%
    ),
    linear-gradient(
      180deg,
      var(--bg-1),
      var(--bg-0)
    );

  color: var(--text);
}

/* TOPBAR */

.topbar {
  height: 88px;
  min-height: 88px;

  background:
    rgba(
      14,
      20,
      32,
      0.86
    );

  border-bottom:
    1px solid
    var(--border-soft);

  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 0 42px;

  position: sticky;
  top: 0;

  z-index: 20;

  backdrop-filter:
    blur(14px);

  gap: 24px;
}

.top-left {
  display: flex;
  align-items: center;

  gap: 22px;

  min-width: 0;

  flex: 1 1 auto;
}

.back-button {
  width: 104px;
  min-width: 104px;
  max-width: 104px;

  height: 46px;

  border:
    1px solid
    var(--border);

  border-radius: 13px;

  background:
    var(--surface);

  color:
    var(--text-dim);

  font-size: 13px;
  font-weight: 700;

  cursor: pointer;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 8px;

  flex: 0 0 104px;

  transition: 0.2s;
}

.back-button:hover {
  background:
    var(--surface-2);

  border-color:
    var(--blue);

  color:
    #bcdcff;
}

.back-icon {
  font-size: 25px;
  line-height: 1;
}

.top-divider {
  width: 1px;
  height: 40px;

  background:
    var(--border);
}

.title-area {
  min-width: 0;

  flex: 1 1 auto;

  display: flex;
  flex-direction: column;
}

.title-area h1,
.title-area p {
  white-space: nowrap;
  overflow: hidden;
  text-overflow:
    ellipsis;
}

.title-badge {
  color:
    var(--cyan);

  font-size: 9px;
  font-weight: 900;

  letter-spacing:
    1.3px;
}

.top-left h1 {
  margin: 0;

  color:
    var(--text);

  font-size: 22px;
  font-weight: 850;
}

.top-left p {
  margin:
    3px 0 0;

  color:
    var(--text-faint);

  font-size: 11px;
}

.profile {
  display: flex;
  align-items: center;

  gap: 11px;

  flex: 0 0 auto;
}

.profile-text {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.profile-text strong {
  color:
    var(--text);

  font-size: 13px;
}

.profile-avatar {
  width: 45px;
  height: 45px;

  border-radius: 15px;

  background:
    var(--grad-primary);

  color:
    #051022;

  display: flex;
  align-items: center;
  justify-content: center;

  font-size: 17px;
  font-weight: 850;

  box-shadow:
    0 7px 18px
    rgba(
      61,
      139,
      255,
      0.35
    );
}

/* CONTENT */

.content {
  width:
    calc(100% - 84px);

  max-width:
    1500px;

  margin: 0 auto;

  padding:
    28px 0 55px;
}

.heading-row {
  display: flex;

  justify-content:
    space-between;

  align-items:
    flex-end;

  margin-bottom: 23px;
}

.heading-label {
  color:
    var(--cyan);

  font-size: 10px;
  font-weight: 900;

  letter-spacing: 1px;
}

.heading-row h2 {
  margin: 0;

  color:
    var(--text);

  font-size: 29px;
  font-weight: 850;
}

.heading-row p {
  margin:
    5px 0 0;

  color:
    var(--text-dim);

  font-size: 13px;
}

.refresh-button {
  height: 43px;

  padding:
    0 17px;

  border:
    1px solid
    rgba(
      61,
      139,
      255,
      0.4
    );

  border-radius: 12px;

  background:
    rgba(
      61,
      139,
      255,
      0.1
    );

  color:
    #7fb4ff;

  display: flex;
  align-items: center;

  gap: 7px;

  font-size: 12px;
  font-weight: 800;

  cursor: pointer;
}

.refresh-button:hover {
  background:
    rgba(
      61,
      139,
      255,
      0.18
    );
}

/* STATS */

.stats-grid {
  display: grid;

  grid-template-columns:
    repeat(4, 1fr);

  gap: 16px;

  margin-bottom: 24px;
}

.stat-card {
  min-height: 130px;

  padding:
    20px 21px;

  border-radius: 19px;

  display: flex;
  align-items: center;
  justify-content:
    space-between;

  position: relative;

  overflow: hidden;

  border:
    1px solid
    var(--border);

  background:
    var(--surface);
}

.stat-card::before {
  content: "";

  position: absolute;
  inset: 0;

  opacity: 0.14;
}

.stat-card::after {
  content: "";

  position: absolute;

  width: 130px;
  height: 130px;

  right: -45px;
  bottom: -55px;

  border-radius: 50%;

  background:
    rgba(
      255,
      255,
      255,
      0.03
    );
}

.stat-pink::before {
  background:
    var(--grad-primary);
}

.stat-yellow::before {
  background:
    var(--grad-amber);
}

.stat-tosca::before {
  background:
    var(--grad-teal);
}

.stat-cream::before {
  background:
    var(--grad-violet);
}

.stat-content {
  display: flex;
  flex-direction: column;

  position: relative;
  z-index: 2;
}

.stat-label {
  color:
    var(--text-dim);

  font-size: 12px;
  font-weight: 700;
}

.stat-content strong {
  margin-top: 9px;

  color:
    var(--text);

  font-size: 28px;
}

.stat-content small {
  margin-top: 8px;

  color:
    var(--text-faint);

  font-size: 10px;
}

.stat-content .income {
  font-size: 20px;
}

.stat-icon {
  width: 51px;
  height: 51px;

  border-radius: 16px;

  display: flex;
  align-items: center;
  justify-content: center;

  font-size: 20px;
  font-weight: 900;

  position: relative;
  z-index: 2;
}

.pink-icon {
  background:
    rgba(
      61,
      139,
      255,
      0.14
    );

  color:
    #7fb4ff;
}

.yellow-icon {
  background:
    rgba(
      245,
      178,
      60,
      0.14
    );

  color:
    var(--amber);
}

.tosca-icon {
  background:
    rgba(
      45,
      212,
      191,
      0.14
    );

  color:
    var(--teal);
}

.income-icon {
  background:
    rgba(
      124,
      107,
      255,
      0.16
    );

  color:
    #a89bff;

  font-size: 13px;
}

/* TABLE */

.table-card {
  background:
    var(--surface);

  border:
    1px solid
    var(--border);

  border-radius:
    21px;

  overflow:
    hidden;

  box-shadow:
    0 16px 40px
    rgba(
      0,
      0,
      0,
      0.35
    );
}

.toolbar {
  min-height: 88px;

  padding:
    18px 22px;

  display: flex;

  justify-content:
    space-between;

  align-items:
    center;

  gap: 20px;

  border-bottom:
    1px solid
    var(--border-soft);

  background:
    linear-gradient(
      90deg,
      rgba(
        61,
        139,
        255,
        0.07
      ),
      rgba(
        124,
        107,
        255,
        0.05
      ),
      rgba(
        34,
        211,
        238,
        0.05
      )
    );
}

.tabs {
  display: flex;

  background:
    var(--bg-1);

  padding: 5px;

  border-radius: 14px;

  border:
    1px solid
    var(--border);
}

.tab-button {
  min-width: 175px;

  height: 43px;

  border: none;

  border-radius: 10px;

  background:
    transparent;

  color:
    var(--text-dim);

  font-size: 12px;
  font-weight: 800;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 8px;

  cursor: pointer;
}

.tab-button.active {
  background:
    var(--surface-3);

  color:
    #7fb4ff;
}

.tab-button:nth-child(2).active {
  color:
    var(--teal);
}

.dot {
  width: 7px;
  height: 7px;

  border-radius: 50%;
}

.pink-dot {
  background:
    var(--blue);
}

.tosca-dot {
  background:
    var(--teal);
}

.count {
  min-width: 22px;
  height: 21px;

  padding: 0 6px;

  border-radius: 20px;

  display: flex;
  align-items: center;
  justify-content: center;

  font-size: 10px;
}

.pink-count {
  background:
    rgba(
      61,
      139,
      255,
      0.18
    );

  color:
    #7fb4ff;
}

.tosca-count {
  background:
    rgba(
      45,
      212,
      191,
      0.18
    );

  color:
    var(--teal);
}

/* SEARCH */

.search-box {
  width: 360px;
  height: 45px;

  border:
    1px solid
    var(--border);

  border-radius: 13px;

  display: flex;
  align-items: center;

  padding:
    0 13px;

  background:
    var(--bg-1);
}

.search-box:focus-within {
  border-color:
    var(--blue);

  box-shadow:
    0 0 0 3px
    rgba(
      61,
      139,
      255,
      0.14
    );
}

.search-icon {
  color:
    var(--text-faint);

  font-size: 22px;

  margin-right: 7px;
}

.search-box input {
  border: none;
  outline: none;

  width: 100%;

  background:
    transparent;

  color:
    var(--text);

  font-size: 12px;
}

.clear-search {
  border: none;

  background:
    transparent;

  color:
    var(--text-dim);

  font-size: 20px;

  cursor: pointer;
}

/* ERROR */

.error-box {
  margin:
    15px 22px 0;

  padding:
    12px 15px;

  border-radius: 11px;

  border:
    1px solid
    rgba(
      251,
      91,
      124,
      0.35
    );

  background:
    rgba(
      251,
      91,
      124,
      0.08
    );

  color:
    #ff90a6;

  font-size: 12px;

  display: flex;
  align-items: center;

  gap: 8px;
}

.error-icon {
  width: 20px;
  height: 20px;

  border-radius: 50%;

  background:
    var(--rose);

  color:
    #170812;

  display: flex;
  align-items: center;
  justify-content: center;

  font-weight: 900;
}

/* TABLE */

.table-wrapper {
  width: 100%;

  overflow-x: auto;
}

table {
  width: 100%;

  min-width:
    1120px;

  border-collapse:
    collapse;
}

thead {
  background:
    var(--bg-1);
}

th {
  padding:
    15px 17px;

  border-bottom:
    1px solid
    var(--border-soft);

  color:
    var(--text-faint);

  font-size: 10px;
  font-weight: 900;

  text-align: left;

  white-space:
    nowrap;
}

td {
  padding:
    16px 17px;

  border-bottom:
    1px solid
    var(--border-soft);

  color:
    var(--text-dim);

  font-size: 12px;

  vertical-align:
    middle;
}

tbody tr:hover {
  background:
    rgba(
      61,
      139,
      255,
      0.05
    );
}

.number-cell {
  color:
    var(--text-faint);

  font-weight: 700;
}

.ticket-code,
.member-code {
  display: inline-flex;

  padding:
    7px 10px;

  border-radius: 8px;

  background:
    rgba(
      61,
      139,
      255,
      0.12
    );

  color:
    #7fb4ff;

  font-weight:
    850;
}

.member-code {
  background:
    rgba(
      45,
      212,
      191,
      0.12
    );

  color:
    var(--teal);
}

.plate {
  color:
    var(--text);

  font-size: 13px;
}

.member-name {
  color:
    var(--text);

  font-size: 13px;
}

.vehicle-text,
.company-text {
  color:
    var(--text-dim);
}

.datetime {
  display: flex;

  flex-direction:
    column;

  gap: 3px;
}

.datetime strong {
  color:
    var(--text);

  font-size: 12px;
}

.datetime span {
  color:
    var(--text-faint);

  font-size: 10px;
}

.money {
  color:
    var(--text);

  white-space:
    nowrap;
}

.paid-money {
  color:
    var(--teal);
}

/* STATUS */

.status {
  display: inline-flex;

  align-items: center;

  gap: 6px;

  padding:
    7px 10px;

  border-radius: 25px;

  font-size: 10px;

  font-weight: 800;

  white-space:
    nowrap;
}

.status-dot {
  width: 6px;
  height: 6px;

  border-radius: 50%;
}

.status.success {
  background:
    rgba(
      45,
      212,
      191,
      0.14
    );

  color:
    var(--teal);
}

.status.success .status-dot {
  background:
    var(--teal);
}

.status.waiting {
  background:
    rgba(
      245,
      178,
      60,
      0.14
    );

  color:
    var(--amber);
}

.status.waiting .status-dot {
  background:
    var(--amber);
}

/* BUTTON */

.pay-button {
  border: none;

  background:
    var(--grad-primary);

  color:
    #051022;

  height: 34px;

  padding:
    0 14px;

  border-radius: 9px;

  font-size: 11px;
  font-weight: 800;

  cursor: pointer;
}

.paid-text {
  color:
    var(--teal);

  font-weight:
    800;

  font-size: 11px;
}

.waiting-text {
  color:
    var(--text-faint);

  font-size: 11px;
}

/* EMPTY */

.empty-cell {
  height: 230px;

  text-align:
    center;
}

.empty-state {
  display: flex;

  flex-direction:
    column;

  align-items:
    center;

  justify-content:
    center;

  gap: 6px;
}

.empty-icon {
  width: 52px;
  height: 52px;

  margin-bottom: 5px;

  border-radius: 16px;

  display: flex;

  align-items:
    center;

  justify-content:
    center;

  font-size: 23px;
}

.pink-empty {
  background:
    rgba(
      61,
      139,
      255,
      0.12
    );

  color:
    #7fb4ff;
}

.tosca-empty {
  background:
    rgba(
      45,
      212,
      191,
      0.12
    );

  color:
    var(--teal);
}

.empty-state strong {
  color:
    var(--text-dim);

  font-size: 12px;
}

.empty-state span {
  color:
    var(--text-faint);

  font-size: 10px;
}

/* LOADER */

.loader {
  width: 30px;
  height: 30px;

  border:
    3px solid
    var(--border);

  border-top-color:
    var(--blue);

  border-radius:
    50%;

  animation:
    spin 0.8s linear infinite;

  margin-bottom:
    8px;
}

@keyframes spin {
  to {
    transform:
      rotate(360deg);
  }
}

/* MODAL */

.modal-overlay {
  position: fixed;

  inset: 0;

  background:
    rgba(
      4,
      7,
      14,
      0.72
    );

  backdrop-filter:
    blur(6px);

  display: flex;

  align-items:
    center;

  justify-content:
    center;

  padding: 18px;

  z-index: 100;
}

.modal {
  width: 100%;

  max-width: 455px;

  background:
    var(--surface);

  border-radius:
    21px;

  overflow:
    hidden;

  box-shadow:
    0 30px 80px
    rgba(
      0,
      0,
      0,
      0.55
    );

  border:
    1px solid
    var(--border);
}

.modal-header {
  padding:
    20px 22px 17px;

  border-bottom:
    1px solid
    var(--border-soft);

  display: flex;

  justify-content:
    space-between;

  background:
    linear-gradient(
      135deg,
      rgba(
        61,
        139,
        255,
        0.12
      ),
      rgba(
        124,
        107,
        255,
        0.08
      ),
      rgba(
        34,
        211,
        238,
        0.06
      )
    );
}

.modal-kicker {
  color:
    var(--cyan);

  font-size: 9px;
  font-weight: 900;

  letter-spacing:
    1px;
}

.modal-header h3 {
  margin:
    5px 0 0;

  color:
    var(--text);

  font-size: 20px;
  font-weight: 850;
}

.close-button {
  width: 34px;
  height: 34px;

  border: none;

  border-radius: 10px;

  background:
    rgba(
      255,
      255,
      255,
      0.06
    );

  color:
    #7fb4ff;

  font-size:
    22px;

  cursor:
    pointer;
}

.modal-body {
  padding:
    20px 22px;
}

.payment-info {
  border:
    1px solid
    var(--border-soft);

  border-radius: 12px;

  overflow:
    hidden;

  margin-bottom:
    15px;

  background:
    var(--bg-1);
}

.payment-info-row {
  padding:
    10px 13px;

  display: flex;

  justify-content:
    space-between;

  gap: 15px;

  border-bottom:
    1px solid
    var(--border-soft);
}

.payment-info-row:last-child {
  border-bottom:
    none;
}

.payment-info-row span {
  color:
    var(--text-faint);

  font-size: 11px;
}

.payment-info-row strong {
  color:
    var(--text);

  font-size: 12px;

  text-align:
    right;
}

.total-box {
  padding:
    14px;

  border-radius:
    13px;

  background:
    linear-gradient(
      135deg,
      rgba(
        61,
        139,
        255,
        0.14
      ),
      rgba(
        34,
        211,
        238,
        0.1
      ),
      rgba(
        124,
        107,
        255,
        0.1
      )
    );

  display: flex;

  justify-content:
    space-between;

  align-items:
    center;

  margin-bottom:
    17px;

  border:
    1px solid
    var(--border-soft);
}

.total-info {
  display: flex;

  flex-direction:
    column;
}

.total-box span {
  color:
    var(--text-dim);

  font-size: 11px;
}

.total-box small {
  color:
    var(--text-faint);

  font-size: 9px;
}

.total-box strong {
  color:
    #7fb4ff;

  font-size: 18px;
}

.input-label {
  display: block;

  margin-bottom: 7px;

  color:
    var(--text-dim);

  font-size: 11px;

  font-weight: 800;
}

.payment-input {
  height: 48px;

  border:
    1px solid
    var(--border);

  border-radius:
    12px;

  display: flex;

  align-items:
    center;

  padding:
    0 13px;

  background:
    var(--bg-1);
}

.payment-input span {
  color:
    #7fb4ff;

  font-size: 13px;

  font-weight: 900;

  margin-right: 8px;
}

.payment-input input {
  width: 100%;

  border: none;

  outline: none;

  background:
    transparent;

  color:
    var(--text);

  font-size: 15px;

  font-weight: 750;
}

.warning-payment {
  margin-top:
    12px;

  padding:
    11px 13px;

  border-radius:
    11px;

  background:
    rgba(
      245,
      178,
      60,
      0.1
    );

  border:
    1px solid
    rgba(
      245,
      178,
      60,
      0.32
    );

  color:
    var(--amber);

  display: flex;

  align-items:
    center;

  gap: 10px;
}

.warning-icon {
  width: 25px;
  height: 25px;

  border-radius:
    8px;

  background:
    rgba(
      245,
      178,
      60,
      0.22
    );

  display: flex;

  align-items:
    center;

  justify-content:
    center;
}

.warning-payment div {
  display: flex;

  flex-direction:
    column;
}

.warning-payment strong {
  font-size: 11px;
}

.warning-payment small {
  font-size: 10px;
}

.change-box {
  margin-top:
    12px;

  padding:
    11px 13px;

  border-radius:
    11px;

  background:
    rgba(
      45,
      212,
      191,
      0.1
    );

  border:
    1px solid
    rgba(
      45,
      212,
      191,
      0.32
    );

  color:
    var(--teal);

  display: flex;

  justify-content:
    space-between;

  align-items:
    center;
}

.change-box div {
  display: flex;
  flex-direction:
    column;
}

.change-box small {
  color:
    #7fd8cb;

  font-size: 9px;
}

.modal-error {
  margin-top:
    12px;

  padding:
    11px 13px;

  background:
    rgba(
      251,
      91,
      124,
      0.1
    );

  border:
    1px solid
    rgba(
      251,
      91,
      124,
      0.32
    );

  border-radius:
    10px;

  color:
    #ff90a6;

  font-size: 11px;

  display: flex;

  align-items:
    center;

  gap: 7px;
}

.modal-error span {
  width: 18px;
  height: 18px;

  border-radius:
    50%;

  background:
    var(--rose);

  color:
    #170812;

  display: flex;

  align-items:
    center;

  justify-content:
    center;
}

.modal-footer {
  padding:
    15px 22px 20px;

  display: flex;

  justify-content:
    flex-end;

  gap: 8px;

  border-top:
    1px solid
    var(--border-soft);

  background:
    var(--bg-1);
}

.cancel-button,
.confirm-button {
  height: 41px;

  padding:
    0 16px;

  border-radius:
    11px;

  font-size: 11px;

  font-weight: 800;

  cursor: pointer;
}

.cancel-button {
  border:
    1px solid
    var(--border);

  background:
    var(--surface);

  color:
    var(--text-dim);
}

.confirm-button {
  border: none;

  background:
    var(--grad-primary);

  color:
    #051022;

  box-shadow:
    0 8px 18px
    rgba(
      61,
      139,
      255,
      0.3
    );
}

.confirm-button:disabled {
  opacity:
    0.4;

  cursor:
    not-allowed;

  box-shadow:
    none;
}

/* RESPONSIVE */

@media (
  max-width: 1100px
) {
  .topbar {
    padding:
      0 25px;
  }

  .content {
    width:
      calc(100% - 50px);
  }

  .stats-grid {
    grid-template-columns:
      repeat(2, 1fr);
  }

  .toolbar {
    flex-direction:
      column;

    align-items:
      stretch;
  }

  .search-box {
    width: 100%;
  }
}

@media (
  max-width: 700px
) {
  .topbar {
    height: auto;

    min-height: 76px;

    padding:
      12px 16px;

    gap: 12px;
  }

  .top-divider,
  .profile-text {
    display:
      none;
  }

  .top-left {
    gap:
      12px;
  }

  .top-left h1 {
    font-size:
      18px;
  }

  .back-button {
    width:
      94px;

    min-width:
      94px;

    max-width:
      94px;

    flex-basis:
      94px;
  }

  .content {
    width:
      calc(100% - 28px);

    padding-top:
      18px;
  }

  .heading-row {
    flex-direction:
      column;

    align-items:
      stretch;

    gap:
      14px;
  }

  .heading-row h2 {
    font-size:
      25px;
  }

  .refresh-button {
    width:
      100%;

    justify-content:
      center;
  }

  .stats-grid {
    grid-template-columns:
      1fr;
  }

  .tabs {
    width:
      100%;
  }

  .tab-button {
    min-width:
      0;

    width:
      50%;
  }

  .modal {
    max-height:
      90vh;

    overflow-y:
      auto;
  }

  .modal-footer {
    flex-direction:
      column-reverse;
  }

  .cancel-button,
  .confirm-button {
    width:
      100%;
  }
}
</style>
```

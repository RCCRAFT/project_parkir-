<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import axios from "axios";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

definePageMeta({
  layout: "admin",
});

/* =========================================================
   API
========================================================= */

const API = "http://127.0.0.1:8000/api";

const transaksi = ref<any[]>([]);
const loading = ref(true);
const refreshing = ref(false);
const errorMessage = ref("");
const lastUpdated = ref("");
const search = ref("");
const filterStatus = ref("semua");
const filterKendaraan = ref("semua");
const filterPeriode = ref("semua");

const user = ref({
  name: "Petugas",
  role: "Petugas Parkir",
});

let refreshTimer: ReturnType<typeof setInterval> | null = null;

/* =========================================================
   AUTH
========================================================= */

const getToken = () => {
  if (import.meta.client) {
    return localStorage.getItem("token") || "";
  }

  return "";
};

const authConfig = () => {
  const token = getToken();

  return token
    ? {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      }
    : {};
};

/* =========================================================
   FORMAT
========================================================= */

const formatRupiah = (value: any) => {
  const number = Number(value || 0);

  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(number);
};

const formatTanggal = (value: any) => {
  if (!value) return "-";

  const date = new Date(value);

  if (isNaN(date.getTime())) return "-";

  return date.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
};

const formatJam = (value: any) => {
  if (!value) return "-";

  const date = new Date(value);

  if (isNaN(date.getTime())) return "-";

  return date.toLocaleTimeString("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
  });
};

const updateLastUpdated = () => {
  lastUpdated.value = new Date().toLocaleTimeString("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
};

/* =========================================================
   NORMALISASI DATA TRANSAKSI
========================================================= */

const getKodeTiket = (item: any) => {
  return item?.kode_tiket || "-";
};

const getPlat = (item: any) => {
  return item?.plat_nomor || "-";
};

const getKendaraan = (item: any) => {
  const kendaraan = String(
    item?.kendaraan || ""
  ).toLowerCase();

  if (kendaraan.includes("motor")) {
    return "Motor";
  }

  if (kendaraan.includes("mobil")) {
    return "Mobil";
  }

  if (kendaraan.includes("member")) {
    return "Member";
  }

  return item?.kendaraan || "Kendaraan";
};

const getWaktuMasuk = (item: any) => {
  return item?.masuk || null;
};

const getWaktuKeluar = (item: any) => {
  return item?.keluar || null;
};

const getTotal = (item: any) => {
  return Number(item?.total ?? 0);
};

const getStatus = (item: any) => {
  const status = String(
    item?.status || ""
  )
    .toLowerCase()
    .trim();

  return status === "lunas"
    ? "Lunas"
    : "Belum Bayar";
};

const isLunas = (item: any) => {
  return getStatus(item) === "Lunas";
};

/* =========================================================
   FETCH TRANSAKSI
========================================================= */

const fetchTransaksi = async () => {
  try {
    const response = await axios.get(
      `${API}/transaksi`,
      authConfig()
    );

    const data = response?.data?.data ?? [];

    transaksi.value = Array.isArray(data)
      ? data
      : [];
  } catch (error: any) {
    console.error(
      "Gagal mengambil transaksi:",
      error
    );

    if (transaksi.value.length === 0) {
      transaksi.value = [];
    }

    throw error;
  }
};

/* =========================================================
   FETCH DASHBOARD
========================================================= */

const fetchData = async (
  showLoading = true
) => {
  if (showLoading) {
    loading.value = true;
  }

  errorMessage.value = "";

  try {
    await fetchTransaksi();

    updateLastUpdated();
  } catch (error: any) {
    console.error(
      "Gagal mengambil data laporan:",
      error
    );

    errorMessage.value =
      error?.response?.data?.message ||
      "Gagal mengambil data laporan dari server.";
  } finally {
    loading.value = false;
  }
};

/* =========================================================
   AUTO REFRESH
========================================================= */

const refreshData = async () => {
  if (refreshing.value) return;

  refreshing.value = true;

  try {
    await fetchData(false);
  } finally {
    refreshing.value = false;
  }
};

/* =========================================================
   FILTER
========================================================= */

const filteredTransaksi = computed(() => {
  let data = [...transaksi.value];

  const keyword = search.value
    .trim()
    .toLowerCase();

  if (keyword) {
    data = data.filter((item) => {
      const kode = getKodeTiket(item)
        .toLowerCase();

      const plat = getPlat(item)
        .toLowerCase();

      const kendaraan = getKendaraan(item)
        .toLowerCase();

      return (
        kode.includes(keyword) ||
        plat.includes(keyword) ||
        kendaraan.includes(keyword)
      );
    });
  }

  if (filterStatus.value !== "semua") {
    data = data.filter((item) => {
      return (
        getStatus(item).toLowerCase() ===
        filterStatus.value
      );
    });
  }

  if (
    filterKendaraan.value !==
    "semua"
  ) {
    data = data.filter((item) => {
      return (
        getKendaraan(item).toLowerCase() ===
        filterKendaraan.value
      );
    });
  }

  if (
    filterPeriode.value !==
    "semua"
  ) {
    const now = new Date();

    data = data.filter((item) => {
      const waktu =
        getWaktuKeluar(item) ||
        getWaktuMasuk(item);

      if (!waktu) return false;

      const date = new Date(waktu);

      if (isNaN(date.getTime())) {
        return false;
      }

      if (
        filterPeriode.value ===
        "hari_ini"
      ) {
        return (
          date.getDate() ===
            now.getDate() &&
          date.getMonth() ===
            now.getMonth() &&
          date.getFullYear() ===
            now.getFullYear()
        );
      }

      if (
        filterPeriode.value ===
        "kemarin"
      ) {
        const yesterday =
          new Date(now);

        yesterday.setDate(
          now.getDate() - 1
        );

        return (
          date.getDate() ===
            yesterday.getDate() &&
          date.getMonth() ===
            yesterday.getMonth() &&
          date.getFullYear() ===
            yesterday.getFullYear()
        );
      }

      if (
        filterPeriode.value ===
        "minggu_ini"
      ) {
        const firstDay =
          new Date(now);

        firstDay.setDate(
          now.getDate() -
            now.getDay()
        );

        firstDay.setHours(
          0,
          0,
          0,
          0
        );

        return date >= firstDay;
      }

      return true;
    });
  }

  return data;
});

/* =========================================================
   STATISTIK
========================================================= */

const totalTransaksi = computed(() => {
  return transaksi.value.length;
});

const totalSudahBayar = computed(() => {
  return transaksi.value.filter(
    (item) => isLunas(item)
  ).length;
});

const totalBelumBayar = computed(() => {
  return transaksi.value.filter(
    (item) => !isLunas(item)
  ).length;
});

const totalMotor = computed(() => {
  return transaksi.value.filter(
    (item) =>
      getKendaraan(item).toLowerCase() ===
      "motor"
  ).length;
});

const totalMobil = computed(() => {
  return transaksi.value.filter(
    (item) =>
      getKendaraan(item).toLowerCase() ===
      "mobil"
  ).length;
});

/* =========================================================
   PENDAPATAN
========================================================= */

const totalPendapatan = computed(() => {
  return transaksi.value
    .filter((item) => isLunas(item))
    .reduce(
      (total, item) =>
        total + getTotal(item),
      0
    );
});

/* =========================================================
   TRANSAKSI HARI INI
========================================================= */

const transaksiHariIni = computed(() => {
  const now = new Date();

  return transaksi.value.filter(
    (item) => {
      const waktu =
        getWaktuKeluar(item) ||
        getWaktuMasuk(item);

      if (!waktu) return false;

      const date = new Date(waktu);

      if (isNaN(date.getTime())) {
        return false;
      }

      return (
        date.getDate() ===
          now.getDate() &&
        date.getMonth() ===
          now.getMonth() &&
        date.getFullYear() ===
          now.getFullYear()
      );
    }
  ).length;
});

/* =========================================================
   PENDAPATAN HARI INI
========================================================= */

const pendapatanHariIni = computed(() => {
  const now = new Date();

  return transaksi.value
    .filter((item) => {
      if (!isLunas(item)) {
        return false;
      }

      const waktu =
        getWaktuKeluar(item) ||
        getWaktuMasuk(item);

      if (!waktu) return false;

      const date = new Date(waktu);

      if (isNaN(date.getTime())) {
        return false;
      }

      return (
        date.getDate() ===
          now.getDate() &&
        date.getMonth() ===
          now.getMonth() &&
        date.getFullYear() ===
          now.getFullYear()
      );
    })
    .reduce(
      (total, item) =>
        total + getTotal(item),
      0
    );
});

/* =========================================================
   RATA-RATA TRANSAKSI
========================================================= */

const rataRataTransaksi = computed(() => {
  if (totalSudahBayar.value === 0) {
    return 0;
  }

  return (
    totalPendapatan.value /
    totalSudahBayar.value
  );
});

/* =========================================================
   CHART 7 HARI
========================================================= */

const chartData = computed(() => {
  const result: any[] = [];

  for (let i = 6; i >= 0; i--) {
    const date = new Date();

    date.setDate(
      date.getDate() - i
    );

    date.setHours(
      0,
      0,
      0,
      0
    );

    const nextDate =
      new Date(date);

    nextDate.setDate(
      date.getDate() + 1
    );

    const income =
      transaksi.value
        .filter((item) => {
          if (!isLunas(item)) {
            return false;
          }

          const waktu =
            getWaktuKeluar(item) ||
            getWaktuMasuk(item);

          if (!waktu) return false;

          const itemDate =
            new Date(waktu);

          if (
            isNaN(
              itemDate.getTime()
            )
          ) {
            return false;
          }

          return (
            itemDate >= date &&
            itemDate < nextDate
          );
        })
        .reduce(
          (total, item) =>
            total + getTotal(item),
          0
        );

    result.push({
      label:
        date.toLocaleDateString(
          "id-ID",
          {
            weekday: "short",
          }
        ),
      date,
      income,
    });
  }

  return result;
});

const maxChartValue = computed(() => {
  return Math.max(
    ...chartData.value.map(
      (item) => item.income
    ),
    1
  );
});

const chartBarHeight = (
  income: number
) => {
  if (income <= 0) {
    return 4;
  }

  return Math.max(
    (income /
      maxChartValue.value) *
      100,
    8
  );
};

/* =========================================================
   EXPORT PDF
========================================================= */

const exportPDF = () => {
  const rows =
    filteredTransaksi.value;

  if (!rows.length) {
    alert(
      "Tidak ada data yang bisa diekspor."
    );

    return;
  }

  const doc = new jsPDF({
    orientation: "landscape",
    unit: "mm",
    format: "a4",
  });

  const pageWidth =
    doc.internal.pageSize.getWidth();

  const pageHeight =
    doc.internal.pageSize.getHeight();

  /* =========================
     HEADER
  ========================= */

  doc.setFillColor(
    2,
    6,
    23
  );

  doc.rect(
    0,
    0,
    pageWidth,
    35,
    "F"
  );

  doc.setTextColor(
    255,
    255,
    255
  );

  doc.setFont(
    "helvetica",
    "bold"
  );

  doc.setFontSize(18);

  doc.text(
    "LAPORAN PARKIR",
    15,
    14
  );

  doc.setFont(
    "helvetica",
    "normal"
  );

  doc.setFontSize(9);

  doc.text(
    "Parkir Plaza Andalas - Ringkasan Aktivitas dan Pendapatan",
    15,
    21
  );

  doc.setFontSize(8);

  doc.text(
    `Dicetak: ${new Date().toLocaleString(
      "id-ID"
    )}`,
    15,
    28
  );

  /* =========================
     RINGKASAN
  ========================= */

  let y = 43;

  doc.setTextColor(
    15,
    23,
    42
  );

  doc.setFont(
    "helvetica",
    "bold"
  );

  doc.setFontSize(11);

  doc.text(
    "Ringkasan",
    15,
    y
  );

  y += 7;

  const summaryWidth =
    (pageWidth - 30 - 12) /
    4;

  const summaryHeight = 20;

  const summaries = [
    {
      title: "Total Transaksi",
      value:
        totalTransaksi.value.toString(),
    },
    {
      title: "Transaksi Lunas",
      value:
        totalSudahBayar.value.toString(),
    },
    {
      title: "Belum Bayar",
      value:
        totalBelumBayar.value.toString(),
    },
    {
      title: "Total Pendapatan",
      value:
        formatRupiah(
          totalPendapatan.value
        ),
    },
  ];

  summaries.forEach(
    (item, index) => {
      const x =
        15 +
        index *
          (summaryWidth + 4);

      doc.setFillColor(
        241,
        245,
        249
      );

      doc.roundedRect(
        x,
        y,
        summaryWidth,
        summaryHeight,
        3,
        3,
        "F"
      );

      doc.setFont(
        "helvetica",
        "normal"
      );

      doc.setFontSize(7);

      doc.setTextColor(
        100,
        116,
        139
      );

      doc.text(
        item.title,
        x + 5,
        y + 7
      );

      doc.setFont(
        "helvetica",
        "bold"
      );

      doc.setFontSize(10);

      doc.setTextColor(
        15,
        23,
        42
      );

      doc.text(
        item.value,
        x + 5,
        y + 15
      );
    }
  );

  /* =========================
     GRAFIK
  ========================= */

  y += 28;

  doc.setFont(
    "helvetica",
    "bold"
  );

  doc.setFontSize(11);

  doc.setTextColor(
    15,
    23,
    42
  );

  doc.text(
    "Pendapatan 7 Hari Terakhir",
    15,
    y
  );

  y += 6;

  const chartX = 15;
  const chartY = y;
  const chartWidth =
    pageWidth - 30;
  const chartHeight = 55;

  doc.setFillColor(
    248,
    250,
    252
  );

  doc.roundedRect(
    chartX,
    chartY,
    chartWidth,
    chartHeight,
    3,
    3,
    "F"
  );

  doc.setDrawColor(
    203,
    213,
    225
  );

  doc.line(
    chartX + 10,
    chartY +
      chartHeight -
      12,
    chartX +
      chartWidth -
      10,
    chartY +
      chartHeight -
      12
  );

  const chartItems =
    chartData.value;

  const chartMax =
    Math.max(
      ...chartItems.map(
        (item) =>
          Number(item.income)
      ),
      1
    );

  const barAreaWidth =
    chartWidth - 30;

  const barWidth = 14;

  const gap =
    (barAreaWidth -
      chartItems.length *
        barWidth) /
    (chartItems.length - 1);

  chartItems.forEach(
    (item, index) => {
      const x =
        chartX +
        15 +
        index *
          (barWidth + gap);

      const barMaxHeight =
        chartHeight - 22;

      const barHeight =
        item.income > 0
          ? Math.max(
              (Number(
                item.income
              ) /
                chartMax) *
                barMaxHeight,
              3
            )
          : 3;

      const barY =
        chartY +
        chartHeight -
        12 -
        barHeight;

      doc.setFillColor(
        30,
        64,
        175
      );

      doc.roundedRect(
        x,
        barY,
        barWidth,
        barHeight,
        2,
        2,
        "F"
      );

      doc.setFont(
        "helvetica",
        "bold"
      );

      doc.setFontSize(7);

      doc.setTextColor(
        71,
        85,
        105
      );

      doc.text(
        item.label,
        x +
          barWidth / 2,
        chartY +
          chartHeight -
          4,
        {
          align: "center",
        }
      );

      doc.setFont(
        "helvetica",
        "normal"
      );

      doc.setFontSize(5);

      const incomeText =
        item.income > 0
          ? formatRupiah(
              item.income
            )
          : "Rp0";

      doc.text(
        incomeText,
        x +
          barWidth / 2,
        Math.max(
          barY - 2,
          chartY + 5
        ),
        {
          align: "center",
        }
      );
    }
  );

  /* =========================
     TABEL
  ========================= */

  y +=
    chartHeight + 11;

  doc.setFont(
    "helvetica",
    "bold"
  );

  doc.setFontSize(11);

  doc.setTextColor(
    15,
    23,
    42
  );

  doc.text(
    "Riwayat Transaksi Parkir",
    15,
    y
  );

  const tableRows =
    rows.map((item) => [
      getKodeTiket(item),
      getPlat(item),
      getKendaraan(item),
      `${formatTanggal(
        getWaktuMasuk(item)
      )}\n${formatJam(
        getWaktuMasuk(item)
      )}`,
      `${formatTanggal(
        getWaktuKeluar(item)
      )}\n${formatJam(
        getWaktuKeluar(item)
      )}`,
      formatRupiah(
        getTotal(item)
      ),
      getStatus(item),
    ]);

  autoTable(doc, {
    startY: y + 5,

    head: [
      [
        "Kode Tiket",
        "Plat Nomor",
        "Kendaraan",
        "Masuk",
        "Keluar",
        "Total",
        "Status",
      ],
    ],

    body: tableRows,

    theme: "grid",

    styles: {
      font: "helvetica",
      fontSize: 7,
      cellPadding: 3,
      textColor: [
        51,
        65,
        85,
      ],
      lineColor: [
        226,
        232,
        240,
      ],
      lineWidth: 0.2,
    },

    headStyles: {
      fillColor: [
        2,
        6,
        23,
      ],
      textColor: [
        255,
        255,
        255,
      ],
      fontStyle: "bold",
      fontSize: 7,
    },

    alternateRowStyles: {
      fillColor: [
        248,
        250,
        252,
      ],
    },

    columnStyles: {
      0: {
        cellWidth: 28,
      },

      1: {
        cellWidth: 27,
      },

      2: {
        cellWidth: 25,
      },

      3: {
        cellWidth: 30,
      },

      4: {
        cellWidth: 30,
      },

      5: {
        cellWidth: 30,
      },

      6: {
        cellWidth: 28,
      },
    },

    didParseCell: (
      data
    ) => {
      if (
        data.section ===
          "body" &&
        data.column.index === 6
      ) {
        const status =
          String(
            data.cell.raw
          );

        if (
          status ===
          "Lunas"
        ) {
          data.cell.styles.textColor =
            [
              22,
              101,
              52,
            ];
        } else {
          data.cell.styles.textColor =
            [
              146,
              64,
              14,
            ];
        }
      }
    },

    didDrawPage: (
      data
    ) => {
      doc.setFont(
        "helvetica",
        "normal"
      );

      doc.setFontSize(7);

      doc.setTextColor(
        148,
        163,
        184
      );

      doc.text(
        `Laporan Parkir - Halaman ${data.pageNumber}`,
        15,
        pageHeight - 8
      );

      doc.text(
        "Sistem laporan parkir aktif",
        pageWidth - 15,
        pageHeight - 8,
        {
          align: "right",
        }
      );
    },
  });

  /* =========================
     DOWNLOAD
  ========================= */

  const tanggal =
    new Date()
      .toISOString()
      .slice(0, 10);

  doc.save(
    `laporan-parkir-${tanggal}.pdf`
  );
};

/* =========================================================
   KEMBALI
========================================================= */

const kembali = () => {
  navigateTo("/admin");
};

/* =========================================================
   MOUNT
========================================================= */

onMounted(async () => {
  await fetchData();

  refreshTimer =
    setInterval(() => {
      refreshData();
    }, 5000);
});

/* =========================================================
   UNMOUNT
========================================================= */

onBeforeUnmount(() => {
  if (refreshTimer) {
    clearInterval(
      refreshTimer
    );

    refreshTimer = null;
  }
});
</script>

<template>
  <div class="laporan-page">

    <header class="topbar">
      <div class="topbar-left">

        <button
          class="back-button"
          type="button"
          @click="kembali"
        >
          <span class="back-arrow">
            ←
          </span>

          <span class="back-text">
            Kembali
          </span>
        </button>

        <div class="top-divider"></div>

        <div class="top-title">

          <div class="top-title-icon">
            <span>▥</span>
          </div>

          <div class="top-title-content">
            <h1>Laporan Parkir</h1>

            <p>
              Ringkasan aktivitas dan pendapatan parkir
            </p>
          </div>

        </div>
      </div>

      <div class="profile">

        <div class="live-status">
          <span class="live-dot"></span>
          LIVE
        </div>

        <div class="profile-avatar">
          P
        </div>

        <div class="profile-info">
          <strong>
            {{ user.name }}
          </strong>

          <span>
            {{ user.role }}
          </span>
        </div>

      </div>
    </header>

    <main class="main-content">

      <section class="page-heading">

        <div class="heading-copy">

          <span class="eyebrow">
            PARKIR PLAZA ANDALAS
          </span>

          <h2>
            Dashboard
            <span>Laporan</span>
          </h2>

          <p>
            Pantau transaksi, kendaraan, dan pendapatan
            parkir secara langsung.
          </p>

        </div>

        <div class="heading-actions">

          <div class="last-update">

            <span class="update-dot"></span>

            Update:
            {{ lastUpdated || "Memuat..." }}

          </div>

          <button
            class="refresh-button"
            type="button"
            :disabled="refreshing"
            @click="refreshData"
          >
            <span
              :class="{
                spinning: refreshing,
              }"
            >
              ↻
            </span>

            {{
              refreshing
                ? "Memuat..."
                : "Refresh"
            }}
          </button>

          <button
            class="export-button"
            type="button"
            @click="exportPDF"
          >
            ↓
            Export PDF
          </button>

        </div>
      </section>

      <div
        v-if="errorMessage"
        class="error-box"
      >
        <span class="error-icon">
          !
        </span>

        <div>
          <strong>
            Koneksi data bermasalah
          </strong>

          <p>
            {{ errorMessage }}
          </p>
        </div>
      </div>

      <section class="stats-grid">

        <div class="stat-card card-dark">

          <div class="stat-icon icon-dark">
            ▣
          </div>

          <div class="stat-content">

            <span>
              TOTAL TRANSAKSI
            </span>

            <strong>
              {{ totalTransaksi }}
            </strong>

            <small>
              Semua transaksi parkir
            </small>

          </div>

          <div class="card-glow"></div>
        </div>

        <div class="stat-card card-blue">

          <div class="stat-icon icon-blue">
            ✓
          </div>

          <div class="stat-content">

            <span>
              TRANSAKSI LUNAS
            </span>

            <strong>
              {{ totalSudahBayar }}
            </strong>

            <small>
              Pembayaran berhasil
            </small>

          </div>

          <div class="card-glow"></div>
        </div>

        <div class="stat-card card-white">

          <div class="stat-icon icon-money">
            Rp
          </div>

          <div class="stat-content">

            <span>
              TOTAL PENDAPATAN
            </span>

            <strong>
              {{ formatRupiah(totalPendapatan) }}
            </strong>

            <small>
              Pendapatan keseluruhan
            </small>

          </div>

          <div class="card-glow"></div>
        </div>

        <div class="stat-card card-light">

          <div class="stat-icon icon-light">
            ↗
          </div>

          <div class="stat-content">

            <span>
              TRANSAKSI HARI INI
            </span>

            <strong>
              {{ transaksiHariIni }}
            </strong>

            <small>
              Aktivitas hari ini
            </small>

          </div>

          <div class="card-glow"></div>
        </div>

      </section>

      <section class="analytics-grid">

        <div class="chart-panel">

          <div class="panel-header">

            <div>

              <span class="panel-label">
                ANALISIS PENDAPATAN
              </span>

              <h3>
                Pendapatan 7 Hari Terakhir
              </h3>

              <p>
                Data diambil langsung dari transaksi parkir
              </p>

            </div>

            <div class="chart-badge">
              <span></span>
              LIVE · 7 HARI
            </div>

          </div>

          <div class="chart-summary">

            <div class="summary-box">

              <span>
                Pendapatan hari ini
              </span>

              <strong>
                {{ formatRupiah(pendapatanHariIni) }}
              </strong>

            </div>

            <div class="summary-box">

              <span>
                Rata-rata transaksi
              </span>

              <strong>
                {{ formatRupiah(rataRataTransaksi) }}
              </strong>

            </div>

          </div>

          <div class="chart-area">

            <div
              v-for="(item, index) in chartData"
              :key="index"
              class="chart-column"
            >

              <div class="chart-value">
                {{ formatRupiah(item.income) }}
              </div>

              <div class="bar-wrapper">

                <div
                  class="chart-bar"
                  :style="{
                    height: `${chartBarHeight(item.income)}%`,
                  }"
                >
                  <span></span>
                </div>

              </div>

              <div class="chart-label">
                {{ item.label }}
              </div>

            </div>

          </div>

        </div>

        <div class="today-panel">

          <div class="today-grid-glow"></div>

          <div class="today-top">

            <div>

              <span class="panel-label">
                RINGKASAN HARI INI
              </span>

              <h3>
                Hari Ini
              </h3>

              <p class="today-date">
                {{ formatTanggal(new Date()) }}
              </p>

            </div>

            <div class="today-live">
              <span></span>
              LIVE
            </div>

          </div>

          <div class="today-income">

            <span>
              Pendapatan
            </span>

            <strong>
              {{ formatRupiah(pendapatanHariIni) }}
            </strong>

          </div>

          <div class="today-divider"></div>

          <div class="mini-stat">

            <div class="mini-icon">
              #
            </div>

            <div>

              <span>
                Total Transaksi
              </span>

              <strong>
                {{ transaksiHariIni }}
              </strong>

            </div>

          </div>

          <div class="mini-stat">

            <div class="mini-icon">
              M
            </div>

            <div>

              <span>
                Motor
              </span>

              <strong>
                {{ totalMotor }}
              </strong>

            </div>

          </div>

          <div class="mini-stat">

            <div class="mini-icon">
              C
            </div>

            <div>

              <span>
                Mobil
              </span>

              <strong>
                {{ totalMobil }}
              </strong>

            </div>

          </div>

        </div>

      </section>

      <section class="table-panel">

        <div class="table-heading">

          <div>

            <span class="panel-label">
              DATA TRANSAKSI
            </span>

            <h3>
              Riwayat Transaksi Parkir
            </h3>

            <p>
              Data transaksi diperbarui otomatis setiap 5 detik.
            </p>

          </div>

          <div class="table-heading-right">

            <div class="live-table-status">
              <span></span>
              AUTO UPDATE
            </div>

            <div class="table-count">
              {{ filteredTransaksi.length }}
              data
            </div>

          </div>

        </div>

        <div class="filters">

          <div class="search-box">

            <span class="search-icon">
              ⌕
            </span>

            <input
              v-model="search"
              type="text"
              placeholder="Cari kode tiket, plat, kendaraan..."
            />

          </div>

          <select v-model="filterStatus">

            <option value="semua">
              Semua Status
            </option>

            <option value="lunas">
              Lunas
            </option>

            <option value="belum bayar">
              Belum Bayar
            </option>

          </select>

          <select v-model="filterKendaraan">

            <option value="semua">
              Semua Kendaraan
            </option>

            <option value="mobil">
              Mobil
            </option>

            <option value="motor">
              Motor
            </option>

          </select>

          <select v-model="filterPeriode">

            <option value="semua">
              Semua Periode
            </option>

            <option value="hari_ini">
              Hari Ini
            </option>

            <option value="kemarin">
              Kemarin
            </option>

            <option value="minggu_ini">
              Minggu Ini
            </option>

          </select>

        </div>

        <div class="table-wrapper">

          <table>

            <thead>

              <tr>
                <th>KODE TIKET</th>
                <th>PLAT NOMOR</th>
                <th>KENDARAAN</th>
                <th>MASUK</th>
                <th>KELUAR</th>
                <th>TOTAL</th>
                <th>STATUS</th>
              </tr>

            </thead>

            <tbody>

              <tr v-if="loading">

                <td
                  colspan="7"
                  class="empty-cell"
                >

                  <div class="loading-box">

                    <div class="loading-circle"></div>

                    <span>
                      Mengambil data dari database...
                    </span>

                  </div>

                </td>

              </tr>

              <tr
                v-else-if="
                  filteredTransaksi.length === 0
                "
              >

                <td
                  colspan="7"
                  class="empty-cell"
                >

                  <div class="empty-box">

                    <div class="empty-icon">
                      —
                    </div>

                    <strong>
                      Tidak ada transaksi
                    </strong>

                    <span>
                      Belum ada data yang sesuai
                      dengan filter.
                    </span>

                  </div>

                </td>

              </tr>

              <tr
                v-for="(item, index) in filteredTransaksi"
                :key="item.id || index"
              >

                <td>

                  <span class="ticket-code">
                    {{ getKodeTiket(item) }}
                  </span>

                </td>

                <td>

                  <strong class="plat-number">
                    {{ getPlat(item) }}
                  </strong>

                </td>

                <td>

                  <span class="vehicle-badge">

                    <span class="vehicle-symbol">
                      {{
                        getKendaraan(item)
                          .toLowerCase()
                          .includes("motor")
                          ? "M"
                          : "C"
                      }}
                    </span>

                    {{ getKendaraan(item) }}

                  </span>

                </td>

                <td>

                  <div class="date-time">

                    <strong>
                      {{
                        formatTanggal(
                          getWaktuMasuk(item)
                        )
                      }}
                    </strong>

                    <span>
                      {{
                        formatJam(
                          getWaktuMasuk(item)
                        )
                      }}
                    </span>

                  </div>

                </td>

                <td>

                  <div class="date-time">

                    <strong>
                      {{
                        formatTanggal(
                          getWaktuKeluar(item)
                        )
                      }}
                    </strong>

                    <span>
                      {{
                        formatJam(
                          getWaktuKeluar(item)
                        )
                      }}
                    </span>

                  </div>

                </td>

                <td>

                  <strong class="price">
                    {{
                      formatRupiah(
                        getTotal(item)
                      )
                    }}
                  </strong>

                </td>

                <td>

                  <span
                    class="status-badge"
                    :class="
                      isLunas(item)
                        ? 'status-paid'
                        : 'status-unpaid'
                    "
                  >

                    <span>
                      {{
                        isLunas(item)
                          ? "✓"
                          : "!"
                      }}
                    </span>

                    {{
                      getStatus(item)
                    }}

                  </span>

                </td>

              </tr>

            </tbody>

          </table>

        </div>

      </section>

      <div class="footer-info">

        <div>
          <span class="footer-dot"></span>
          Sistem laporan parkir aktif
        </div>

        <span>
          Data otomatis diperbarui setiap 5 detik
        </span>

      </div>

    </main>

  </div>
</template>

<style scoped>
.laporan-page {
  min-height: 100vh;
  width: 100%;
  overflow-x: hidden;
  background:
    radial-gradient(
      circle at 8% 0%,
      rgba(37, 99, 235, 0.13),
      transparent 28%
    ),
    radial-gradient(
      circle at 92% 15%,
      rgba(6, 182, 212, 0.11),
      transparent 30%
    ),
    linear-gradient(
      135deg,
      #dbe3ec 0%,
      #f8fafc 43%,
      #d6dee8 100%
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

.topbar {
  position: sticky;
  top: 0;
  z-index: 50;
  width: 100%;
  min-height: 78px;
  padding: 12px 40px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(248, 250, 252, 0.92);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(148, 163, 184, 0.35);
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
  box-sizing: border-box;
}

.topbar-left {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 18px;
}

.back-button {
  flex-shrink: 0;
  height: 42px;
  min-width: 108px;
  padding: 0 15px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 11px;
  background: linear-gradient(
    135deg,
    #020617,
    #1e293b
  );
  color: #ffffff;
  font-size: 11px;
  font-weight: 850;
  cursor: pointer;
  box-shadow: 0 7px 18px rgba(15, 23, 42, 0.18);
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background 0.2s ease;
}

.back-button:hover {
  transform: translateX(-3px);
  background: linear-gradient(
    135deg,
    #0f172a,
    #334155
  );
  box-shadow: 0 10px 23px rgba(15, 23, 42, 0.24);
}

.back-button:active {
  transform: translateX(-1px);
}

.back-arrow {
  font-size: 18px;
  line-height: 1;
}

.top-divider {
  width: 1px;
  height: 35px;
  flex-shrink: 0;
  background: linear-gradient(
    to bottom,
    transparent,
    #cbd5e1,
    transparent
  );
}

.top-title {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.top-title-icon {
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 13px;
  background: linear-gradient(
    135deg,
    #020617,
    #1e40af 70%,
    #0891b2
  );
  color: #ffffff;
  font-size: 20px;
  font-weight: 900;
  box-shadow: 0 8px 20px rgba(30, 64, 175, 0.22);
}

.top-title-content {
  min-width: 0;
}

.top-title h1 {
  margin: 0;
  color: #0f172a;
  font-size: 18px;
  line-height: 1.2;
  font-weight: 850;
  white-space: nowrap;
}

.top-title p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 9px;
  line-height: 1.3;
  white-space: nowrap;
}

.profile {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  margin-left: 25px;
}

.live-status {
  height: 28px;
  padding: 0 10px;
  display: flex;
  align-items: center;
  gap: 6px;
  border-radius: 20px;
  background: rgba(34, 197, 94, 0.09);
  border: 1px solid rgba(34, 197, 94, 0.18);
  color: #15803d;
  font-size: 8px;
  font-weight: 900;
  letter-spacing: 0.7px;
}

.live-dot,
.update-dot,
.live-table-status span,
.today-live span,
.footer-dot {
  display: inline-block;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
}

.profile-avatar {
  width: 42px;
  height: 42px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(
    135deg,
    #020617,
    #1e3a8a,
    #0891b2
  );
  color: white;
  font-size: 13px;
  font-weight: 900;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.17);
}

.profile-info {
  display: flex;
  flex-direction: column;
}

.profile-info strong {
  color: #1e293b;
  font-size: 11px;
  font-weight: 850;
}

.profile-info span {
  margin-top: 2px;
  color: #64748b;
  font-size: 8px;
}

.main-content {
  width: min(1450px, calc(100% - 70px));
  margin: 0 auto;
  padding: 34px 0 50px;
}

.page-heading {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 30px;
  margin-bottom: 24px;
}

.heading-copy {
  min-width: 0;
}

.eyebrow,
.panel-label {
  display: block;
  color: #475569;
  font-size: 9px;
  font-weight: 900;
  letter-spacing: 1.8px;
}

.page-heading h2 {
  margin: 7px 0 6px;
  color: #020617;
  font-size: 34px;
  line-height: 1.1;
  font-weight: 850;
  letter-spacing: -1px;
}

.page-heading h2 span {
  color: #64748b;
}

.page-heading p {
  margin: 0;
  color: #64748b;
  font-size: 11px;
}

.heading-actions {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 9px;
}

.last-update {
  height: 38px;
  padding: 0 11px;
  display: flex;
  align-items: center;
  gap: 7px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.68);
  border: 1px solid rgba(203, 213, 225, 0.8);
  color: #64748b;
  font-size: 9px;
  font-weight: 750;
}

.refresh-button,
.export-button {
  height: 38px;
  padding: 0 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  border: none;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 850;
  cursor: pointer;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background 0.2s ease;
}

.refresh-button {
  background: #e2e8f0;
  color: #334155;
}

.refresh-button:hover:not(:disabled) {
  background: #cbd5e1;
  transform: translateY(-2px);
}

.refresh-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.export-button {
  background: linear-gradient(
    135deg,
    #020617,
    #1e3a8a
  );
  color: white;
  box-shadow: 0 7px 17px rgba(15, 23, 42, 0.16);
}

.export-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 11px 23px rgba(15, 23, 42, 0.22);
}

.spinning {
  display: inline-block;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error-box {
  margin-bottom: 18px;
  padding: 13px 16px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  border-radius: 12px;
  background: linear-gradient(
    135deg,
    #fff7ed,
    #fefce8
  );
  border: 1px solid #fed7aa;
  color: #7c2d12;
  font-size: 10px;
}

.error-icon {
  width: 27px;
  height: 27px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: #fed7aa;
  color: #9a3412;
  font-weight: 900;
}

.error-box strong {
  font-size: 10px;
}

.error-box p {
  margin: 3px 0 0;
  font-size: 9px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.stat-card {
  position: relative;
  overflow: hidden;
  min-height: 140px;
  padding: 20px;
  display: flex;
  align-items: flex-start;
  gap: 13px;
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.75);
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.1);
  transition:
    transform 0.25s ease,
    box-shadow 0.25s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 17px 34px rgba(15, 23, 42, 0.15);
}

.card-dark {
  background: linear-gradient(
    135deg,
    #020617 0%,
    #0f172a 50%,
    #1e3a8a 135%
  );
}

.card-blue {
  background: linear-gradient(
    135deg,
    #0f172a 0%,
    #1e293b 42%,
    #0369a1 130%
  );
}

.card-white {
  background: linear-gradient(
    135deg,
    #ffffff 0%,
    #f8fafc 48%,
    #dbeafe 130%
  );
}

.card-light {
  background: linear-gradient(
    135deg,
    #f8fafc 0%,
    #e2e8f0 55%,
    #bae6fd 150%
  );
}

.card-glow {
  position: absolute;
  width: 120px;
  height: 120px;
  right: -45px;
  bottom: -55px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  filter: blur(2px);
}

.stat-icon {
  width: 47px;
  height: 47px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 13px;
  font-size: 15px;
  font-weight: 900;
}

.icon-dark,
.icon-blue {
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.12);
}

.icon-money {
  background: linear-gradient(
    135deg,
    #dbeafe,
    #bfdbfe
  );
  color: #1e3a8a;
}

.icon-light {
  background: rgba(255, 255, 255, 0.7);
  color: #1e3a8a;
  border: 1px solid rgba(255, 255, 255, 0.7);
}

.stat-content {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.stat-content span {
  color: #64748b;
  font-size: 8px;
  font-weight: 900;
  letter-spacing: 0.6px;
}

.stat-content strong {
  margin-top: 7px;
  color: #0f172a;
  font-size: 22px;
  line-height: 1.2;
  font-weight: 850;
  word-break: break-word;
}

.stat-content small {
  margin-top: 6px;
  color: #64748b;
  font-size: 8px;
}

.card-dark .stat-content span,
.card-blue .stat-content span {
  color: #94a3b8;
}

.card-dark .stat-content strong,
.card-blue .stat-content strong {
  color: #ffffff;
}

.card-dark .stat-content small,
.card-blue .stat-content small {
  color: #94a3b8;
}

.analytics-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 330px;
  gap: 20px;
  margin-bottom: 20px;
}

.chart-panel {
  padding: 25px;
  border-radius: 20px;
  background: linear-gradient(
    135deg,
    rgba(255, 255, 255, 0.98),
    rgba(241, 245, 249, 0.98)
  );
  border: 1px solid rgba(255, 255, 255, 0.9);
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
}

.panel-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 15px;
}

.panel-header h3,
.today-panel h3,
.table-heading h3 {
  margin: 6px 0 4px;
  color: #172033;
  font-size: 18px;
  font-weight: 850;
}

.panel-header p,
.table-heading p {
  margin: 0;
  color: #64748b;
  font-size: 9px;
}

.chart-badge {
  flex-shrink: 0;
  padding: 8px 11px;
  display: flex;
  align-items: center;
  gap: 6px;
  border-radius: 9px;
  background: linear-gradient(
    135deg,
    #020617,
    #1e3a8a
  );
  color: white;
  font-size: 8px;
  font-weight: 900;
}

.chart-badge span {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #22c55e;
}

.chart-summary {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-top: 19px;
}

.summary-box {
  padding: 12px 14px;
  border-radius: 11px;
  background: rgba(226, 232, 240, 0.55);
  border: 1px solid rgba(203, 213, 225, 0.7);
}

.summary-box span {
  display: block;
  color: #64748b;
  font-size: 8px;
  font-weight: 750;
}

.summary-box strong {
  display: block;
  margin-top: 4px;
  color: #0f172a;
  font-size: 13px;
  font-weight: 850;
}

.chart-area {
  height: 245px;
  margin-top: 18px;
  padding: 5px 12px 0;
  display: flex;
  align-items: flex-end;
  gap: 18px;
  border-bottom: 1px solid #cbd5e1;
}

.chart-column {
  flex: 1;
  height: 100%;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
}

.chart-value {
  min-height: 18px;
  margin-bottom: 6px;
  color: #475569;
  font-size: 7px;
  font-weight: 850;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

.bar-wrapper {
  width: min(48px, 75%);
  height: 165px;
  display: flex;
  align-items: flex-end;
}

.chart-bar {
  position: relative;
  width: 100%;
  min-height: 5px;
  border-radius: 10px 10px 3px 3px;
  background: linear-gradient(
    to top,
    #020617,
    #1e40af 70%,
    #06b6d4
  );
  box-shadow: 0 9px 20px rgba(15, 23, 42, 0.2);
  transition: height 0.6s ease;
}

.chart-bar::after {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 6px;
  border-radius: 10px 10px 0 0;
  background: rgba(255, 255, 255, 0.45);
}

.chart-bar span {
  position: absolute;
  top: 6px;
  left: 50%;
  width: 6px;
  height: 6px;
  transform: translateX(-50%);
  border-radius: 50%;
  background: white;
}

.chart-label {
  margin-top: 8px;
  color: #475569;
  font-size: 9px;
  font-weight: 800;
}

.today-panel {
  position: relative;
  overflow: hidden;
  padding: 25px;
  border-radius: 20px;
  background: linear-gradient(
    145deg,
    #020617 0%,
    #0f172a 50%,
    #164e63 150%
  );
  color: white;
  box-shadow: 0 13px 30px rgba(15, 23, 42, 0.17);
}

.today-grid-glow {
  position: absolute;
  width: 180px;
  height: 180px;
  right: -80px;
  top: -80px;
  border-radius: 50%;
  background: rgba(6, 182, 212, 0.12);
  filter: blur(12px);
}

.today-top {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.today-panel .panel-label {
  color: #64748b;
}

.today-panel h3 {
  color: white;
}

.today-date {
  margin: 0;
  color: #64748b;
  font-size: 9px;
}

.today-live {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 7px 9px;
  border-radius: 20px;
  background: rgba(34, 197, 94, 0.1);
  color: #86efac;
  font-size: 8px;
  font-weight: 900;
}

.today-income {
  position: relative;
  margin-top: 25px;
  display: flex;
  flex-direction: column;
}

.today-income span {
  color: #64748b;
  font-size: 9px;
}

.today-income strong {
  margin-top: 5px;
  color: white;
  font-size: 24px;
  font-weight: 850;
}

.today-divider {
  height: 1px;
  margin: 19px 0;
  background: rgba(255, 255, 255, 0.1);
}

.mini-stat {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 12px;
}

.mini-icon {
  width: 34px;
  height: 34px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
  background: rgba(255, 255, 255, 0.08);
  color: #bae6fd;
  font-size: 10px;
  font-weight: 850;
  border: 1px solid rgba(255, 255, 255, 0.07);
}

.mini-stat span {
  color: #64748b;
  font-size: 8px;
}

.mini-stat strong {
  display: block;
  margin-top: 2px;
  color: white;
  font-size: 13px;
}

.table-panel {
  overflow: hidden;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.96);
  border: 1px solid rgba(255, 255, 255, 0.9);
  box-shadow: 0 13px 30px rgba(15, 23, 42, 0.08);
}

.table-heading {
  padding: 24px 25px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.table-heading-right {
  display: flex;
  align-items: center;
  gap: 9px;
}

.live-table-status {
  height: 29px;
  padding: 0 9px;
  display: flex;
  align-items: center;
  gap: 6px;
  border-radius: 20px;
  background: #f0fdf4;
  border: 1px solid #dcfce7;
  color: #15803d;
  font-size: 8px;
  font-weight: 900;
}

.live-table-status span {
  width: 6px;
  height: 6px;
  box-shadow: none;
}

.table-count {
  padding: 8px 13px;
  border-radius: 20px;
  background: linear-gradient(
    135deg,
    #020617,
    #1e3a8a
  );
  color: white;
  font-size: 9px;
  font-weight: 850;
}

.filters {
  display: grid;
  grid-template-columns:
    minmax(250px, 1fr)
    180px
    180px
    180px;
  gap: 10px;
  padding: 0 25px 20px;
}

.search-box {
  height: 42px;
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 0 13px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #f8fafc;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.search-box:focus-within {
  border-color: #60a5fa;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-icon {
  color: #475569;
  font-size: 19px;
}

.search-box input {
  width: 100%;
  border: none;
  outline: none;
  background: transparent;
  color: #334155;
  font-size: 10px;
}

.search-box input::placeholder {
  color: #94a3b8;
}

.filters select {
  height: 42px;
  padding: 0 12px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #f8fafc;
  color: #475569;
  outline: none;
  font-size: 10px;
  font-weight: 750;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.filters select:focus {
  border-color: #60a5fa;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.table-wrapper {
  width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
}

table {
  width: 100%;
  min-width: 850px;
  border-collapse: collapse;
}

thead {
  background: linear-gradient(
    90deg,
    #020617,
    #172554 55%,
    #164e63
  );
}

th {
  padding: 14px 17px;
  color: #e2e8f0;
  text-align: left;
  font-size: 8px;
  font-weight: 900;
  letter-spacing: 0.6px;
  white-space: nowrap;
}

td {
  padding: 14px 17px;
  border-top: 1px solid #e2e8f0;
  color: #64748b;
  font-size: 9px;
  white-space: nowrap;
}

tbody tr {
  transition: background 0.15s ease;
}

tbody tr:hover {
  background: linear-gradient(
    90deg,
    #f8fafc,
    #eff6ff
  );
}

.ticket-code {
  display: inline-block;
  padding: 6px 9px;
  border-radius: 7px;
  background: #e2e8f0;
  color: #334155;
  font-size: 8px;
  font-weight: 900;
}

.plat-number {
  color: #1e293b;
  font-size: 9px;
  font-weight: 850;
}

.vehicle-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 9px;
  border-radius: 8px;
  background: #f1f5f9;
  color: #475569;
  font-weight: 750;
}

.vehicle-symbol {
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 5px;
  background: #cbd5e1;
  color: #334155;
  font-size: 8px;
  font-weight: 900;
}

.date-time {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.date-time strong {
  color: #475569;
  font-size: 9px;
}

.date-time span {
  color: #94a3b8;
  font-size: 8px;
}

.price {
  color: #0f172a;
  font-size: 9px;
  font-weight: 850;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 9px;
  border-radius: 20px;
  font-size: 8px;
  font-weight: 900;
}

.status-badge > span {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 14px;
  height: 14px;
  border-radius: 50%;
}

.status-paid {
  background: #dcfce7;
  color: #166534;
}

.status-paid > span {
  background: #bbf7d0;
}

.status-unpaid {
  background: #fef3c7;
  color: #92400e;
}

.status-unpaid > span {
  background: #fde68a;
}

.empty-cell {
  height: 240px;
  text-align: center;
}

.loading-box,
.empty-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 9px;
}

.loading-box span,
.empty-box span {
  color: #94a3b8;
  font-size: 9px;
}

.loading-circle {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: 3px solid #e2e8f0;
  border-top-color: #2563eb;
  animation: spin 0.8s linear infinite;
}

.empty-icon {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: #f1f5f9;
  color: #94a3b8;
  font-size: 20px;
}

.empty-box strong {
  color: #475569;
  font-size: 11px;
}

.footer-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 17px 4px 0;
  color: #64748b;
  font-size: 9px;
}

.footer-dot {
  width: 7px;
  height: 7px;
  margin-right: 6px;
  box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.08);
}

@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .analytics-grid {
    grid-template-columns: 1fr;
  }

  .filters {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 900px) {
  .topbar {
    padding: 12px 22px;
  }

  .profile-info {
    display: none;
  }

  .main-content {
    width: calc(100% - 40px);
  }

  .page-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .heading-actions {
    width: 100%;
    flex-wrap: wrap;
  }

  .last-update {
    margin-right: auto;
  }
}

@media (max-width: 650px) {
  .topbar {
    min-height: 70px;
    padding: 10px 15px;
  }

  .topbar-left {
    gap: 10px;
  }

  .top-divider {
    display: none;
  }

  .back-button {
    min-width: 42px;
    width: 42px;
    height: 40px;
    padding: 0;
  }

  .back-text {
    display: none;
  }

  .back-arrow {
    font-size: 19px;
  }

  .top-title {
    gap: 9px;
  }

  .top-title-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    font-size: 17px;
  }

  .top-title h1 {
    font-size: 14px;
  }

  .top-title p {
    display: none;
  }

  .profile {
    margin-left: 8px;
  }

  .live-status {
    display: none;
  }

  .profile-avatar {
    width: 37px;
    height: 37px;
  }

  .main-content {
    width: calc(100% - 28px);
    padding-top: 25px;
  }

  .page-heading h2 {
    font-size: 29px;
  }

  .heading-actions {
    gap: 7px;
  }

  .last-update {
    width: 100%;
    margin-right: 0;
    justify-content: center;
  }

  .refresh-button,
  .export-button {
    flex: 1;
  }

  .stats-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .stat-card {
    min-height: 125px;
    padding: 17px;
  }

  .chart-panel,
  .today-panel {
    padding: 20px;
  }

  .chart-summary {
    grid-template-columns: 1fr;
  }

  .table-heading {
    align-items: flex-start;
    flex-direction: column;
    padding: 20px 18px 16px;
  }

  .table-heading-right {
    width: 100%;
    justify-content: space-between;
  }

  .filters {
    grid-template-columns: 1fr;
    padding: 0 18px 18px;
  }

  .footer-info {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
}

@media (max-width: 420px) {
  .top-title h1 {
    font-size: 13px;
  }

  .top-title-icon {
    display: none;
  }

  .page-heading h2 {
    font-size: 26px;
  }

  .heading-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
  }

  .last-update {
    grid-column: 1 / -1;
  }

  .chart-area {
    gap: 7px;
    padding-left: 4px;
    padding-right: 4px;
  }

  .chart-value {
    font-size: 6px;
  }

  .bar-wrapper {
    width: 70%;
  }

  .chart-label {
    font-size: 8px;
  }
}
</style>
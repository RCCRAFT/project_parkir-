<template>

  <div
    class="min-h-screen flex items-center justify-center relative overflow-hidden bg-slate-950 px-4 py-8"
  >
    <!-- Background -->
    <div class="absolute inset-0 bg-gradient-to-br from-emerald-950 via-slate-950 to-blue-950"></div>

    <!-- Glow -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl"></div>

    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/20"></div>

    <!-- Main Card -->
    <div
      class="relative z-10 w-full max-w-[460px] min-h-[680px] rounded-[32px] border border-white/10 bg-slate-900/75 backdrop-blur-2xl shadow-[0_25px_80px_rgba(0,0,0,0.55)] flex flex-col px-8 sm:px-10 py-10"
    >
      <!-- Header -->
      <div class="text-center">
        <div
          class="mx-auto mb-5 w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-400 to-cyan-500 flex items-center justify-center shadow-lg shadow-emerald-500/20"
        >
          <span class="text-3xl">🚗</span>
        </div>

        <p class="text-emerald-400 text-xs font-bold tracking-[0.3em] uppercase">
          Parking System
        </p>

        <h1 class="mt-2 text-white text-3xl sm:text-4xl font-black tracking-tight">
          PARKIR

          <span
            class="block bg-gradient-to-r from-emerald-400 via-cyan-400 to-blue-400 bg-clip-text text-transparent"
          >
            PLAZA ANDALAS
          </span>
        </h1>

        <p class="mt-3 text-slate-400 text-sm">
          Sistem parkir modern & terintegrasi
        </p>
      </div>

      <!-- Divider -->
      <div
        class="w-full h-px bg-gradient-to-r from-transparent via-white/15 to-transparent my-9"
      ></div>

      <!-- Print Ticket Section -->
      <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-6">
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-400/20 flex items-center justify-center"
          >
            <span class="text-xl">🎫</span>
          </div>

          <div>
            <h2 class="text-white font-bold text-base">
              Ambil Tiket Parkir
            </h2>

            <p class="text-slate-400 text-xs mt-1">
              Khusus non member, tekan tombol untuk cetak tiket
            </p>
          </div>
        </div>

        <!--
          Tiket non member HANYA dibuat lewat klik tombol ini.
          Enter / Space dari keyboard atau scanner diblokir,
          jadi scan QR tidak bisa memicu cetak tiket.
        -->
        <button
          type="button"
          tabindex="-1"
          :disabled="printing"
          @click="printTicket"
          @keydown.enter.prevent
          @keydown.space.prevent
          @keyup.space.prevent
          class="mt-5 w-full h-12 rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 text-white font-extrabold tracking-wider shadow-lg shadow-emerald-500/20 hover:from-emerald-400 hover:to-cyan-400 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed"
        >
          {{ printing ? "MENCETAK..." : "PRINT TIKET" }}
        </button>
      </div>

      <!-- Member Section -->
      <div class="mt-5 rounded-2xl border border-white/10 bg-white/[0.04] p-6">
        <div class="flex items-center gap-4">
          <div
            class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-400/20 flex items-center justify-center"
          >
            <span class="text-xl">💳</span>
          </div>

          <div>
            <h2 class="text-white font-bold text-base">
              Member Parking
            </h2>

            <p class="text-slate-400 text-xs mt-1">
              Scan QR kartu atau ketik kode member
            </p>
          </div>
        </div>

        <!-- Scanner -->
        <div class="relative mt-5">
          <input
            ref="scanInput"
            v-model="scanCard"
            autofocus
            autocomplete="off"
            spellcheck="false"
            type="text"
            placeholder="SCAN QR / KETIK KODE MEMBER"
            :disabled="checkingMember"
            class="w-full h-14 rounded-xl bg-slate-950/80 border border-white/10 text-white text-center text-sm font-bold tracking-widest placeholder:text-slate-600 outline-none focus:border-emerald-400/60 focus:ring-2 focus:ring-emerald-400/10 transition disabled:opacity-50"
            @keydown.enter.prevent="openGate"
          />

          <div
            class="absolute bottom-0 left-1/2 -translate-x-1/2 w-24 h-[2px] bg-gradient-to-r from-transparent via-emerald-400 to-transparent"
          ></div>
        </div>

        <p class="mt-3 text-center text-[11px] text-slate-500">
          Contoh kode: MBR-ABC123. Ketik lalu tekan Enter.
        </p>

        <!-- CHECKING -->
        <div
          v-if="checkingMember"
          class="mt-4 rounded-xl border border-blue-400/20 bg-blue-500/10 p-4 flex items-center gap-3"
        >
          <div
            class="w-5 h-5 rounded-full border-2 border-blue-300/30 border-t-blue-400 animate-spin"
          ></div>

          <div>
            <p class="text-blue-300 text-sm font-bold">
              Memeriksa member...
            </p>

            <p class="text-slate-400 text-xs mt-1">
              Mohon tunggu sebentar
            </p>
          </div>
        </div>

        <!-- SUCCESS -->
        <div
          v-if="scanStatus === 'success' && !checkingMember"
          class="mt-4 rounded-xl border border-emerald-400/20 bg-emerald-500/10 p-4"
        >
          <div class="flex items-start gap-3">
            <div
              class="w-8 h-8 rounded-lg bg-emerald-400/15 text-emerald-300 flex items-center justify-center font-bold"
            >
              ✓
            </div>

            <div>
              <p class="text-emerald-300 text-sm font-bold">
                Akses diterima
              </p>

              <p class="text-slate-400 text-xs mt-1">
                {{ statusMessage }}
              </p>
            </div>
          </div>
        </div>

        <!-- ALREADY INSIDE -->
        <div
          v-if="scanStatus === 'already_in' && !checkingMember"
          class="mt-4 rounded-xl border border-amber-400/20 bg-amber-500/10 p-4"
        >
          <div class="flex items-start gap-3">
            <div
              class="w-8 h-8 rounded-lg bg-amber-400/15 text-amber-300 flex items-center justify-center font-bold"
            >
              !
            </div>

            <div>
              <p class="text-amber-300 text-sm font-bold">
                Member sudah masuk
              </p>

              <p class="text-slate-400 text-xs mt-1">
                {{ statusMessage }}
              </p>
            </div>
          </div>
        </div>

        <!-- ERROR -->
        <div
          v-if="scanStatus === 'error' && !checkingMember"
          class="mt-4 rounded-xl border border-red-400/20 bg-red-500/10 p-4"
        >
          <div class="flex items-start gap-3">
            <div
              class="w-8 h-8 rounded-lg bg-red-400/15 text-red-300 flex items-center justify-center font-bold"
            >
              ×
            </div>

            <div>
              <p class="text-red-300 text-sm font-bold">
                Gagal memproses
              </p>

              <p class="text-slate-400 text-xs mt-1">
                {{ statusMessage }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-auto pt-8 text-center">
        <div
          class="flex items-center justify-center gap-2 text-[10px] text-slate-500 uppercase tracking-[0.2em]"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>

          System Online

          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
        </div>

        <p class="text-slate-600 text-[10px] mt-2">
          Plaza Andalas Parking Management
        </p>
      </div>
    </div>

    <!--
      POPUP GERBANG
      Di-teleport ke body supaya tidak terpengaruh backdrop-blur kartu.
    -->
    <Teleport to="body">
      <Transition name="popup">
        <div
          v-if="gatePopup"
          class="fixed inset-0 z-[100] flex items-center justify-center px-4 bg-black/70 backdrop-blur-sm"
          @click="closeGatePopup"
        >
          <div
            class="popup-card w-full max-w-[420px] rounded-[28px] border bg-slate-900 px-8 py-9 text-center shadow-[0_25px_80px_rgba(0,0,0,0.6)]"
            :class="
              gatePopup.type === 'open'
                ? 'border-emerald-400/30'
                : 'border-red-400/30'
            "
            @click.stop
          >

            <!-- ILUSTRASI PALANG -->
            <div class="gate-scene">

              <div class="gate-post">
                <span
                  class="gate-light"
                  :class="
                    gatePopup.type === 'open'
                      ? 'light-open'
                      : 'light-blocked'
                  "
                ></span>
              </div>

              <div
                class="gate-arm"
                :class="{
                  'is-open': gatePopup.type === 'open' && gateOpen,
                  'is-blocked': gatePopup.type === 'blocked'
                }"
              ></div>

              <div class="gate-road"></div>

            </div>

            <!-- TEKS -->
            <p
              class="mt-6 text-xs font-bold tracking-[0.3em] uppercase"
              :class="
                gatePopup.type === 'open'
                  ? 'text-emerald-400'
                  : 'text-red-400'
              "
            >
              {{
                gatePopup.type === 'open'
                  ? 'Palang terbuka'
                  : 'Akses ditolak'
              }}
            </p>

            <h3 class="mt-2 text-white text-2xl font-black tracking-tight">
              {{ gatePopup.title }}
            </h3>

            <p class="mt-2 text-slate-300 text-sm">
              {{ gatePopup.message }}
            </p>

            <p
              v-if="gatePopup.detail"
              class="mt-4 inline-block rounded-lg bg-white/5 border border-white/10 px-4 py-2 text-slate-200 text-sm font-bold tracking-widest"
            >
              {{ gatePopup.detail }}
            </p>

            <button
              type="button"
              tabindex="-1"
              class="mt-7 w-full h-11 rounded-xl text-sm font-bold text-white transition"
              :class="
                gatePopup.type === 'open'
                  ? 'bg-emerald-500 hover:bg-emerald-400'
                  : 'bg-red-500 hover:bg-red-400'
              "
              @click="closeGatePopup"
            >
              Tutup
            </button>

          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<script setup lang="ts">
import {
  ref,
  nextTick,
  onMounted,
  onBeforeUnmount,
} from "vue";

import jsPDF from "jspdf";
import QRCode from "qrcode";

const { $api } = useNuxtApp() as any;

// ======================================================
// STATE
// ======================================================

const scanCard = ref("");

const tiket = ref<any>(null);

const checkingMember = ref(false);

const printing = ref(false);

const scanStatus = ref<
  "idle" | "success" | "already_in" | "error"
>("idle");

const statusMessage = ref("");

// ======================================================
// STATE POPUP GERBANG
// ======================================================

type GatePopup = {
  type: "open" | "blocked";
  title: string;
  message: string;
  detail?: string;
};

const gatePopup = ref<GatePopup | null>(null);

// true = palang terangkat (dipakai untuk animasi)
const gateOpen = ref(false);

let popupTimer: ReturnType<typeof setTimeout> | null = null;
let openTimer: ReturnType<typeof setTimeout> | null = null;

// ======================================================
// REF INPUT
// ======================================================

const scanInput = ref<HTMLInputElement | null>(null);

// ======================================================
// RESET STATUS
// ======================================================

const resetScanStatus = () => {
  scanStatus.value = "idle";

  statusMessage.value = "";
};

// ======================================================
// FOCUS ULANG SCANNER
// ======================================================

const focusScanner = async () => {
  await nextTick();

  scanInput.value?.focus();
};

// ======================================================
// POPUP GERBANG (TAMPIL / TUTUP)
// ======================================================

const closeGatePopup = async () => {
  if (popupTimer) {
    clearTimeout(popupTimer);
    popupTimer = null;
  }

  if (openTimer) {
    clearTimeout(openTimer);
    openTimer = null;
  }

  gatePopup.value = null;
  gateOpen.value = false;

  await focusScanner();
};

const showGatePopup = (popup: GatePopup) => {
  if (popupTimer) {
    clearTimeout(popupTimer);
  }

  if (openTimer) {
    clearTimeout(openTimer);
  }

  // Mulai dari palang tertutup, lalu terangkat setelah popup muncul
  gateOpen.value = false;
  gatePopup.value = popup;

  if (popup.type === "open") {
    openTimer = setTimeout(() => {
      gateOpen.value = true;
    }, 350);
  }

  // Tutup otomatis
  popupTimer = setTimeout(() => {
    closeGatePopup();
  }, 4500);
};

// ======================================================
// PENJAGA FOKUS SCANNER
// ======================================================
//
// Scanner USB mengetik isi QR lalu menekan Enter.
// Kalau fokus tertinggal di tombol (mis. setelah klik
// PRINT TIKET), penjaga ini memindahkan fokus ke kolom
// scan begitu ada tombol keyboard ditekan.
//
const handleGlobalKeydown = (event: KeyboardEvent) => {
  const target = event.target as HTMLElement | null;

  if (target === scanInput.value) {
    return;
  }

  const tagName = target?.tagName?.toLowerCase();

  if (
    tagName !== "input" &&
    tagName !== "textarea" &&
    tagName !== "select"
  ) {
    // Sinkron (tanpa await) supaya karakter pertama
    // dari scanner ikut masuk ke kolom scan.
    scanInput.value?.focus();
  }
};

onMounted(async () => {
  window.addEventListener("keydown", handleGlobalKeydown);

  await focusScanner();
});

onBeforeUnmount(() => {
  window.removeEventListener("keydown", handleGlobalKeydown);

  if (popupTimer) {
    clearTimeout(popupTimer);
  }

  if (openTimer) {
    clearTimeout(openTimer);
  }
});

// ======================================================
// BUAT TIKET
// ======================================================

const buatTiket = async () => {
  try {
    const response = await $api.post("/tiket");

    tiket.value = response.data.data;

    return tiket.value;

  } catch (error: any) {
    console.error("Gagal membuat tiket:", error);

    const status = error?.response?.status;

    const pesan =
      error?.response?.data?.message ||
      error?.message ||
      "Tidak ada pesan";

    alert(
      "Gagal membuat tiket\n\n" +
      `Status: ${status ?? "tidak ada respons"}\n` +
      pesan
    );

    return null;
  }
};

// ======================================================
// PRINT TIKET (NON MEMBER)
// ======================================================
//
// Hanya dipanggil dari klik tombol PRINT TIKET.
//
const printTicket = async () => {
  // Cegah tiket ganda kalau tombol tertekan berulang.
  if (printing.value) {
    return;
  }

  printing.value = true;

  try {
    const data = await buatTiket();

    if (!data) {
      return;
    }

    const nomorTiket = data.kode_tiket;

    const qrData = `
PARKIR PLAZA ANDALAS

Kode Tiket : ${nomorTiket}
Waktu Masuk : ${data.waktu_masuk}
Status : ${data.status}
`;

    const qrImage = await QRCode.toDataURL(qrData);

    const pdf = new jsPDF({
      orientation: "portrait",
      unit: "mm",
      format: [80, 140],
    });

    pdf.setFontSize(12);

    pdf.text("PARKIR", 40, 12, { align: "center" });

    pdf.text("PLAZA ANDALAS", 40, 20, { align: "center" });

    pdf.setFontSize(7);

    pdf.text(
      "Jl. Dr. KRT Radjiman Widyodiningrat",
      40,
      30,
      { align: "center" }
    );

    pdf.text("Jakarta Timur", 40, 35, { align: "center" });

    pdf.addImage(qrImage, "PNG", 25, 45, 30, 30);

    pdf.setFontSize(16);

    pdf.text(nomorTiket, 40, 85, { align: "center" });

    pdf.setFontSize(8);

    pdf.text(
      `
Informasi :

1. Non Member
Parkir Rp. 3.000

2. Member
Parkir Rp. 150.000 / bulan

Terima Kasih
`,
      40,
      100,
      { align: "center" }
    );

    pdf.save(`Tiket-${nomorTiket}.pdf`);

    // Tiket berhasil dibuat: tampilkan popup palang terbuka
    showGatePopup({
      type: "open",
      title: "Silakan Masuk",
      message: "Tiket berhasil dicetak. Palang pintu terbuka.",
      detail: nomorTiket,
    });

  } finally {
    printing.value = false;

    // Kembalikan fokus ke kolom scan.
    await focusScanner();
  }
};

// ======================================================
// MEMBER MASUK (SCAN QR ATAU KETIK KODE)
// ======================================================
//
// Dua cara masuk untuk member:
// 1. Ketik kode member, mis. MBR-ABC123 -> dikirim sebagai
//    kode_member (huruf kecil otomatis jadi besar).
// 2. Scan QR kartu (berisi token acak) -> dikirim sebagai
//    token, tanpa diubah huruf besar/kecilnya.
//
// Member hanya boleh masuk SATU KALI sampai dia keluar.
// "already_in" hanya kalau backend mengirim already_in: true
// (baik di respons sukses maupun di respons error 409).
//
const openGate = async () => {
  const hasilScan = scanCard.value.trim();

  if (!hasilScan) {
    return;
  }

  // Jangan proses scan baru ketika scan sebelumnya
  // masih dicek.
  if (checkingMember.value) {
    return;
  }

  const cocokKode = hasilScan.match(/MBR-[A-Z0-9]{6}/i);

  const payload = cocokKode
    ? { kode_member: cocokKode[0].toUpperCase() }
    : { token: hasilScan };

  const label = cocokKode
    ? cocokKode[0].toUpperCase()
    : "member";

  checkingMember.value = true;

  scanStatus.value = "idle";

  statusMessage.value = "";

  // Selalu kosongkan kolom scan supaya scan berikutnya
  // tidak menempel di belakang kode lama.
  scanCard.value = "";

  // Tampilkan popup "sudah ada kendaraan yang masuk"
  const tampilkanSudahMasuk = (pesan?: string) => {
    scanStatus.value = "already_in";

    statusMessage.value =
      pesan || `Member ${label} sudah masuk.`;

    showGatePopup({
      type: "blocked",
      title: "Sudah ada kendaraan yang masuk",
      message:
        "Kartu member hanya bisa dipakai satu kali sampai kendaraan keluar.",
      detail: label,
    });
  };

  try {
    /*
     * Endpoint:
     * POST /api/member/check
     */
    const response = await $api.post("/member/check", payload);

    const result = response?.data ?? {};

    const data = result?.data ?? {};

    if (result?.already_in === true) {
      tampilkanSudahMasuk(result?.message);

      return;
    }

    scanStatus.value = "success";

    statusMessage.value =
      result?.message ||
      `Member ${data?.nama_member || label} terverifikasi. Pintu terbuka.`;

    // Berhasil masuk: tampilkan popup palang terbuka
    showGatePopup({
      type: "open",
      title: "Silakan Masuk",
      message: data?.nama_member
        ? `Selamat datang, ${data.nama_member}.`
        : "Member terverifikasi. Palang pintu terbuka.",
      detail: data?.kode_member || label,
    });

  } catch (error: any) {
    console.error("Gagal memeriksa member:", error);

    const responseData = error?.response?.data ?? {};

    const errorData = responseData?.data ?? {};

    if (Boolean(responseData?.already_in)) {
      tampilkanSudahMasuk(responseData?.message);

      return;
    }

    scanStatus.value = "error";

    statusMessage.value =
      responseData?.message ||
      errorData?.message ||
      "Kode member tidak dapat diverifikasi.";

  } finally {
    checkingMember.value = false;

    await focusScanner();
  }
};
</script>

<style scoped>
/* ================================
   ILUSTRASI PALANG
================================ */

.gate-scene {
  position: relative;
  width: 280px;
  height: 130px;
  margin: 0 auto;
}

.gate-post {
  position: absolute;
  left: 16px;
  bottom: 12px;
  width: 46px;
  height: 84px;

  border-radius: 10px;
  background: #1e293b;
  border: 1px solid rgba(255, 255, 255, 0.14);
}

.gate-light {
  position: absolute;
  top: 12px;
  left: 50%;
  transform: translateX(-50%);

  width: 12px;
  height: 12px;
  border-radius: 50%;
  transition: background 0.4s, box-shadow 0.4s;
}

.light-open {
  background: #34d399;
  box-shadow: 0 0 14px #34d399;
}

.light-blocked {
  background: #f87171;
  box-shadow: 0 0 14px #f87171;
}

.gate-arm {
  position: absolute;
  left: 46px;
  bottom: 62px;
  width: 210px;
  height: 12px;

  border-radius: 6px;
  transform-origin: left center;
  transition: transform 0.9s cubic-bezier(0.22, 1, 0.36, 1);

  background: repeating-linear-gradient(
    90deg,
    #ef4444 0,
    #ef4444 22px,
    #f8fafc 22px,
    #f8fafc 44px
  );
}

/* palang terangkat */
.gate-arm.is-open {
  transform: rotate(-72deg);
}

/* palang tertahan dan bergetar */
.gate-arm.is-blocked {
  box-shadow: 0 0 18px rgba(248, 113, 113, 0.55);
  animation: gate-shake 0.45s ease 2;
}

.gate-road {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 4px;
  height: 3px;

  border-radius: 2px;
  background: repeating-linear-gradient(
    90deg,
    rgba(255, 255, 255, 0.25) 0,
    rgba(255, 255, 255, 0.25) 14px,
    transparent 14px,
    transparent 26px
  );
}

@keyframes gate-shake {
  0%, 100% { transform: rotate(0deg); }
  25%      { transform: rotate(-4deg); }
  75%      { transform: rotate(3deg); }
}

/* ================================
   ANIMASI MUNCUL POPUP
================================ */

.popup-enter-active,
.popup-leave-active {
  transition: opacity 0.25s ease;
}

.popup-enter-active .popup-card,
.popup-leave-active .popup-card {
  transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.popup-enter-from,
.popup-leave-to {
  opacity: 0;
}

.popup-enter-from .popup-card,
.popup-leave-to .popup-card {
  transform: translateY(18px) scale(0.96);
}

@media (max-width: 380px) {
  .gate-scene {
    transform: scale(0.85);
  }
}

@media (prefers-reduced-motion: reduce) {
  .gate-arm,
  .popup-enter-active,
  .popup-leave-active,
  .popup-enter-active .popup-card,
  .popup-leave-active .popup-card {
    transition: none !important;
  }

  .gate-arm.is-blocked {
    animation: none;
  }
}
</style>
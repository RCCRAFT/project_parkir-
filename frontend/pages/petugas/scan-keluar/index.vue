<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from "vue"
import jsPDF from "jspdf"
import QRCode from "qrcode"

definePageMeta({
  layout: false
})

/* ---------------------------------------------------------------
   CONFIG API
--------------------------------------------------------------- */

const API_BASE = "http://127.0.0.1:8000/api"

/* ---------------------------------------------------------------
   STATE
--------------------------------------------------------------- */

const scannerInput = ref(null)

const kodeScanner = ref("")
const tiketData = ref(null)

const jenisKendaraan = ref("motor")
const nomorPlat = ref("")
const uangDibayar = ref(0)

const loadingCari = ref(false)
const loadingBayar = ref(false)

const memberData = ref(null)
const jenisKendaraanKeluar = ref("motor")
const loadingKeluarMember = ref(false)

/* ---------------------------------------------------------------
   TARIF
--------------------------------------------------------------- */

const tarif = {
  motor: 3000,
  mobil: 5000
}

/* ---------------------------------------------------------------
   COMPUTED
--------------------------------------------------------------- */

const tarifParkir = computed(() => {
  return tarif[jenisKendaraan.value] || 3000
})

const totalBayar = computed(() => {
  return tarifParkir.value
})

const kembalian = computed(() => {
  const uang = Number(uangDibayar.value || 0)
  const total = Number(totalBayar.value || 0)

  return Math.max(uang - total, 0)
})

const bisaBayar = computed(() => {
  return Number(uangDibayar.value || 0) >= Number(totalBayar.value || 0)
})

// Nomor plat wajib diisi
const platTerisi = computed(() => {
  return nomorPlat.value.trim().length > 0
})

const tiketSudahKeluar = computed(() => {
  if (!tiketData.value) {
    return false
  }

  return (
    String(tiketData.value.status || "").toLowerCase() === "keluar" ||
    !!tiketData.value.waktu_keluar
  )
})

// Nomor polisi diambil dari input petugas (dipakai untuk konfirmasi, API, dan invoice)
const nomorPolisi = computed(() => {
  const plat = nomorPlat.value.trim().toUpperCase()

  return plat || "-"
})

const jenisKendaraanLabel = computed(() => {
  const jenis =
    tiketData.value?.jenis_kendaraan ||
    tiketData.value?.jenis ||
    jenisKendaraan.value

  return String(jenis).toLowerCase() === "mobil"
    ? "Mobil"
    : "Motor"
})

const durasiParkir = computed(() => {
  if (!tiketData.value?.waktu_masuk) {
    return "-"
  }

  const waktuMasuk = new Date(tiketData.value.waktu_masuk)

  const waktuAkhir = tiketData.value.waktu_keluar
    ? new Date(tiketData.value.waktu_keluar)
    : new Date()

  if (
    Number.isNaN(waktuMasuk.getTime()) ||
    Number.isNaN(waktuAkhir.getTime())
  ) {
    return "-"
  }

  const selisihMs = Math.max(
    waktuAkhir.getTime() - waktuMasuk.getTime(),
    0
  )

  const totalMenit = Math.floor(selisihMs / 1000 / 60)

  const jam = Math.floor(totalMenit / 60)
  const menit = totalMenit % 60

  if (jam > 0) {
    return `${jam} Jam ${menit} Menit`
  }

  return `${menit} Menit`
})

/* ---------------------------------------------------------------
   FORMAT
--------------------------------------------------------------- */

function rupiah(value) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0
  }).format(Number(value || 0))
}

function formatTanggal(value) {
  if (!value) {
    return "-"
  }

  const tanggal = new Date(value)

  if (Number.isNaN(tanggal.getTime())) {
    return value
  }

  return tanggal.toLocaleString("id-ID", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit"
  })
}

/* ---------------------------------------------------------------
   FOCUS SCANNER
--------------------------------------------------------------- */

async function fokusScanner() {
  await nextTick()

  if (!scannerInput.value) {
    return
  }

  scannerInput.value.focus()

  try {
    scannerInput.value.select()
  } catch (error) {
    // abaikan
  }
}

/* ---------------------------------------------------------------
   POPUP GERBANG (PALANG TERBUKA / DITOLAK)
--------------------------------------------------------------- */

const gatePopup = ref(null)

// true = palang terangkat (dipakai untuk animasi)
const gateOpen = ref(false)

let popupTimer = null
let openTimer = null

async function closeGatePopup() {
  if (popupTimer) {
    clearTimeout(popupTimer)
    popupTimer = null
  }

  if (openTimer) {
    clearTimeout(openTimer)
    openTimer = null
  }

  gatePopup.value = null
  gateOpen.value = false

  await fokusScanner()
}

function showGatePopup(popup) {
  if (popupTimer) {
    clearTimeout(popupTimer)
  }

  if (openTimer) {
    clearTimeout(openTimer)
  }

  // Mulai dari palang tertutup, lalu terangkat setelah popup muncul
  gateOpen.value = false
  gatePopup.value = popup

  if (popup.type === "open") {
    openTimer = setTimeout(() => {
      gateOpen.value = true
    }, 350)
  }

  // Tutup otomatis
  popupTimer = setTimeout(() => {
    closeGatePopup()
  }, 4500)
}

/* ---------------------------------------------------------------
   PARSE KODE TIKET / MEMBER
--------------------------------------------------------------- */

function extractKode(text) {
  if (!text) {
    return null
  }

  const isi = String(text).trim()

  // 1. Tiket berlabel (QR dari tiket cetak).
  //    Hanya "A" + angka, supaya teks sesudahnya
  //    ("Waktu Masuk") tidak ikut terbaca.
  const tiketBerlabel = isi.match(
    /Kode\s*Tiket\s*[:=\-]?\s*(A\d{3,})/i
  )

  if (tiketBerlabel) {
    return {
      kode: tiketBerlabel[1].toUpperCase(),
      tipe: "tiket"
    }
  }

  // 2. Member berlabel: "Kode Member : MBR-XXXXXX"
  const memberBerlabel = isi.match(
    /Kode\s*Member\s*[:=\-]?\s*(MBR-[A-Z0-9]{6})/i
  )

  if (memberBerlabel) {
    return {
      kode: memberBerlabel[1].toUpperCase(),
      tipe: "member"
    }
  }

  // 3. Kode member tanpa label: MBR-XXXXXX
  const kodeMember = isi.match(/MBR-[A-Z0-9]{6}/i)

  if (kodeMember) {
    return {
      kode: kodeMember[0].toUpperCase(),
      tipe: "member"
    }
  }

  // 4. Kode tiket tanpa label: A007
  const kodeTiket = isi.match(/(?<![A-Z0-9])A\d{3,}/i)

  if (kodeTiket) {
    return {
      kode: kodeTiket[0].toUpperCase(),
      tipe: "tiket"
    }
  }

  // 5. Satu kata tanpa spasi (misalnya token di QR kartu member).
  //    Token bisa memuat huruf kecil, jadi jangan diubah ke huruf besar.
  if (/^[A-Z0-9][A-Z0-9_\-\/]{2,}$/i.test(isi)) {
    return {
      kode: isi,
      tipe: "member"
    }
  }

  return null
}

/* ---------------------------------------------------------------
   PROSES HASIL SCAN
--------------------------------------------------------------- */

async function prosesScan() {
  const hasilScan = kodeScanner.value.trim()

  if (!hasilScan) {
    await fokusScanner()
    return
  }

  const hasil = extractKode(hasilScan)

  if (!hasil) {
    alert(
      "QR terbaca, tetapi kode tidak dikenali.\n\n" +
      `Isi hasil scan: ${hasilScan}`
    )
    kodeScanner.value = ""
    await fokusScanner()
    return
  }

  if (hasil.tipe === "member") {
    await cariMember(hasil.kode)
  } else {
    await cariTiket(hasil.kode)
  }

  kodeScanner.value = ""

  // Kalau tiket ditemukan, fokus pindah ke input plat.
  // Kalau tidak, kembali ke scanner.
  if ((tiketData.value && !tiketSudahKeluar.value) || memberData.value) {
    await fokusPlat()
  } else {
    await fokusScanner()
  }
}

/* ---------------------------------------------------------------
   FOCUS INPUT PLAT
--------------------------------------------------------------- */

const platInput = ref(null)

async function fokusPlat() {
  await nextTick()

  if (platInput.value) {
    platInput.value.focus()
  }
}

/* ---------------------------------------------------------------
   CARI TIKET NON-MEMBER DARI API
--------------------------------------------------------------- */

async function cariTiket(kode) {
  if (!kode) {
    return
  }

  try {
    loadingCari.value = true
    memberData.value = null

    const response = await fetch(
      `${API_BASE}/tiket`,
      {
        method: "GET",
        headers: {
          Accept: "application/json"
        }
      }
    )

    const result = await response.json()

    if (!response.ok) {
      throw new Error(
        result?.message ||
        "Gagal mengambil data tiket."
      )
    }

    let daftarTiket = []

    if (Array.isArray(result)) {
      daftarTiket = result
    } else if (Array.isArray(result?.data)) {
      daftarTiket = result.data
    }

    const kodeTarget = String(kode)
      .trim()
      .toUpperCase()

    const tiket = daftarTiket.find((item) => {
      return String(item?.kode_tiket || "")
        .trim()
        .toUpperCase() === kodeTarget
    })

    if (!tiket) {
      tiketData.value = null

      alert(
        `Tiket ${kodeTarget} tidak ditemukan.`
      )

      return
    }

    tiketData.value = tiket

    // Isi otomatis kalau tiket sudah punya plat, kalau belum petugas isi manual
    nomorPlat.value =
      tiket.nomor_polisi ||
      tiket.nomor_plat ||
      tiket.plat_nomor ||
      ""

    const jenis =
      tiket.jenis_kendaraan ||
      tiket.jenis ||
      "motor"

    if (String(jenis).toLowerCase() === "mobil") {
      jenisKendaraan.value = "mobil"
    } else {
      jenisKendaraan.value = "motor"
    }

    uangDibayar.value = 0

    // Tiket hanya boleh dipakai keluar satu kali
    if (tiketSudahKeluar.value) {
      showGatePopup({
        type: "blocked",
        title: "Kendaraan sudah keluar",
        message: "Tiket ini sudah dipakai untuk keluar. Tidak bisa dipakai dua kali.",
        detail: tiket.kode_tiket
      })
    }

  } catch (error) {
    console.error("Cari tiket error:", error)

    alert(
      error?.message ||
      "Terjadi kesalahan saat mencari tiket."
    )
  } finally {
    loadingCari.value = false
  }
}

/* ---------------------------------------------------------------
   CARI MEMBER DARI API (READ ONLY)
--------------------------------------------------------------- */

async function cariMember(kode) {
  try {
    loadingCari.value = true
    tiketData.value = null

    const response = await fetch(
      `${API_BASE}/member/cari/${encodeURIComponent(kode)}`,
      {
        method: "GET",
        headers: { Accept: "application/json" }
      }
    )

    const result = await response.json()

    if (!response.ok) {
      throw new Error(result?.message || "Member tidak ditemukan.")
    }

    const member = result?.data

    const statusParkir = String(member?.status_parkir || "").toLowerCase()

    if (statusParkir !== "sedang_masuk") {
      memberData.value = null

      const sudahKeluar = statusParkir.includes("keluar")

      showGatePopup({
        type: "blocked",
        title: sudahKeluar
          ? "Kendaraan sudah keluar"
          : "Belum tercatat masuk",
        message: sudahKeluar
          ? "Member ini sudah keluar. Kartu tidak bisa discan dua kali."
          : "Member ini belum tercatat masuk parkir.",
        detail: member?.kode_member || kode
      })

      return
    }

    memberData.value = member
    jenisKendaraanKeluar.value = member?.jenis_kendaraan || "motor"

    // Isi otomatis kalau member sudah punya plat, kalau belum petugas isi manual
    nomorPlat.value =
      member?.nomor_polisi ||
      member?.nomor_plat ||
      member?.plat_nomor ||
      ""

  } catch (error) {
    console.error("Cari member error:", error)
    memberData.value = null
    alert(error?.message || "Terjadi kesalahan saat mencari member.")
  } finally {
    loadingCari.value = false
  }
}

/* ---------------------------------------------------------------
   CARI MANUAL
--------------------------------------------------------------- */

async function cariManual() {
  const input = kodeScanner.value.trim()

  if (!input) {
    alert("Masukkan kode terlebih dahulu.")
    await fokusScanner()
    return
  }

  const hasil = extractKode(input)

  if (!hasil) {
    alert(
      "Format kode tidak valid.\n\n" +
      `Isi input: ${input}`
    )
    kodeScanner.value = ""
    await fokusScanner()
    return
  }

  if (hasil.tipe === "member") {
    await cariMember(hasil.kode)
  } else {
    await cariTiket(hasil.kode)
  }

  kodeScanner.value = ""

  if ((tiketData.value && !tiketSudahKeluar.value) || memberData.value) {
    await fokusPlat()
  } else {
    await fokusScanner()
  }
}

/* ---------------------------------------------------------------
   PEMBAYARAN + KELUAR (NON-MEMBER) + CETAK INVOICE
--------------------------------------------------------------- */

async function prosesPembayaran() {
  if (!tiketData.value) {
    alert("Data tiket belum tersedia.")
    return
  }

  if (tiketSudahKeluar.value) {
    alert("Tiket ini sudah diproses keluar.")
    return
  }

  if (!platTerisi.value) {
    alert("Nomor plat wajib diisi.")
    await fokusPlat()
    return
  }

  if (!bisaBayar.value) {
    alert(
      `Uang pembayaran kurang.\n\n` +
      `Total pembayaran: ${rupiah(totalBayar.value)}`
    )

    return
  }

  const konfirmasi = window.confirm(
    "Konfirmasi transaksi?\n\n" +
    `Kode Tiket : ${tiketData.value.kode_tiket}\n` +
    `Kendaraan  : ${jenisKendaraanLabel.value}\n` +
    `No. Polisi : ${nomorPolisi.value}\n` +
    `Total      : ${rupiah(totalBayar.value)}\n` +
    `Dibayar    : ${rupiah(uangDibayar.value)}\n` +
    `Kembalian  : ${rupiah(kembalian.value)}`
  )

  if (!konfirmasi) {
    await fokusScanner()
    return
  }

  try {
    loadingBayar.value = true

    const id = tiketData.value.id

    const payload = {
      jenis_kendaraan: jenisKendaraan.value,
      jenis: jenisKendaraan.value,

      nomor_polisi: nomorPolisi.value,
      nomor_plat: nomorPolisi.value,

      tarif_per_jam: Number(tarifParkir.value),

      uang_dibayar: Number(uangDibayar.value),

      total_bayar: Number(totalBayar.value),

      kembalian: Number(kembalian.value)
    }

    const response = await fetch(
      `${API_BASE}/tiket/${id}/keluar`,
      {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json"
        },
        body: JSON.stringify(payload)
      }
    )

    const result = await response.json()

    if (!response.ok) {
      throw new Error(
        result?.message ||
        "Pembayaran gagal."
      )
    }

    const tiketUpdate = result?.data || result

    if (
      String(tiketUpdate?.status || "").toLowerCase() !== "keluar" &&
      !tiketUpdate?.waktu_keluar
    ) {
      throw new Error(
        "Server belum mengubah status tiket menjadi keluar."
      )
    }

    tiketData.value = tiketUpdate

    await cetakInvoiceNonMember({
      kode_tiket: tiketData.value.kode_tiket,
      jenis: jenisKendaraanLabel.value,
      nomor_polisi: nomorPolisi.value,
      waktu_masuk: tiketData.value.waktu_masuk,
      waktu_keluar: tiketData.value.waktu_keluar,
      durasi: durasiParkir.value,
      tarif: totalBayar.value,
      dibayar: uangDibayar.value,
      kembalian: kembalian.value
    })

    showGatePopup({
      type: "open",
      title: "Silakan Keluar",
      message: `Pembayaran berhasil. Kembalian ${rupiah(kembalian.value)}.`,
      detail: `${tiketData.value.kode_tiket} - ${nomorPolisi.value}`
    })

    kodeScanner.value = ""

    await fokusScanner()

  } catch (error) {
    console.error("Pembayaran error:", error)

    alert(
      error?.message ||
      "Terjadi kesalahan saat memproses pembayaran."
    )

    await fokusScanner()

  } finally {
    loadingBayar.value = false
  }
}

async function cetakInvoiceNonMember(data) {
  const qrData = `
PARKIR PLAZA ANDALAS

Kode Tiket : ${data.kode_tiket}
No. Polisi : ${data.nomor_polisi}
Waktu Keluar : ${data.waktu_keluar}
Status : keluar
`

  const qrImage = await QRCode.toDataURL(qrData)

  const pdf = new jsPDF({
    orientation: "portrait",
    unit: "mm",
    format: [80, 150]
  })

  pdf.setFontSize(12)
  pdf.text("PARKIR", 40, 12, { align: "center" })
  pdf.text("PLAZA ANDALAS", 40, 20, { align: "center" })

  pdf.setFontSize(7)
  pdf.text("Jl. Dr. KRT Radjiman Widyodiningrat", 40, 30, { align: "center" })
  pdf.text("Jakarta Timur", 40, 35, { align: "center" })

  pdf.setFontSize(9)
  pdf.text("BUKTI PEMBAYARAN KELUAR", 40, 42, { align: "center" })

  pdf.addImage(qrImage, "PNG", 25, 48, 30, 30)

  pdf.setFontSize(13)
  pdf.text(data.kode_tiket, 40, 85, { align: "center" })

  pdf.setFontSize(8)

  const baris = [
    `Kendaraan   : ${data.jenis}`,
    `No. Polisi  : ${data.nomor_polisi}`,
    "",
    `Waktu Masuk : ${formatTanggal(data.waktu_masuk)}`,
    `Waktu Keluar: ${formatTanggal(data.waktu_keluar)}`,
    `Durasi      : ${data.durasi}`,
    "",
    `Tarif       : ${rupiah(data.tarif)}`,
    `Dibayar     : ${rupiah(data.dibayar)}`,
    `Kembalian   : ${rupiah(data.kembalian)}`,
    "",
    "Terima Kasih"
  ]

  let posisiY = 96

  baris.forEach((line) => {
    pdf.text(line, 40, posisiY, { align: "center" })
    posisiY += 5
  })

  pdf.save(`Keluar-${data.kode_tiket}-${Date.now()}.pdf`)
}

/* ---------------------------------------------------------------
   MEMBER KELUAR (TANPA BAYAR) + CETAK INVOICE
--------------------------------------------------------------- */

async function prosesMemberKeluar() {
  if (!memberData.value) {
    alert("Data member belum tersedia.")
    return
  }

  if (!platTerisi.value) {
    alert("Nomor plat wajib diisi.")
    await fokusPlat()
    return
  }

  const konfirmasi = window.confirm(
    "Konfirmasi member keluar?\n\n" +
    `Kode Member : ${memberData.value.kode_member}\n` +
    `Nama        : ${memberData.value.nama_member}\n` +
    `No. Polisi  : ${nomorPolisi.value}\n` +
    `Kendaraan   : ${jenisKendaraanKeluar.value === "mobil" ? "Mobil" : "Motor"}`
  )

  if (!konfirmasi) {
    await fokusScanner()
    return
  }

  try {
    loadingKeluarMember.value = true

    const waktuMasukSebelumKeluar = memberData.value.waktu_masuk_terakhir

    const response = await fetch(`${API_BASE}/member/keluar`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json"
      },
      body: JSON.stringify({
        kode_member: memberData.value.kode_member,
        jenis_kendaraan: jenisKendaraanKeluar.value,
        nomor_polisi: nomorPolisi.value,
        nomor_plat: nomorPolisi.value
      })
    })

    const result = await response.json()

    if (!response.ok) {
      throw new Error(result?.message || "Gagal memproses member keluar.")
    }

    const memberSetelahKeluar = result?.data || memberData.value

    await cetakInvoiceMemberKeluar({
      ...memberSetelahKeluar,
      waktu_masuk_terakhir: waktuMasukSebelumKeluar,
      jenis_kendaraan: jenisKendaraanKeluar.value,
      nomor_polisi: nomorPolisi.value
    })

    const kodeMember = memberData.value.kode_member
    const namaMember = memberData.value.nama_member

    showGatePopup({
      type: "open",
      title: "Silakan Keluar",
      message: namaMember
        ? `Terima kasih, ${namaMember}. Palang pintu terbuka.`
        : "Member berhasil keluar. Palang pintu terbuka.",
      detail: `${kodeMember} - ${nomorPolisi.value}`
    })

    memberData.value = null
    nomorPlat.value = ""
    kodeScanner.value = ""
    await fokusScanner()

  } catch (error) {
    console.error("Member keluar error:", error)
    alert(error?.message || "Terjadi kesalahan.")
    await fokusScanner()
  } finally {
    loadingKeluarMember.value = false
  }
}

async function cetakInvoiceMemberKeluar(member) {
  const waktuMasuk = member.waktu_masuk_terakhir
    ? new Date(member.waktu_masuk_terakhir)
    : null

  const waktuKeluar = new Date()

  let durasiText = "-"

  if (waktuMasuk && !Number.isNaN(waktuMasuk.getTime())) {
    const selisihMs = Math.max(waktuKeluar.getTime() - waktuMasuk.getTime(), 0)
    const totalMenit = Math.floor(selisihMs / 1000 / 60)
    const jam = Math.floor(totalMenit / 60)
    const menit = totalMenit % 60

    durasiText = jam > 0 ? `${jam} Jam ${menit} Menit` : `${menit} Menit`
  }

  const qrData = `
PARKIR PLAZA ANDALAS

Kode Member : ${member.kode_member}
Nama : ${member.nama_member}
No. Polisi : ${member.nomor_polisi || "-"}
Waktu Keluar : ${waktuKeluar.toLocaleString("id-ID")}
`

  const qrImage = await QRCode.toDataURL(qrData)

  const pdf = new jsPDF({
    orientation: "portrait",
    unit: "mm",
    format: [80, 150]
  })

  pdf.setFontSize(12)
  pdf.text("PARKIR", 40, 12, { align: "center" })
  pdf.text("PLAZA ANDALAS", 40, 20, { align: "center" })

  pdf.setFontSize(7)
  pdf.text("Jl. Dr. KRT Radjiman Widyodiningrat", 40, 30, { align: "center" })
  pdf.text("Jakarta Timur", 40, 35, { align: "center" })

  pdf.setFontSize(9)
  pdf.text("BUKTI KELUAR MEMBER", 40, 42, { align: "center" })

  pdf.addImage(qrImage, "PNG", 25, 48, 30, 30)

  pdf.setFontSize(13)
  pdf.text(member.kode_member, 40, 85, { align: "center" })

  pdf.setFontSize(8)

  const jenisLabel = member.jenis_kendaraan === "mobil" ? "Mobil" : "Motor"

  const baris = [
    `Nama Member : ${member.nama_member}`,
    `Perusahaan  : ${member.nama_perusahaan || "-"}`,
    `Kendaraan   : ${jenisLabel}`,
    `No. Polisi  : ${member.nomor_polisi || "-"}`,
    "",
    `Waktu Masuk : ${waktuMasuk ? waktuMasuk.toLocaleString("id-ID") : "-"}`,
    `Waktu Keluar: ${waktuKeluar.toLocaleString("id-ID")}`,
    `Durasi      : ${durasiText}`,
    "",
    "Status : LUNAS (Member Bulanan)",
    "",
    "Terima Kasih"
  ]

  let posisiY = 96

  baris.forEach((line) => {
    pdf.text(line, 40, posisiY, { align: "center" })
    posisiY += 5
  })

  pdf.save(`Keluar-${member.kode_member}-${Date.now()}.pdf`)
}

/* ---------------------------------------------------------------
   RESET
--------------------------------------------------------------- */

async function resetTiket() {
  tiketData.value = null
  memberData.value = null

  kodeScanner.value = ""

  jenisKendaraan.value = "motor"
  nomorPlat.value = ""

  uangDibayar.value = 0

  await fokusScanner()
}

/* ---------------------------------------------------------------
   KEMBALI
--------------------------------------------------------------- */

function kembali() {
  window.history.back()
}

/* ---------------------------------------------------------------
   KEYBOARD SUPPORT
--------------------------------------------------------------- */

function handleGlobalKeydown(event) {
  const target = event.target

  if (target === scannerInput.value) {
    return
  }

  const tagName = target?.tagName?.toLowerCase()

  if (
    tagName !== "input" &&
    tagName !== "textarea" &&
    tagName !== "select"
  ) {
    fokusScanner()
  }
}

/* ---------------------------------------------------------------
   MOUNT
--------------------------------------------------------------- */

onMounted(async () => {
  window.addEventListener("keydown", handleGlobalKeydown)

  await fokusScanner()
})

onBeforeUnmount(() => {
  window.removeEventListener("keydown", handleGlobalKeydown)

  if (popupTimer) {
    clearTimeout(popupTimer)
  }

  if (openTimer) {
    clearTimeout(openTimer)
  }
})
</script>

<template>
  <div class="page">

    <!-- HEADER -->
    <header class="topbar">
      <div class="topbar-inner">

        <div class="brand">
          <div class="brand-icon">P</div>

          <div class="brand-text">
            <h1>Parkir Plaza Andalas</h1>
            <span>Gerbang keluar</span>
          </div>
        </div>

        <button
          class="back-btn"
          type="button"
          @click="kembali"
        >
          <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <path d="M15 18l-6-6 6-6" />
          </svg>
          Kembali
        </button>

      </div>
    </header>

    <!-- CONTENT -->
    <main class="content">

      <!-- TITLE -->
      <section class="hero">
        <h2>Scan tiket kendaraan</h2>

        <p class="subtitle">
          Arahkan scanner ke QR pada tiket atau kartu member.
          Setelah terbaca, data akan diproses otomatis.
        </p>
      </section>

      <!-- SCANNER -->
      <section class="panel scanner-card">

        <div class="scanner-head">
          <div class="scanner-icon">
            <svg
              width="22"
              height="22"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M4 8V6a2 2 0 0 1 2-2h2" />
              <path d="M16 4h2a2 2 0 0 1 2 2v2" />
              <path d="M20 16v2a2 2 0 0 1-2 2h-2" />
              <path d="M8 20H6a2 2 0 0 1-2-2v-2" />
              <path d="M7 12h10" />
            </svg>
          </div>

          <div>
            <h3>Scan tiket atau kartu member</h3>
            <p class="scanner-description">
              Scanner USB akan mengirim kode ke kolom di bawah.
            </p>
          </div>

          <span class="scanner-status">
            <i class="dot"></i>
            Siap
          </span>
        </div>

        <div class="scanner-input-wrap">
          <input
            ref="scannerInput"
            v-model="kodeScanner"
            class="scanner-input"
            type="text"
            autocomplete="off"
            spellcheck="false"
            placeholder="Menunggu scanner..."
            @keydown.enter.prevent="prosesScan"
          />

          <button
            type="button"
            class="manual-btn"
            :disabled="loadingCari"
            @click="cariManual"
          >
            {{ loadingCari ? "Mencari..." : "Cari kode" }}
          </button>
        </div>

      </section>

      <!-- DATA TIKET NON-MEMBER -->
      <section
        v-if="tiketData"
        class="panel ticket-card"
      >

        <!-- TICKET HEADER -->
        <div class="ticket-head">

          <div>
            <p class="section-label">Tiket ditemukan</p>

            <h3 class="kode-tiket">
              {{ tiketData.kode_tiket }}
            </h3>
          </div>

          <div
            class="status-badge"
            :class="tiketSudahKeluar ? 'status-out' : 'status-in'"
          >
            {{ tiketSudahKeluar ? "Sudah keluar" : "Masih parkir" }}
          </div>

        </div>

        <!-- SUDAH KELUAR -->
        <div
          v-if="tiketSudahKeluar"
          class="warning-box"
        >
          Tiket ini sudah digunakan untuk transaksi keluar.
        </div>

        <!-- INFO -->
        <div class="info-grid">

          <div class="info-item">
            <span>Jenis kendaraan</span>
            <strong>{{ jenisKendaraanLabel }}</strong>
          </div>

          <div class="info-item">
            <span>Nomor polisi</span>
            <strong>{{ nomorPolisi }}</strong>
          </div>

          <div class="info-item">
            <span>Waktu masuk</span>
            <strong>{{ formatTanggal(tiketData.waktu_masuk) }}</strong>
          </div>

          <div class="info-item">
            <span>Durasi parkir</span>
            <strong>{{ durasiParkir }}</strong>
          </div>

        </div>

        <!-- PAYMENT -->
        <div
          v-if="!tiketSudahKeluar"
          class="payment-section"
        >

          <div class="payment-header">
            Detail pembayaran
          </div>

          <!-- JENIS -->
          <div class="form-group">

            <label>Jenis kendaraan</label>

            <select v-model="jenisKendaraan">
              <option value="motor">Motor</option>
              <option value="mobil">Mobil</option>
            </select>

          </div>

          <!-- NOMOR PLAT -->
          <div class="form-group">

            <label>
              Nomor plat <span class="wajib">*</span>
            </label>

            <input
              ref="platInput"
              v-model="nomorPlat"
              type="text"
              placeholder="Contoh: B 1234 XYZ"
              autocomplete="off"
              spellcheck="false"
              :class="{ 'input-error': !platTerisi }"
            />

            <p
              v-if="!platTerisi"
              class="hint-plat"
            >
              Nomor plat wajib diisi sebelum pembayaran.
            </p>

          </div>

          <!-- PAYMENT BOX -->
          <div class="payment-box">

            <div class="payment-row">
              <span>Tarif</span>
              <strong>{{ rupiah(tarifParkir) }}</strong>
            </div>

            <div class="payment-row">
              <span>Uang dibayar</span>

              <input
                v-model.number="uangDibayar"
                type="number"
                min="0"
                placeholder="0"
              />
            </div>

            <div class="payment-row total">
              <span>Total bayar</span>
              <strong>{{ rupiah(totalBayar) }}</strong>
            </div>

            <div class="payment-row change">
              <span>Kembalian</span>
              <strong>{{ rupiah(kembalian) }}</strong>
            </div>

          </div>

          <!-- BAYAR -->
          <button
            class="pay-btn"
            type="button"
            :disabled="loadingBayar || !bisaBayar || !platTerisi"
            @click="prosesPembayaran"
          >
            {{ loadingBayar ? "Memproses transaksi..." : "Konfirmasi pembayaran" }}
          </button>

        </div>

        <!-- RESET -->
        <button
          class="reset-btn"
          type="button"
          @click="resetTiket"
        >
          Scan tiket berikutnya
        </button>

      </section>

      <!-- DATA MEMBER -->
      <section
        v-if="memberData"
        class="panel ticket-card"
      >

        <div class="ticket-head">
          <div>
            <p class="section-label">Member ditemukan</p>

            <h3 class="kode-tiket">
              {{ memberData.kode_member }}
            </h3>
          </div>

          <div class="status-badge status-in">
            Sedang parkir
          </div>
        </div>

        <div class="info-grid">
          <div class="info-item">
            <span>Nama member</span>
            <strong>{{ memberData.nama_member }}</strong>
          </div>

          <div class="info-item">
            <span>Perusahaan</span>
            <strong>{{ memberData.nama_perusahaan || '-' }}</strong>
          </div>
        </div>

        <div class="payment-section">
          <div class="payment-header">
            Konfirmasi keluar
          </div>

          <div class="form-group">
            <label>Jenis kendaraan</label>

            <select v-model="jenisKendaraanKeluar">
              <option value="motor">Motor</option>
              <option value="mobil">Mobil</option>
            </select>
          </div>

          <!-- NOMOR PLAT MEMBER -->
          <div class="form-group">

            <label>
              Nomor plat <span class="wajib">*</span>
            </label>

            <input
              ref="platInput"
              v-model="nomorPlat"
              type="text"
              placeholder="Contoh: B 1234 XYZ"
              autocomplete="off"
              spellcheck="false"
              :class="{ 'input-error': !platTerisi }"
            />

            <p
              v-if="!platTerisi"
              class="hint-plat"
            >
              Nomor plat wajib diisi sebelum member keluar.
            </p>

          </div>

          <button
            class="pay-btn"
            type="button"
            :disabled="loadingKeluarMember || !platTerisi"
            @click="prosesMemberKeluar"
          >
            {{ loadingKeluarMember ? "Memproses..." : "Konfirmasi member keluar" }}
          </button>
        </div>

        <button
          class="reset-btn"
          type="button"
          @click="resetTiket"
        >
          Scan berikutnya
        </button>

      </section>

    </main>

    <!--
      POPUP GERBANG
      Di-teleport ke body, jadi warnanya ditulis langsung (bukan variabel .page).
    -->
    <Teleport to="body">
      <Transition name="popup">
        <div
          v-if="gatePopup"
          class="gate-overlay"
          @click="closeGatePopup"
        >
          <div
            class="gate-card popup-card"
            :class="gatePopup.type === 'open' ? 'is-ok' : 'is-bad'"
            @click.stop
          >

            <!-- ILUSTRASI PALANG -->
            <div class="gate-scene">

              <div class="gate-post">
                <span
                  class="gate-light"
                  :class="gatePopup.type === 'open' ? 'light-open' : 'light-blocked'"
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

            <p class="gate-tag">
              {{ gatePopup.type === 'open' ? 'Palang terbuka' : 'Akses ditolak' }}
            </p>

            <h3 class="gate-title">
              {{ gatePopup.title }}
            </h3>

            <p class="gate-msg">
              {{ gatePopup.message }}
            </p>

            <p
              v-if="gatePopup.detail"
              class="gate-detail"
            >
              {{ gatePopup.detail }}
            </p>

            <button
              type="button"
              tabindex="-1"
              class="gate-btn"
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

<style scoped>
* {
  box-sizing: border-box;
}

/*
  Palet
  putih   #ffffff  permukaan kartu
  kabut   #f4f6fa  latar halaman
  garis   #e3e8f0  border
  tinta   #1b2230  teks & tombol utama
  redup   #6a7588  teks sekunder
  biru    #3b68d1  aksen
  biru-t  #eef3fc  latar aksen lembut
*/

.page {
  --putih: #ffffff;
  --kabut: #f4f6fa;
  --garis: #e3e8f0;
  --tinta: #1b2230;
  --redup: #6a7588;
  --biru: #3b68d1;
  --biru-tua: #2f56b3;
  --biru-t: #eef3fc;
  --hijau: #2f7d5b;
  --hijau-t: #eaf5ef;
  --kuning: #8a6416;
  --kuning-t: #fbf5e6;
  --merah: #c0392b;

  min-height: 100vh;
  color: var(--tinta);
  background: var(--kabut);

  font-family:
    Inter,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
}

/* ================================
   HEADER
================================ */

.topbar {
  position: sticky;
  top: 0;
  z-index: 50;

  background: var(--putih);
  border-bottom: 1px solid var(--garis);
}

.topbar-inner {
  width: min(880px, calc(100% - 32px));
  min-height: 68px;
  margin: 0 auto;

  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.brand-icon {
  width: 38px;
  height: 38px;

  display: grid;
  place-items: center;

  border-radius: 10px;
  background: var(--tinta);
  color: var(--putih);

  font-weight: 700;
  font-size: 18px;
}

.brand-text h1 {
  margin: 0;

  font-size: 15px;
  font-weight: 650;
  line-height: 1.2;
}

.brand-text span {
  display: block;
  margin-top: 2px;

  color: var(--redup);
  font-size: 12px;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;

  padding: 8px 14px 8px 10px;

  border: 1px solid var(--garis);
  border-radius: 8px;
  background: var(--putih);
  color: var(--tinta);

  font-size: 13px;
  font-weight: 600;

  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
}

.back-btn:hover {
  background: var(--kabut);
  border-color: #d3dae6;
}

/* ================================
   CONTENT
================================ */

.content {
  width: min(880px, calc(100% - 32px));
  margin: 0 auto;
  padding: 40px 0 72px;
}

.hero h2 {
  margin: 0;

  font-size: clamp(26px, 4vw, 32px);
  font-weight: 650;
  line-height: 1.15;
  letter-spacing: -0.5px;
}

.subtitle {
  max-width: 560px;
  margin: 10px 0 28px;

  color: var(--redup);
  font-size: 14px;
  line-height: 1.65;
}

.panel {
  border: 1px solid var(--garis);
  border-radius: 14px;
  background: var(--putih);
}

/* ================================
   SCANNER CARD
================================ */

.scanner-card {
  padding: 24px;
}

.scanner-head {
  display: flex;
  align-items: center;
  gap: 14px;
}

.scanner-icon {
  flex: 0 0 44px;

  width: 44px;
  height: 44px;

  display: grid;
  place-items: center;

  border-radius: 10px;
  background: var(--biru-t);
  color: var(--biru);
}

.scanner-head h3 {
  margin: 0;

  font-size: 16px;
  font-weight: 650;
}

.scanner-description {
  margin: 3px 0 0;

  color: var(--redup);
  font-size: 13px;
}

.scanner-status {
  margin-left: auto;

  display: inline-flex;
  align-items: center;
  gap: 6px;

  padding: 5px 10px;

  border-radius: 999px;
  background: var(--hijau-t);
  color: var(--hijau);

  font-size: 12px;
  font-weight: 600;
}

.dot {
  width: 6px;
  height: 6px;

  border-radius: 50%;
  background: currentColor;
}

.scanner-input-wrap {
  display: flex;
  align-items: center;
  gap: 8px;

  margin-top: 20px;
  padding: 6px 6px 6px 16px;

  border: 1px solid var(--garis);
  border-radius: 10px;
  background: var(--kabut);

  transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}

.scanner-input-wrap:focus-within {
  border-color: var(--biru);
  background: var(--putih);
  box-shadow: 0 0 0 3px rgba(59, 104, 209, 0.14);
}

.scanner-input {
  flex: 1;
  min-width: 0;
  height: 42px;

  border: 0;
  outline: 0;
  background: transparent;

  color: var(--tinta);
  font-size: 17px;
  font-weight: 600;
  letter-spacing: 0.4px;
}

.scanner-input::placeholder {
  color: #9aa4b5;
  font-weight: 400;
  letter-spacing: 0;
}

.manual-btn {
  height: 40px;
  padding: 0 16px;

  border: 0;
  border-radius: 8px;
  background: var(--tinta);
  color: var(--putih);

  font-size: 13px;
  font-weight: 600;

  cursor: pointer;
  transition: background 0.15s;
}

.manual-btn:hover:not(:disabled) {
  background: #2c3547;
}

.manual-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ================================
   TICKET
================================ */

.ticket-card {
  margin-top: 20px;
  padding: 24px;
}

.ticket-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;

  margin-bottom: 20px;
}

.section-label {
  margin: 0 0 4px;

  color: var(--redup);
  font-size: 13px;
}

.kode-tiket {
  margin: 0;

  font-size: 26px;
  font-weight: 650;
  letter-spacing: -0.3px;
}

.status-badge {
  padding: 5px 12px;

  border-radius: 999px;

  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.status-in {
  color: var(--biru-tua);
  background: var(--biru-t);
}

.status-out {
  color: var(--hijau);
  background: var(--hijau-t);
}

.warning-box {
  margin-bottom: 16px;
  padding: 12px 14px;

  border-radius: 10px;
  background: var(--kuning-t);
  color: var(--kuning);

  font-size: 13px;
}

/* ================================
   INFO GRID
================================ */

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.info-item {
  padding: 14px 16px;

  border-radius: 10px;
  background: var(--kabut);
}

.info-item span {
  display: block;
  margin-bottom: 4px;

  color: var(--redup);
  font-size: 12px;
}

.info-item strong {
  color: var(--tinta);
  font-size: 15px;
  font-weight: 600;
}

/* ================================
   PAYMENT
================================ */

.payment-section {
  margin-top: 24px;
  padding-top: 20px;

  border-top: 1px solid var(--garis);
}

.payment-header {
  font-size: 14px;
  font-weight: 650;
}

.form-group {
  margin-top: 14px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;

  color: var(--redup);
  font-size: 13px;
}

.form-group select,
.form-group input {
  width: 100%;
  height: 42px;
  padding: 0 12px;

  border: 1px solid var(--garis);
  border-radius: 8px;
  background: var(--putih);
  color: var(--tinta);

  font-size: 14px;
  outline: none;
}

.form-group input {
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.form-group input::placeholder {
  text-transform: none;
  font-weight: 400;
  letter-spacing: 0;
  color: #9aa4b5;
}

.form-group input:focus {
  border-color: var(--biru);
}

.form-group input.input-error {
  border-color: #e3a29a;
}

.wajib {
  color: var(--merah);
}

.hint-plat {
  margin: 6px 0 0;

  color: var(--merah);
  font-size: 12px;
}

.payment-box {
  margin-top: 14px;
  padding: 4px 16px;

  border: 1px solid var(--garis);
  border-radius: 10px;
}

.payment-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;

  padding: 12px 0;

  border-bottom: 1px solid var(--garis);
}

.payment-row:last-child {
  border-bottom: 0;
}

.payment-row span {
  color: var(--redup);
  font-size: 14px;
}

.payment-row strong {
  color: var(--tinta);
  font-size: 15px;
  font-weight: 600;
}

.payment-row input {
  width: 200px;
  height: 38px;
  padding: 0 12px;

  border: 1px solid var(--garis);
  border-radius: 8px;
  background: var(--putih);
  color: var(--tinta);

  font-size: 14px;
  text-align: right;
  outline: none;
}

.payment-row.total strong {
  font-size: 20px;
  font-weight: 700;
}

.payment-row.change strong {
  color: var(--biru-tua);
  font-size: 17px;
}

.pay-btn {
  width: 100%;
  height: 46px;
  margin-top: 16px;

  border: 0;
  border-radius: 10px;
  background: var(--biru);
  color: var(--putih);

  font-size: 14px;
  font-weight: 650;

  cursor: pointer;
  transition: background 0.15s;
}

.pay-btn:hover:not(:disabled) {
  background: var(--biru-tua);
}

.pay-btn:disabled {
  background: #c5cede;
  cursor: not-allowed;
}

.reset-btn {
  width: 100%;
  height: 42px;
  margin-top: 10px;

  border: 1px solid var(--garis);
  border-radius: 10px;
  background: var(--putih);
  color: var(--tinta);

  font-size: 13px;
  font-weight: 600;

  cursor: pointer;
  transition: background 0.15s;
}

.reset-btn:hover {
  background: var(--kabut);
}

/* fokus keyboard yang jelas */
.back-btn:focus-visible,
.manual-btn:focus-visible,
.pay-btn:focus-visible,
.reset-btn:focus-visible,
.form-group select:focus-visible,
.form-group input:focus-visible,
.payment-row input:focus-visible {
  outline: 2px solid var(--biru);
  outline-offset: 2px;
}

/* ================================
   RESPONSIVE
================================ */

@media (max-width: 720px) {
  .content {
    padding-top: 28px;
  }

  .scanner-head {
    flex-wrap: wrap;
  }

  .scanner-status {
    margin-left: 0;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .ticket-head {
    flex-direction: column;
    gap: 10px;
  }
}

@media (max-width: 500px) {
  .scanner-card,
  .ticket-card {
    padding: 18px;
  }

  .scanner-input-wrap {
    flex-direction: column;
    align-items: stretch;
    padding: 8px;
  }

  .scanner-input {
    padding: 0 8px;
  }

  .payment-row {
    align-items: flex-start;
    flex-direction: column;
    gap: 6px;
  }

  .payment-row input {
    width: 100%;
    text-align: left;
  }
}

@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
  }
}

/* ================================
   POPUP GERBANG
================================ */

.gate-overlay {
  position: fixed;
  inset: 0;
  z-index: 100;

  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;

  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(3px);
}

.gate-card {
  width: 100%;
  max-width: 400px;
  padding: 32px 28px 26px;

  border-radius: 24px;
  background: #ffffff;
  text-align: center;
  box-shadow: 0 25px 70px rgba(15, 23, 42, 0.35);

  font-family:
    Inter,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
}

.gate-tag {
  margin: 22px 0 0;

  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.25em;
  text-transform: uppercase;
}

.is-ok .gate-tag {
  color: #2f7d5b;
}

.is-bad .gate-tag {
  color: #c0392b;
}

.gate-title {
  margin: 8px 0 0;

  color: #1b2230;
  font-size: 24px;
  font-weight: 750;
  letter-spacing: -0.3px;
}

.gate-msg {
  margin: 8px 0 0;

  color: #6a7588;
  font-size: 14px;
  line-height: 1.55;
}

.gate-detail {
  display: inline-block;
  margin: 16px 0 0;
  padding: 8px 16px;

  border-radius: 8px;
  background: #f4f6fa;
  border: 1px solid #e3e8f0;
  color: #1b2230;

  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.06em;
}

.gate-btn {
  width: 100%;
  height: 44px;
  margin-top: 24px;

  border: 0;
  border-radius: 10px;
  color: #ffffff;

  font-size: 14px;
  font-weight: 650;
  cursor: pointer;
  transition: background 0.15s;
}

.is-ok .gate-btn {
  background: #2f7d5b;
}

.is-ok .gate-btn:hover {
  background: #276b4e;
}

.is-bad .gate-btn {
  background: #c0392b;
}

.is-bad .gate-btn:hover {
  background: #a93226;
}

/* ilustrasi palang */

.gate-scene {
  position: relative;
  width: 280px;
  max-width: 100%;
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
  border: 1px solid #cbd5e1;
}

.gate-arm.is-open {
  transform: rotate(-72deg);
}

.gate-arm.is-blocked {
  box-shadow: 0 0 16px rgba(248, 113, 113, 0.6);
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
    #cbd5e1 0,
    #cbd5e1 14px,
    transparent 14px,
    transparent 26px
  );
}

@keyframes gate-shake {
  0%, 100% { transform: rotate(0deg); }
  25%      { transform: rotate(-4deg); }
  75%      { transform: rotate(3deg); }
}

/* animasi muncul popup */

.popup-enter-active,
.popup-leave-active {
  transition: opacity 0.25s ease;
}

.popup-enter-active .popup-card,
.popup-leave-active .popup-card {
  transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1) !important;
}

.popup-enter-from,
.popup-leave-to {
  opacity: 0;
}

.popup-enter-from .popup-card,
.popup-leave-to .popup-card {
  transform: translateY(18px) scale(0.96);
}

@media (prefers-reduced-motion: reduce) {
  .gate-arm.is-blocked {
    animation: none;
  }
}
</style>
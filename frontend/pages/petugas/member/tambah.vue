<script setup>
import { ref, computed } from 'vue'

import jsPDF from 'jspdf'
import QRCode from 'qrcode'

import {
  UserIcon,
  BuildingOfficeIcon,
  BanknotesIcon
} from '@heroicons/vue/24/outline'


// =========================================================
// CONFIG
// =========================================================

const config = useRuntimeConfig()


// =========================================================
// FORM
// =========================================================

const namaMember = ref('')
const namaPerusahaan = ref('')
const jumlahBayar = ref(0)

const loading = ref(false)
const error = ref('')
const success = ref('')

const hargaMember = 150000


// =========================================================
// FORMAT RUPIAH
// =========================================================

const formatRupiah = (value) => {

  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(
    Number(value || 0)
  )

}


// =========================================================
// KEMBALIAN
// =========================================================

const kembalian = computed(() => {

  const bayar = Number(jumlahBayar.value || 0)

  if (bayar <= hargaMember) {
    return 0
  }

  return bayar - hargaMember

})


// =========================================================
// UANG KURANG
// =========================================================

const uangKurang = computed(() => {

  const bayar = Number(jumlahBayar.value || 0)

  return bayar < hargaMember
    ? hargaMember - bayar
    : 0

})


// =========================================================
// BOLEH SIMPAN (tombol aktif hanya jika uang cukup)
// =========================================================

const bolehSimpan = computed(() => {

  return uangKurang.value === 0 && !loading.value

})


// =========================================================
// STATUS PEMBAYARAN
// =========================================================

const statusPembayaran = computed(() => {

  const bayar = Number(jumlahBayar.value || 0)

  if (bayar >= hargaMember) {
    return 'lunas'
  }

  return 'belum lunas'

})


// =========================================================
// HELPER INVOICE
// =========================================================

// Format "Rp 150.000" yang aman untuk PDF (tanpa karakter khusus)
const rp = (value) => {

  const angka = Math.round(Number(value || 0))

  return 'Rp ' + angka.toLocaleString('id-ID').replace(/\u00a0/g, ' ')

}

// Format tanggal "30 September 2026"
const tgl = (value) => {

  const date = value ? new Date(value) : new Date()

  const valid = isNaN(date.getTime()) ? new Date() : date

  return valid.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  })

}


// =========================================================
// BUAT & UNDUH INVOICE PDF
// =========================================================

const cetakInvoice = async (m) => {

  const pdf = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4'
  })

  const W = pdf.internal.pageSize.getWidth()
  const L = 18
  const R = W - 18

  const now = new Date()

  const noInvoice =
    'INV-' +
    now.getFullYear() +
    String(now.getMonth() + 1).padStart(2, '0') +
    '-' +
    String(m.id ?? 0).padStart(4, '0')

  // ---------------- HEADER ----------------
  pdf.setFillColor(2, 6, 23)
  pdf.rect(0, 0, W, 40, 'F')

  pdf.setTextColor(255, 255, 255)
  pdf.setFont('helvetica', 'bold')
  pdf.setFontSize(16)
  pdf.text('PARKIR PLAZA ANDALAS', L, 17)

  pdf.setFont('helvetica', 'normal')
  pdf.setFontSize(8)
  pdf.setTextColor(148, 163, 184)
  pdf.text('Jl. Dr. KRT Radjiman Widyodiningrat, Jakarta Timur', L, 24)
  pdf.text('Plaza Andalas Parking Management', L, 29)

  pdf.setFont('helvetica', 'bold')
  pdf.setFontSize(24)
  pdf.setTextColor(255, 255, 255)
  pdf.text('INVOICE', R, 22, { align: 'right' })

  // ---------------- INFO ----------------
  pdf.setTextColor(100, 116, 139)
  pdf.setFont('helvetica', 'bold')
  pdf.setFontSize(8)
  pdf.text('DITAGIHKAN KEPADA', L, 55)

  pdf.setTextColor(15, 23, 42)
  pdf.setFontSize(13)
  pdf.text(String(m.nama_member || '-'), L, 63)

  pdf.setFont('helvetica', 'normal')
  pdf.setFontSize(10)
  pdf.setTextColor(71, 85, 105)
  pdf.text(String(m.nama_perusahaan || '-'), L, 69)

  const labelX = 118
  const infoRows = [
    ['No. Invoice', noInvoice],
    ['Tanggal', tgl(m.tanggal_bayar)],
    ['Kode Member', String(m.kode_member || '-')]
  ]

  let y = 55

  infoRows.forEach(([label, value]) => {
    pdf.setFont('helvetica', 'normal')
    pdf.setFontSize(9)
    pdf.setTextColor(100, 116, 139)
    pdf.text(label, labelX, y)

    pdf.setFont('helvetica', 'bold')
    pdf.setTextColor(15, 23, 42)
    pdf.text(value, R, y, { align: 'right' })

    y += 7
  })

  // ---------------- TABEL ----------------
  const tabelY = 88

  pdf.setFillColor(241, 245, 249)
  pdf.rect(L, tabelY, R - L, 10, 'F')

  pdf.setFont('helvetica', 'bold')
  pdf.setFontSize(8)
  pdf.setTextColor(71, 85, 105)
  pdf.text('DESKRIPSI', L + 3, tabelY + 6.5)
  pdf.text('QTY', 122, tabelY + 6.5, { align: 'center' })
  pdf.text('HARGA', 158, tabelY + 6.5, { align: 'right' })
  pdf.text('JUMLAH', R - 3, tabelY + 6.5, { align: 'right' })

  const rowY = tabelY + 19

  pdf.setFont('helvetica', 'bold')
  pdf.setFontSize(10)
  pdf.setTextColor(15, 23, 42)
  pdf.text('Keanggotaan Parkir Bulanan', L + 3, rowY)

  pdf.setFont('helvetica', 'normal')
  pdf.setFontSize(8)
  pdf.setTextColor(100, 116, 139)
  pdf.text(
    'Periode: ' + tgl(m.tanggal_mulai) + ' s/d ' + tgl(m.tanggal_expired),
    L + 3,
    rowY + 5.5
  )

  const harga = Number(m.total_harga || hargaMember)

  pdf.setFont('helvetica', 'normal')
  pdf.setFontSize(10)
  pdf.setTextColor(15, 23, 42)
  pdf.text('1', 122, rowY, { align: 'center' })
  pdf.text(rp(harga), 158, rowY, { align: 'right' })
  pdf.text(rp(harga), R - 3, rowY, { align: 'right' })

  pdf.setDrawColor(226, 232, 240)
  pdf.line(L, rowY + 11, R, rowY + 11)

  // ---------------- RINGKASAN ----------------
  const sumY = rowY + 24
  const sumLabelX = 118

  const dibayar = Number(m.jumlah_bayar || 0)
  const kembali = Number(m.kembalian || 0)

  const sumRows = [
    ['Total Tagihan', rp(harga)],
    ['Uang Dibayar', rp(dibayar)],
    ['Kembalian', rp(kembali)]
  ]

  let sy = sumY

  sumRows.forEach(([label, value]) => {
    pdf.setFont('helvetica', 'normal')
    pdf.setFontSize(10)
    pdf.setTextColor(71, 85, 105)
    pdf.text(label, sumLabelX, sy)

    pdf.setFont('helvetica', 'bold')
    pdf.setTextColor(15, 23, 42)
    pdf.text(value, R - 3, sy, { align: 'right' })

    sy += 8
  })

  // Label status
  const lunas = String(m.status || '').toLowerCase().trim() === 'lunas'

  if (lunas) {
    pdf.setFillColor(220, 252, 231)
    pdf.setTextColor(22, 101, 52)
  } else {
    pdf.setFillColor(254, 243, 199)
    pdf.setTextColor(146, 64, 14)
  }

  pdf.roundedRect(R - 34, sy + 1, 34, 10, 2, 2, 'F')
  pdf.setFont('helvetica', 'bold')
  pdf.setFontSize(10)
  pdf.text(lunas ? 'LUNAS' : 'BELUM LUNAS', R - 17, sy + 7.6, {
    align: 'center'
  })

  // ---------------- QR KARTU MEMBER ----------------
  if (m.kode_member) {

    const qrImage = await QRCode.toDataURL(String(m.kode_member), {
      margin: 1,
      width: 300
    })

    pdf.addImage(qrImage, 'PNG', L, sumY - 6, 34, 34)

    pdf.setFont('helvetica', 'bold')
    pdf.setFontSize(9)
    pdf.setTextColor(15, 23, 42)
    pdf.text(String(m.kode_member), L + 17, sumY + 32, { align: 'center' })

    pdf.setFont('helvetica', 'normal')
    pdf.setFontSize(7)
    pdf.setTextColor(100, 116, 139)
    pdf.text('Scan QR ini di pintu parkir', L + 17, sumY + 37, {
      align: 'center'
    })

  }

  // ---------------- FOOTER ----------------
  const H = pdf.internal.pageSize.getHeight()

  pdf.setDrawColor(226, 232, 240)
  pdf.line(L, H - 30, R, H - 30)

  pdf.setFont('helvetica', 'bold')
  pdf.setFontSize(10)
  pdf.setTextColor(15, 23, 42)
  pdf.text('Terima kasih', W / 2, H - 22, { align: 'center' })

  pdf.setFont('helvetica', 'normal')
  pdf.setFontSize(8)
  pdf.setTextColor(100, 116, 139)
  pdf.text(
    'Invoice ini dibuat otomatis oleh sistem dan sah tanpa tanda tangan.',
    W / 2,
    H - 16,
    { align: 'center' }
  )

  pdf.save(`Invoice-${m.kode_member || noInvoice}.pdf`)

}


// =========================================================
// SIMPAN MEMBER
// =========================================================

const simpanMember = async () => {

  error.value = ''
  success.value = ''


  // =======================================================
  // VALIDASI NAMA MEMBER
  // =======================================================

  if (!namaMember.value.trim()) {

    error.value = 'Nama member wajib diisi.'

    return

  }


  // =======================================================
  // VALIDASI PERUSAHAAN
  // =======================================================

  if (!namaPerusahaan.value.trim()) {

    error.value = 'Nama perusahaan wajib diisi.'

    return

  }


  // =======================================================
  // VALIDASI JUMLAH BAYAR (HARUS MINIMAL HARGA MEMBER)
  // =======================================================

  if (Number(jumlahBayar.value || 0) < hargaMember) {

    error.value =
      `Uang kurang ${formatRupiah(uangKurang.value)}. Minimal bayar ${formatRupiah(hargaMember)}.`

    return

  }


  loading.value = true


  // Simpan salinan isi form untuk cadangan data invoice
  // (sebelum form dikosongkan).
  const cadangan = {
    nama_member: namaMember.value,
    nama_perusahaan: namaPerusahaan.value,
    total_harga: hargaMember,
    jumlah_bayar: Number(jumlahBayar.value || 0),
    kembalian: kembalian.value,
    status: statusPembayaran.value
  }


  try {

    // =====================================================
    // KIRIM DATA KE LARAVEL
    // =====================================================

    const response = await $fetch(
      `${config.public.apiBase}/member`,
      {
        method: 'POST',

        body: {

          nama_member: namaMember.value,

          nama_perusahaan: namaPerusahaan.value,

          jumlah_bayar: Number(jumlahBayar.value || 0)

        }

      }
    )


    // =====================================================
    // BERHASIL
    // =====================================================

    success.value =
      response?.message ||
      'Member berhasil ditambahkan.'


    // =====================================================
    // BUAT INVOICE PDF
    // Data dari backend dipakai lebih dulu, form sebagai cadangan.
    // Kalau pembuatan PDF gagal, member tetap tersimpan.
    // =====================================================

    try {

      await cetakInvoice({
        ...cadangan,
        ...(response?.data || {})
      })

      success.value += ' Invoice PDF telah diunduh.'

    } catch (pdfErr) {

      console.error('Gagal membuat invoice PDF:', pdfErr)

      success.value +=
        ' Namun invoice PDF gagal dibuat.'

    }


    // =====================================================
    // KOSONGKAN FORM
    // =====================================================

    namaMember.value = ''
    namaPerusahaan.value = ''
    jumlahBayar.value = 0


    // =====================================================
    // KEMBALI KE DATA MEMBER
    // =====================================================

    setTimeout(() => {

      navigateTo('/petugas/member/')

    }, 1800)


  } catch (err) {

    console.error('Gagal menambahkan member:', err)


    if (err?.data?.message) {

      error.value = err.data.message

    } else {

      error.value =
        'Member gagal ditambahkan. Pastikan Laravel sedang berjalan.'

    }

  } finally {

    loading.value = false

  }

}


// =========================================================
// KEMBALI
// =========================================================

const kembali = () => {

  navigateTo('/petugas/member/')

}

</script>


<template>

  <!-- BACKGROUND -->
  <div class="min-h-screen bg-[#eef9fc] flex items-start justify-center px-4 py-10">

    <!-- CARD -->
    <div class="w-[390px] max-w-full bg-white rounded-2xl shadow-md border border-gray-100 p-6">

      <!-- JUDUL -->
      <h1 class="text-center text-xl font-bold text-gray-800 mb-6">
        Tambah Member
      </h1>


      <!-- ERROR -->
      <div
        v-if="error"
        class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600"
      >
        {{ error }}
      </div>


      <!-- SUCCESS -->
      <div
        v-if="success"
        class="mb-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs text-green-600"
      >
        {{ success }}
      </div>


      <!-- FORM -->
      <div class="space-y-3">

        <!-- NAMA MEMBER -->
        <div class="relative">

          <UserIcon
            class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
          />

          <input
            v-model="namaMember"
            type="text"
            placeholder="Nama Member"
            class="w-full h-10 rounded-lg border border-gray-200 bg-white pl-10 pr-3 text-sm text-gray-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
          />

        </div>


        <!-- NAMA PERUSAHAAN -->
        <div class="relative">

          <BuildingOfficeIcon
            class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
          />

          <input
            v-model="namaPerusahaan"
            type="text"
            placeholder="Nama Perusahaan"
            class="w-full h-10 rounded-lg border border-gray-200 bg-white pl-10 pr-3 text-sm text-gray-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
          />

        </div>


        <!-- HARGA MEMBER -->
        <div class="rounded-lg bg-gray-100 px-3 py-2.5">

          <div class="flex items-center gap-2">

            <BanknotesIcon class="h-5 w-5 text-blue-600" />

            <div>

              <p class="text-[10px] text-gray-500">
                Harga Member / Bulan
              </p>

              <p class="text-sm font-bold text-blue-700">
                {{ formatRupiah(hargaMember) }}
              </p>

            </div>

          </div>

        </div>


        <!-- JUMLAH BAYAR -->
        <input
          v-model.number="jumlahBayar"
          type="number"
          min="0"
          placeholder="Jumlah Bayar"
          class="w-full h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
        />


        <!-- HASIL PEMBAYARAN -->
        <div class="rounded-lg bg-[#eef8fa] px-3 py-3 text-xs leading-5">

          <p>
            <span class="font-bold">Harga :</span>
            {{ formatRupiah(hargaMember) }}
          </p>

          <p>
            <span class="font-bold">Uang Bayar :</span>
            {{ formatRupiah(jumlahBayar) }}
          </p>

          <p>
            <span class="font-bold">Kembalian :</span>
            {{ formatRupiah(kembalian) }}
          </p>

          <p>
            <span class="font-bold">Status :</span>

            <span
              :class="
                statusPembayaran === 'lunas'
                  ? 'text-green-600'
                  : 'text-red-600'
              "
            >
              {{ statusPembayaran }}
            </span>
          </p>

        </div>


        <!-- PERINGATAN UANG KURANG -->
        <div
          v-if="jumlahBayar > 0 && uangKurang > 0"
          class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600"
        >
          Uang kurang {{ formatRupiah(uangKurang) }}.
          Pembayaran harus minimal {{ formatRupiah(hargaMember) }}.
        </div>


        <!-- SIMPAN -->
        <button
          type="button"
          @click="simpanMember"
          :disabled="!bolehSimpan"
          class="w-full h-10 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed"
        >

          <span v-if="loading">
            Menyimpan...
          </span>

          <span v-else>
            Simpan
          </span>

        </button>


        <!-- KEMBALI -->
        <button
          type="button"
          @click="kembali"
          class="w-full pt-1 text-xs text-gray-500 hover:text-blue-600"
        >
          Kembali
        </button>

      </div>

    </div>

  </div>

</template>
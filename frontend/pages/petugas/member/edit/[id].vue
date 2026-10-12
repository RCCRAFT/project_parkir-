<template>
    <div
        class="
            min-h-screen
            bg-slate-100
            flex
            items-center
            justify-center
            p-4
        "
    >

        <div
            class="
                bg-white
                w-full
                max-w-[380px]
                rounded-2xl
                shadow-xl
                p-5
            "
        >

            <!-- ========================================= -->
            <!-- JUDUL -->
            <!-- ========================================= -->

            <div class="text-center mb-5">

                <h1
                    class="
                        text-xl
                        font-bold
                        text-gray-800
                    "
                >
                    Pembayaran Member
                </h1>

                <p
                    class="
                        text-xs
                        text-gray-500
                        mt-1
                    "
                >
                    Perbarui pembayaran member
                </p>

            </div>


            <!-- ========================================= -->
            <!-- LOADING -->
            <!-- ========================================= -->

            <div
                v-if="loadingData"
                class="
                    py-10
                    text-center
                    text-gray-500
                    text-sm
                "
            >
                Memuat data member...
            </div>


            <!-- ========================================= -->
            <!-- FORM -->
            <!-- ========================================= -->

            <div v-else>

                <!-- ===================================== -->
                <!-- INFORMASI MEMBER -->
                <!-- ===================================== -->

                <div
                    class="
                        bg-slate-50
                        rounded-xl
                        p-4
                        mb-5
                        space-y-2
                    "
                >

                    <!-- KODE -->

                    <div class="flex text-sm">

                        <span
                            class="
                                w-[105px]
                                shrink-0
                                text-gray-500
                            "
                        >
                            Kode Member
                        </span>

                        <span
                            class="
                                font-semibold
                                text-gray-800
                                break-all
                            "
                        >
                            :
                            {{ form.kode_member || "-" }}
                        </span>

                    </div>


                    <!-- NAMA -->

                    <div class="flex text-sm">

                        <span
                            class="
                                w-[105px]
                                shrink-0
                                text-gray-500
                            "
                        >
                            Nama Member
                        </span>

                        <span
                            class="
                                font-semibold
                                text-gray-800
                                break-words
                            "
                        >
                            :
                            {{ form.nama_member || "-" }}
                        </span>

                    </div>


                    <!-- PERUSAHAAN -->

                    <div class="flex text-sm">

                        <span
                            class="
                                w-[105px]
                                shrink-0
                                text-gray-500
                            "
                        >
                            Perusahaan
                        </span>

                        <span
                            class="
                                font-semibold
                                text-gray-800
                                break-words
                            "
                        >
                            :
                            {{ form.nama_perusahaan || "-" }}
                        </span>

                    </div>


                    <!-- TAGIHAN -->

                    <div class="flex text-sm">

                        <span
                            class="
                                w-[105px]
                                shrink-0
                                text-gray-500
                            "
                        >
                            Tagihan
                        </span>

                        <span
                            class="
                                font-bold
                                text-blue-600
                            "
                        >
                            :
                            Rp
                            {{ formatRupiah(form.total_harga) }}
                        </span>

                    </div>

                </div>


                <!-- ===================================== -->
                <!-- SUDAH LUNAS BULAN INI -->
                <!-- ===================================== -->

                <div
                    v-if="sudahLunas"
                    class="
                        mb-4
                        rounded-xl
                        border
                        border-green-200
                        bg-green-50
                        px-4
                        py-3
                        text-xs
                        text-green-700
                    "
                >
                    Member ini sudah lunas untuk bulan ini.
                    Anda bisa mencetak ulang invoice-nya.
                </div>


                <!-- ===================================== -->
                <!-- INPUT PEMBAYARAN -->
                <!-- ===================================== -->

                <label
                    class="
                        block
                        text-sm
                        font-semibold
                        text-gray-700
                        mb-2
                    "
                >
                    Masukkan Pembayaran
                </label>


                <div class="relative">

                    <span
                        class="
                            absolute
                            left-3
                            top-1/2
                            -translate-y-1/2
                            text-gray-500
                            font-semibold
                        "
                    >
                        Rp
                    </span>


                    <input
                        v-model.number="form.jumlah_bayar"
                        type="number"
                        min="0"
                        :disabled="sudahLunas"
                        class="
                            w-full
                            border
                            border-gray-300
                            rounded-xl
                            py-3
                            pl-10
                            pr-3
                            focus:outline-none
                            focus:ring-2
                            focus:ring-green-500
                            focus:border-green-500
                            disabled:bg-gray-100
                        "
                        placeholder="Masukkan jumlah pembayaran"
                    />

                </div>


                <!-- ===================================== -->
                <!-- PREVIEW -->
                <!-- ===================================== -->

                <div
                    class="
                        mt-4
                        bg-gray-50
                        rounded-xl
                        p-4
                        space-y-2
                    "
                >

                    <!-- DIBAYAR -->

                    <div
                        class="
                            flex
                            justify-between
                            text-sm
                        "
                    >

                        <span class="text-gray-600">
                            Dibayar
                        </span>

                        <span class="font-semibold">
                            Rp
                            {{ formatRupiah(jumlahBayar) }}
                        </span>

                    </div>


                    <!-- TAGIHAN -->

                    <div
                        class="
                            flex
                            justify-between
                            text-sm
                        "
                    >

                        <span class="text-gray-600">
                            Tagihan
                        </span>

                        <span class="font-semibold">
                            Rp
                            {{ formatRupiah(totalHarga) }}
                        </span>

                    </div>


                    <!-- KEMBALIAN -->

                    <div
                        class="
                            flex
                            justify-between
                            text-sm
                        "
                    >

                        <span class="text-gray-600">
                            Kembalian
                        </span>

                        <span
                            class="
                                font-bold
                                text-blue-600
                            "
                        >
                            Rp
                            {{ formatRupiah(kembalian) }}
                        </span>

                    </div>


                    <!-- STATUS -->

                    <div
                        class="
                            flex
                            justify-between
                            items-center
                            pt-2
                            border-t
                            border-gray-200
                        "
                    >

                        <span class="text-gray-600">
                            Status
                        </span>


                        <span
                            class="
                                px-3
                                py-1
                                rounded-full
                                text-xs
                                font-bold
                            "
                            :class="
                                statusPembayaran === 'lunas'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'
                            "
                        >
                            {{
                                statusPembayaran === 'lunas'
                                    ? 'LUNAS'
                                    : 'BELUM LUNAS'
                            }}
                        </span>

                    </div>

                </div>


                <!-- ===================================== -->
                <!-- SIMPAN -->
                <!-- ===================================== -->

                <button
                    v-if="!sudahLunas"
                    @click="updatePembayaran"
                    :disabled="loading"
                    class="
                        mt-5
                        w-full
                        bg-green-600
                        hover:bg-green-700
                        disabled:bg-gray-400
                        text-white
                        py-3
                        rounded-xl
                        font-bold
                        flex
                        items-center
                        justify-center
                        gap-2
                    "
                >

                    <span
                        v-if="loading"
                        class="
                            w-4
                            h-4
                            border-2
                            border-white
                            border-t-transparent
                            rounded-full
                            animate-spin
                        "
                    ></span>

                    {{
                        loading
                            ? 'Menyimpan...'
                            : 'Simpan Pembayaran'
                    }}

                </button>


                <!-- ===================================== -->
                <!-- CETAK ULANG INVOICE (jika sudah lunas) -->
                <!-- ===================================== -->

                <button
                    v-if="sudahLunas"
                    @click="cetakUlangInvoice"
                    :disabled="loading"
                    class="
                        mt-5
                        w-full
                        bg-blue-600
                        hover:bg-blue-700
                        disabled:bg-gray-400
                        text-white
                        py-3
                        rounded-xl
                        font-bold
                    "
                >
                    Cetak Invoice
                </button>


                <!-- ===================================== -->
                <!-- KEMBALI -->
                <!-- ===================================== -->

                <button
                    @click="kembali"
                    :disabled="loading"
                    class="
                        mt-2
                        w-full
                        bg-gray-500
                        hover:bg-gray-600
                        disabled:bg-gray-400
                        text-white
                        py-3
                        rounded-xl
                        font-bold
                    "
                >
                    Kembali
                </button>

            </div>

        </div>

    </div>
</template>


<script setup lang="ts">

import jsPDF from "jspdf";
// @ts-ignore (paket qrcode tidak punya definisi tipe)
import QRCode from "qrcode";

// =========================================================
// API & ROUTER
// =========================================================

const { $api } = useNuxtApp() as any;

const router = useRouter();

const route = useRoute();


// =========================================================
// STATE
// =========================================================

const loading = ref(false);

const loadingData = ref(true);


// =========================================================
// FORM
// =========================================================

const form = reactive<any>({

    id: null,

    kode_member: "",

    nama_member: "",

    nama_perusahaan: "",

    total_harga: 150000,

    jumlah_bayar: 0,

    kembalian: 0,

    status: "belum lunas",

    bulan_tagihan: null,

    tanggal_bayar: null,

    tanggal_mulai: null,

    tanggal_expired: null

});


// =========================================================
// SUDAH LUNAS BULAN INI
// (dari data server, bukan dari isi input)
// =========================================================

const sudahLunas = computed(() => {

    return String(form.status || "")
        .toLowerCase()
        .trim() === "lunas";

});


// =========================================================
// TOTAL HARGA
// =========================================================

const totalHarga = computed(() => {

    return Number(
        form.total_harga || 0
    );

});


// =========================================================
// JUMLAH BAYAR
// =========================================================

const jumlahBayar = computed(() => {

    const nilai =
        Number(
            form.jumlah_bayar || 0
        );

    if (nilai < 0) {
        return 0;
    }

    return nilai;

});


// =========================================================
// KEMBALIAN
// =========================================================

const kembalian = computed(() => {

    const hasil =
        jumlahBayar.value -
        totalHarga.value;


    if (hasil > 0) {

        return hasil;

    }

    return 0;

});


// =========================================================
// STATUS (PREVIEW DARI INPUT)
// =========================================================

const statusPembayaran = computed(() => {

    if (
        jumlahBayar.value >=
        totalHarga.value
    ) {

        return "lunas";

    }

    return "belum lunas";

});


// =========================================================
// FORMAT RUPIAH
// =========================================================

const formatRupiah = (
    value: any
) => {

    return new Intl.NumberFormat(
        "id-ID"
    ).format(
        Number(value || 0)
    );

};


// =========================================================
// INVOICE PDF
// =========================================================

// =========================================================
// HELPER
// =========================================================

// "Rp 150.000" yang aman untuk PDF
const rp = (value: any) => {
  const angka = Math.round(Number(value || 0));

  return "Rp " + angka.toLocaleString("id-ID").replace(/\u00a0/g, " ");
};

// "30 September 2026"
const tgl = (value?: any) => {
  const date = value ? new Date(value) : new Date();
  const valid = isNaN(date.getTime()) ? new Date() : date;

  return valid.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });
};

// "2026-09" -> "September 2026"
const labelBulan = (bulan?: string) => {
  if (bulan && /^\d{4}-\d{2}$/.test(bulan)) {
    const [y = 0, m = 1] = bulan.split("-").map(Number);

    return new Date(y, m - 1, 1).toLocaleDateString("id-ID", {
      month: "long",
      year: "numeric",
    });
  }

  return new Date().toLocaleDateString("id-ID", {
    month: "long",
    year: "numeric",
  });
};

// =========================================================
// BUAT & UNDUH INVOICE PDF
// =========================================================
//
// Data yang dipakai (semua opsional, ada nilai cadangan):
// id, kode_member, nama_member, nama_perusahaan, total_harga,
// jumlah_bayar, kembalian, status, bulan_tagihan,
// tanggal_bayar, tanggal_mulai, tanggal_expired
//
const cetakInvoiceMember = async (m: any) => {
  const pdf = new jsPDF({
    orientation: "portrait",
    unit: "mm",
    format: "a4",
  });

  const W = pdf.internal.pageSize.getWidth();
  const H = pdf.internal.pageSize.getHeight();
  const L = 18;
  const R = W - 18;

  const now = new Date();

  const kodeBulan = /^\d{4}-\d{2}$/.test(m?.bulan_tagihan || "")
    ? String(m.bulan_tagihan).replace("-", "")
    : now.getFullYear() + String(now.getMonth() + 1).padStart(2, "0");

  const noInvoice =
    "INV-" + kodeBulan + "-" + String(m?.id ?? 0).padStart(4, "0");

  // ---------------- HEADER ----------------
  pdf.setFillColor(2, 6, 23);
  pdf.rect(0, 0, W, 40, "F");

  pdf.setTextColor(255, 255, 255);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(16);
  pdf.text("PARKIR PLAZA ANDALAS", L, 17);

  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(8);
  pdf.setTextColor(148, 163, 184);
  pdf.text("Jl. Dr. KRT Radjiman Widyodiningrat, Jakarta Timur", L, 24);
  pdf.text("Plaza Andalas Parking Management", L, 29);

  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(24);
  pdf.setTextColor(255, 255, 255);
  pdf.text("INVOICE", R, 22, { align: "right" });

  // ---------------- INFO ----------------
  pdf.setTextColor(100, 116, 139);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(8);
  pdf.text("DITAGIHKAN KEPADA", L, 55);

  pdf.setTextColor(15, 23, 42);
  pdf.setFontSize(13);
  pdf.text(String(m?.nama_member || "-"), L, 63);

  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(10);
  pdf.setTextColor(71, 85, 105);
  pdf.text(String(m?.nama_perusahaan || "-"), L, 69);

  const infoRows: [string, string][] = [
    ["No. Invoice", noInvoice],
    ["Tanggal Bayar", tgl(m?.tanggal_bayar)],
    ["Kode Member", String(m?.kode_member || "-")],
    ["Periode Tagihan", labelBulan(m?.bulan_tagihan)],
  ];

  let y = 55;

  infoRows.forEach(([label, value]) => {
    pdf.setFont("helvetica", "normal");
    pdf.setFontSize(9);
    pdf.setTextColor(100, 116, 139);
    pdf.text(label, 118, y);

    pdf.setFont("helvetica", "bold");
    pdf.setTextColor(15, 23, 42);
    pdf.text(value, R, y, { align: "right" });

    y += 7;
  });

  // ---------------- TABEL ----------------
  const tabelY = 92;

  pdf.setFillColor(241, 245, 249);
  pdf.rect(L, tabelY, R - L, 10, "F");

  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(8);
  pdf.setTextColor(71, 85, 105);
  pdf.text("DESKRIPSI", L + 3, tabelY + 6.5);
  pdf.text("QTY", 122, tabelY + 6.5, { align: "center" });
  pdf.text("HARGA", 158, tabelY + 6.5, { align: "right" });
  pdf.text("JUMLAH", R - 3, tabelY + 6.5, { align: "right" });

  const rowY = tabelY + 19;

  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(10);
  pdf.setTextColor(15, 23, 42);
  pdf.text("Pembayaran Keanggotaan Parkir Bulanan", L + 3, rowY);

  const periodeTeks = m?.tanggal_mulai
    ? "Berlaku: " + tgl(m.tanggal_mulai) + " s/d " + tgl(m.tanggal_expired)
    : m?.tanggal_expired
      ? "Berlaku sampai: " + tgl(m.tanggal_expired)
      : "Periode: " + labelBulan(m?.bulan_tagihan);

  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(8);
  pdf.setTextColor(100, 116, 139);
  pdf.text(periodeTeks, L + 3, rowY + 5.5);

  const harga = Number(m?.total_harga || 150000);

  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(10);
  pdf.setTextColor(15, 23, 42);
  pdf.text("1", 122, rowY, { align: "center" });
  pdf.text(rp(harga), 158, rowY, { align: "right" });
  pdf.text(rp(harga), R - 3, rowY, { align: "right" });

  pdf.setDrawColor(226, 232, 240);
  pdf.line(L, rowY + 11, R, rowY + 11);

  // ---------------- RINGKASAN ----------------
  const sumY = rowY + 24;

  const dibayar = Number(m?.jumlah_bayar || 0);
  const kembali = Number(m?.kembalian || 0);

  const sumRows: [string, string][] = [
    ["Total Tagihan", rp(harga)],
    ["Uang Dibayar", rp(dibayar)],
    ["Kembalian", rp(kembali)],
  ];

  let sy = sumY;

  sumRows.forEach(([label, value]) => {
    pdf.setFont("helvetica", "normal");
    pdf.setFontSize(10);
    pdf.setTextColor(71, 85, 105);
    pdf.text(label, 118, sy);

    pdf.setFont("helvetica", "bold");
    pdf.setTextColor(15, 23, 42);
    pdf.text(value, R - 3, sy, { align: "right" });

    sy += 8;
  });

  const lunas = String(m?.status || "").toLowerCase().trim() === "lunas";

  if (lunas) {
    pdf.setFillColor(220, 252, 231);
    pdf.setTextColor(22, 101, 52);
  } else {
    pdf.setFillColor(254, 243, 199);
    pdf.setTextColor(146, 64, 14);
  }

  pdf.roundedRect(R - 34, sy + 1, 34, 10, 2, 2, "F");
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(10);
  pdf.text(lunas ? "LUNAS" : "BELUM LUNAS", R - 17, sy + 7.6, {
    align: "center",
  });

  // ---------------- QR KARTU MEMBER ----------------
  if (m?.kode_member) {
    const qrImage = await QRCode.toDataURL(String(m.kode_member), {
      margin: 1,
      width: 300,
    });

    pdf.addImage(qrImage, "PNG", L, sumY - 6, 34, 34);

    pdf.setFont("helvetica", "bold");
    pdf.setFontSize(9);
    pdf.setTextColor(15, 23, 42);
    pdf.text(String(m.kode_member), L + 17, sumY + 32, { align: "center" });

    pdf.setFont("helvetica", "normal");
    pdf.setFontSize(7);
    pdf.setTextColor(100, 116, 139);
    pdf.text("Scan QR ini di pintu parkir", L + 17, sumY + 37, {
      align: "center",
    });
  }

  // ---------------- FOOTER ----------------
  pdf.setDrawColor(226, 232, 240);
  pdf.line(L, H - 30, R, H - 30);

  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(10);
  pdf.setTextColor(15, 23, 42);
  pdf.text("Terima kasih", W / 2, H - 22, { align: "center" });

  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(8);
  pdf.setTextColor(100, 116, 139);
  pdf.text(
    "Invoice ini dibuat otomatis oleh sistem dan sah tanpa tanda tangan.",
    W / 2,
    H - 16,
    { align: "center" }
  );

  pdf.save(`Invoice-${m?.kode_member || noInvoice}-${kodeBulan}.pdf`);
};

// =========================================================
// AMBIL DATA MEMBER
// =========================================================

const getData = async () => {

    try {

        loadingData.value = true;


        const response =
            await $api.get(
                `/member/${route.params.id}`
            );


        if (
            !response.data?.status
        ) {

            alert(
                response.data?.message ||
                "Data member tidak ditemukan"
            );

            router.push(
                "/petugas/member/"
            );

            return;
        }


        const data =
            response.data.data;


        form.id =
            data.id;

        form.kode_member =
            data.kode_member || "";

        form.nama_member =
            data.nama_member || "";

        form.nama_perusahaan =
            data.nama_perusahaan || "";

        form.total_harga =
            Number(
                data.total_harga || 150000
            );

        form.jumlah_bayar =
            Number(
                data.jumlah_bayar || 0
            );

        form.kembalian =
            Number(
                data.kembalian || 0
            );

        form.status =
            data.status || "belum lunas";

        form.bulan_tagihan =
            data.bulan_tagihan || null;

        form.tanggal_bayar =
            data.tanggal_bayar || null;

        form.tanggal_mulai =
            data.tanggal_mulai || null;

        form.tanggal_expired =
            data.tanggal_expired || null;

    }
    catch (error: any) {

        console.error(
            "Gagal mengambil data member:",
            error
        );


        alert(
            error?.response?.data?.message ||
            "Gagal mengambil data member"
        );


        router.push(
            "/petugas/member/"
        );

    }
    finally {

        loadingData.value = false;

    }

};


// =========================================================
// SIMPAN PEMBAYARAN  (SUDAH DIPERBAIKI)
// =========================================================

const updatePembayaran = async () => {

    const bayar = Number(form.jumlah_bayar || 0);
    const harga = Number(form.total_harga || 0);


    // -----------------------------------------------
    // VALIDASI
    // -----------------------------------------------

    if (bayar < 0) {

        alert("Pembayaran tidak boleh kurang dari Rp 0");

        return;
    }


    if (bayar < harga) {

        alert(
            `Pembayaran kurang Rp ${formatRupiah(harga - bayar)}. ` +
            `Minimal bayar Rp ${formatRupiah(harga)}.`
        );

        return;
    }


    // -----------------------------------------------
    // KONFIRMASI
    // -----------------------------------------------

    if (!confirm(`Simpan pembayaran Rp ${formatRupiah(bayar)}?`)) {
        return;
    }


    try {

        loading.value = true;


        // -------------------------------------------
        // KIRIM KE BACKEND
        // -------------------------------------------

        const response =
            await $api.put(

                `/member/${route.params.id}/pembayaran`,

                {
                    jumlah_bayar: bayar
                }

            );


        if (!response.data?.status) {

            alert(
                response.data?.message ||
                "Pembayaran gagal"
            );

            return;
        }


        // -------------------------------------------
        // SIAPKAN DATA INVOICE
        // Data backend dipakai dulu, tapi 4 field di bawah
        // DIPAKSA supaya tidak tertimpa data lama / kosong.
        // -------------------------------------------

        const dataBackend = response.data?.data || {};

        const kembaliFinal = Math.max(bayar - harga, 0);

        const dataInvoice = {
            ...form,
            ...dataBackend,
            status: "lunas",
            jumlah_bayar: bayar,
            kembalian: kembaliFinal,
            tanggal_bayar:
                dataBackend.tanggal_bayar || new Date().toISOString(),
        };

        // Sinkronkan form supaya halaman ikut berubah jadi lunas
        Object.assign(form, dataInvoice);


        // -------------------------------------------
        // CETAK INVOICE PDF
        // Kalau PDF gagal, pembayaran tetap tersimpan.
        // -------------------------------------------

        let pesanInvoice = "Invoice PDF telah diunduh.";

        try {

            await cetakInvoiceMember(dataInvoice);

        }
        catch (pdfError) {

            console.error(
                "Gagal membuat invoice PDF:",
                pdfError
            );

            pesanInvoice =
                "Namun invoice PDF gagal dibuat. " +
                "Buka lagi halaman ini untuk mencetak ulang.";

        }


        alert(
            (
                response.data?.message ||
                "Pembayaran berhasil disimpan"
            ) +
            "\n" +
            pesanInvoice
        );


        router.push(
            "/petugas/member/"
        );

    }
    catch (error: any) {

        console.error(
            "Gagal menyimpan pembayaran:",
            error
        );


        alert(
            error?.response?.data?.message ||
            "Gagal menyimpan pembayaran"
        );

    }
    finally {

        loading.value = false;

    }

};


// =========================================================
// CETAK ULANG INVOICE (member yang sudah lunas)
// =========================================================

const cetakUlangInvoice = async () => {

    try {

        await cetakInvoiceMember({ ...form });

    }
    catch (error) {

        console.error(
            "Gagal membuat invoice PDF:",
            error
        );

        alert("Invoice PDF gagal dibuat.");

    }

};


// =========================================================
// KEMBALI
// =========================================================

const kembali = () => {

    router.push(
        "/petugas/member/"
    );

};


// =========================================================
// LOAD
// =========================================================

onMounted(() => {

    getData();

});

</script>
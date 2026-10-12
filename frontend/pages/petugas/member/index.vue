<template>

    <div
        class="
            min-h-screen
            bg-gradient-to-br
            from-slate-950
            via-[#0b1120]
            to-slate-950
            p-4
            md:p-8
            text-slate-200
        "
    >

        <!-- ===================================================== -->
        <!-- HEADER -->
        <!-- ===================================================== -->

        <div
            class="
                mb-6
                flex
                flex-col
                gap-4
                sm:flex-row
                sm:items-center
                sm:justify-between
            "
        >

            <div
                class="
                    flex
                    min-w-0
                    items-center
                    gap-4
                "
            >

                <!-- KEMBALI -->

                <button
                    type="button"
                    @click="router.push('/petugas')"
                    class="
                        flex
                        shrink-0
                        items-center
                        gap-2
                        rounded-lg
                        border
                        border-slate-700
                        bg-slate-800/60
                        px-4
                        py-2.5
                        text-sm
                        font-semibold
                        text-slate-300
                        transition
                        hover:border-blue-500
                        hover:bg-slate-800
                        hover:text-blue-300
                    "
                >
                    <span class="text-lg leading-none">‹</span>
                    <span class="whitespace-nowrap">Kembali</span>
                </button>

                <div
                    class="
                        hidden
                        h-8
                        w-px
                        shrink-0
                        bg-slate-700
                        sm:block
                    "
                ></div>

                <h1
                    class="
                        truncate
                        text-2xl
                        md:text-3xl
                        font-bold
                        bg-gradient-to-r
                        from-blue-400
                        to-cyan-300
                        bg-clip-text
                        text-transparent
                    "
                >
                    Data Member
                </h1>

            </div>


            <button
                type="button"
                @click="router.push('/petugas/member/tambah')"
                class="
                    shrink-0
                    rounded-lg
                    bg-gradient-to-r
                    from-blue-600
                    to-cyan-500
                    px-5
                    py-2.5
                    text-sm
                    font-semibold
                    text-white
                    shadow-lg
                    shadow-blue-950/40
                    transition
                    hover:brightness-110
                "
            >
                + Tambah Member
            </button>

        </div>


        <!-- ===================================================== -->
        <!-- TABLE -->
        <!-- ===================================================== -->

        <div
            class="
                overflow-x-auto
                rounded-xl
                border
                border-slate-800
                bg-slate-900/70
                p-4
                shadow-xl
                shadow-black/30
                backdrop-blur
            "
        >

            <table
                class="
                    w-full
                    min-w-[1100px]
                    text-sm
                "
            >

                <thead>

                    <tr
                        class="
                            border-b
                            border-slate-800
                            bg-slate-800/40
                        "
                    >

                        <th class="p-3 text-left font-semibold text-slate-400">
                            Kode
                        </th>

                        <th class="p-3 text-left font-semibold text-slate-400">
                            Nama Member
                        </th>

                        <th class="p-3 text-left font-semibold text-slate-400">
                            Perusahaan
                        </th>

                        <th class="p-3 text-left font-semibold text-slate-400">
                            Tagihan
                        </th>

                        <th class="p-3 text-left font-semibold text-slate-400">
                            Dibayar
                        </th>

                        <th class="p-3 text-left font-semibold text-slate-400">
                            Status
                        </th>

                        <th class="p-3 text-left font-semibold text-slate-400">
                            Expired
                        </th>

                        <th class="p-3 text-center font-semibold text-slate-400">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <!-- ================================================= -->
                    <!-- DATA MEMBER -->
                    <!-- ================================================= -->

                    <tr
                        v-for="item in members"
                        :key="item.id"
                        class="
                            border-b
                            border-slate-800/70
                            transition
                            hover:bg-slate-800/40
                        "
                    >

                        <!-- KODE -->

                        <td class="p-3 text-slate-200">
                            {{ item.kode_member || '-' }}
                        </td>


                        <!-- NAMA -->

                        <td class="p-3 text-slate-200">
                            {{ item.nama_member || '-' }}
                        </td>


                        <!-- PERUSAHAAN -->

                        <td class="p-3 text-slate-400">
                            {{ item.nama_perusahaan || '-' }}
                        </td>


                        <!-- TAGIHAN -->

                        <td
                            class="
                                p-3
                                font-semibold
                                whitespace-nowrap
                                text-slate-100
                            "
                        >
                            Rp {{ formatRupiah(item.total_harga) }}
                        </td>


                        <!-- DIBAYAR -->

                        <td
                            class="
                                p-3
                                font-semibold
                                whitespace-nowrap
                                text-slate-100
                            "
                        >
                            Rp {{ formatRupiah(item.jumlah_bayar) }}
                        </td>


                        <!-- ================================================= -->
                        <!-- STATUS -->
                        <!-- ================================================= -->

                        <td class="p-3">

                            <span
                                class="
                                    inline-block
                                    whitespace-nowrap
                                    rounded-full
                                    border
                                    px-3
                                    py-1
                                    text-xs
                                    font-semibold
                                "
                                :class="
                                    isLunas(item)
                                        ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
                                        : 'bg-rose-500/10 text-rose-400 border-rose-500/30'
                                "
                            >

                                {{
                                    isLunas(item)
                                        ? 'LUNAS'
                                        : 'BELUM LUNAS'
                                }}

                            </span>

                        </td>


                        <!-- ================================================= -->
                        <!-- EXPIRED -->
                        <!-- ================================================= -->

                        <td
                            class="
                                p-3
                                whitespace-nowrap
                                text-slate-400
                            "
                        >

                            {{
                                formatTanggal(
                                    item.tanggal_expired
                                )
                            }}

                        </td>


                        <!-- ================================================= -->
                        <!-- AKSI -->
                        <!-- ================================================= -->

                        <td class="p-3">

                            <div
                                class="
                                    flex
                                    flex-nowrap
                                    items-center
                                    justify-center
                                    gap-2
                                "
                            >

                                <!-- ================================================= -->
                                <!-- DETAIL -->
                                <!-- ================================================= -->

                                <button
                                    type="button"
                                    @click.stop="
                                        lihatDetail(item.id)
                                    "
                                    class="
                                        shrink-0
                                        rounded-lg
                                        border
                                        border-blue-500/40
                                        bg-blue-600/20
                                        p-2
                                        text-blue-300
                                        transition
                                        hover:bg-blue-600/35
                                        hover:text-blue-200
                                    "
                                    title="Detail"
                                >

                                    <EyeIcon
                                        class="w-4 h-4"
                                    />

                                </button>


                                <!-- ================================================= -->
                                <!-- PEMBAYARAN -->
                                <!-- ================================================= -->

                                <button
                                    type="button"
                                    :disabled="isLunas(item)"
                                    @click.stop="
                                        !isLunas(item)
                                            ? edit(item.id)
                                            : null
                                    "
                                    :class="
                                        isLunas(item)
                                            ? `
                                                bg-slate-800
                                                text-slate-600
                                                border-slate-700
                                                cursor-not-allowed
                                            `
                                            : `
                                                bg-amber-500/20
                                                text-amber-300
                                                border-amber-500/40
                                                hover:bg-amber-500/35
                                                hover:text-amber-200
                                                cursor-pointer
                                            `
                                    "
                                    class="
                                        shrink-0
                                        rounded-lg
                                        border
                                        p-2
                                        transition
                                    "
                                    :title="
                                        isLunas(item)
                                            ? 'Member sudah lunas'
                                            : 'Pembayaran'
                                    "
                                >

                                    <PencilIcon
                                        class="w-4 h-4"
                                    />

                                </button>


                                <!-- ================================================= -->
                                <!-- HAPUS -->
                                <!-- ================================================= -->

                                <button
                                    type="button"

                                    :disabled="isLunas(item)"

                                    @click.stop="
                                        !isLunas(item)
                                            ? hapus(item.id)
                                            : null
                                    "

                                    :class="
                                        isLunas(item)
                                            ? `
                                                bg-slate-800
                                                text-slate-600
                                                border-slate-700
                                                cursor-not-allowed
                                            `
                                            : `
                                                bg-rose-600/20
                                                text-rose-300
                                                border-rose-500/40
                                                hover:bg-rose-600/35
                                                hover:text-rose-200
                                                cursor-pointer
                                            `
                                    "

                                    class="
                                        shrink-0
                                        rounded-lg
                                        border
                                        p-2
                                        transition
                                    "

                                    :title="
                                        isLunas(item)
                                            ? 'Member sudah lunas, tidak bisa dihapus'
                                            : 'Hapus member'
                                    "
                                >

                                    <TrashIcon
                                        class="w-4 h-4"
                                    />

                                </button>

                            </div>

                        </td>

                    </tr>


                    <!-- ================================================= -->
                    <!-- DATA KOSONG -->
                    <!-- ================================================= -->

                    <tr
                        v-if="
                            !loading &&
                            members.length === 0
                        "
                    >

                        <td
                            colspan="8"
                            class="
                                p-8
                                text-center
                                text-slate-500
                            "
                        >
                            Belum ada data member
                        </td>

                    </tr>


                    <!-- ================================================= -->
                    <!-- LOADING -->
                    <!-- ================================================= -->

                    <tr v-if="loading">

                        <td
                            colspan="8"
                            class="
                                p-8
                                text-center
                                text-slate-500
                            "
                        >
                            Memuat data member...
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>


        <!-- ===================================================== -->
        <!-- MODAL DETAIL MEMBER -->
        <!-- ===================================================== -->

        <div
            v-if="showModal"
            class="
                fixed
                inset-0
                z-[9999]
                flex
                items-center
                justify-center
                bg-black/70
                p-4
                backdrop-blur-sm
            "
            @click.self="tutupModal"
        >

            <div
                class="
                    relative
                    w-full
                    max-w-md
                    max-h-[90vh]
                    overflow-y-auto
                    rounded-2xl
                    border
                    border-slate-700
                    bg-slate-900
                    shadow-2xl
                    shadow-black/50
                "
            >

                <!-- ================================================= -->
                <!-- JUDUL -->
                <!-- ================================================= -->

                <div
                    class="
                        w-full
                        border-b
                        border-slate-800
                        bg-gradient-to-r
                        from-blue-500/10
                        via-cyan-400/5
                        to-transparent
                        px-6
                        py-6
                        text-center
                    "
                >

                    <h2
                        class="
                            text-2xl
                            font-bold
                            text-slate-100
                        "
                    >
                        Kartu Member
                    </h2>

                </div>


                <!-- ================================================= -->
                <!-- ISI -->
                <!-- ================================================= -->

                <div
                    class="
                        w-full
                        px-7
                        pt-7
                        pb-8
                    "
                >

                    <!-- ================================================= -->
                    <!-- DATA MEMBER -->
                    <!-- ================================================= -->

                    <div
                        class="
                            w-full
                            space-y-5
                        "
                    >

                        <!-- KODE -->

                        <div class="flex items-start">

                            <div
                                class="
                                    w-[135px]
                                    shrink-0
                                    font-bold
                                    text-slate-100
                                "
                            >
                                Kode
                            </div>

                            <div class="flex-1 text-slate-300">
                                :
                                {{ detailMember.kode_member || '-' }}
                            </div>

                        </div>


                        <!-- NAMA -->

                        <div class="flex items-start">

                            <div
                                class="
                                    w-[135px]
                                    shrink-0
                                    font-bold
                                    text-slate-100
                                "
                            >
                                Nama
                            </div>

                            <div class="flex-1 text-slate-300">
                                :
                                {{ detailMember.nama_member || '-' }}
                            </div>

                        </div>


                        <!-- PERUSAHAAN -->

                        <div class="flex items-start">

                            <div
                                class="
                                    w-[135px]
                                    shrink-0
                                    font-bold
                                    text-slate-100
                                "
                            >
                                Perusahaan
                            </div>

                            <div class="flex-1 text-slate-300">
                                :
                                {{ detailMember.nama_perusahaan || '-' }}
                            </div>

                        </div>


                        <!-- TAGIHAN -->

                        <div class="flex items-start">

                            <div
                                class="
                                    w-[135px]
                                    shrink-0
                                    font-bold
                                    text-slate-100
                                "
                            >
                                Tagihan
                            </div>

                            <div class="flex-1 text-slate-300">
                                :
                                Rp
                                {{ formatRupiah(
                                    detailMember.total_harga
                                ) }}
                            </div>

                        </div>


                        <!-- DIBAYAR -->

                        <div class="flex items-start">

                            <div
                                class="
                                    w-[135px]
                                    shrink-0
                                    font-bold
                                    text-slate-100
                                "
                            >
                                Dibayar
                            </div>

                            <div class="flex-1 text-slate-300">
                                :
                                Rp
                                {{ formatRupiah(
                                    detailMember.jumlah_bayar
                                ) }}
                            </div>

                        </div>


                        <!-- KEMBALIAN -->

                        <div class="flex items-start">

                            <div
                                class="
                                    w-[135px]
                                    shrink-0
                                    font-bold
                                    text-slate-100
                                "
                            >
                                Kembalian
                            </div>

                            <div class="flex-1 text-slate-300">
                                :
                                Rp
                                {{ formatRupiah(
                                    detailMember.kembalian
                                ) }}
                            </div>

                        </div>


                        <!-- STATUS -->

                        <div class="flex items-start">

                            <div
                                class="
                                    w-[135px]
                                    shrink-0
                                    font-bold
                                    text-slate-100
                                "
                            >
                                Status
                            </div>

                            <div
                                class="
                                    flex-1
                                    font-bold
                                "
                                :class="
                                    isLunas(detailMember)
                                        ? 'text-emerald-400'
                                        : 'text-rose-400'
                                "
                            >

                                :

                                {{
                                    isLunas(detailMember)
                                        ? 'LUNAS'
                                        : 'BELUM LUNAS'
                                }}

                            </div>

                        </div>


                        <!-- TANGGAL BAYAR -->

                        <div class="flex items-start">

                            <div
                                class="
                                    w-[135px]
                                    shrink-0
                                    font-bold
                                    text-slate-100
                                "
                            >
                                Tanggal Bayar
                            </div>

                            <div class="flex-1 text-slate-300">

                                :

                                {{
                                    formatTanggal(
                                        detailMember.tanggal_bayar
                                    )
                                }}

                            </div>

                        </div>


                        <!-- BERLAKU SAMPAI -->

                        <div class="flex items-start">

                            <div
                                class="
                                    w-[135px]
                                    shrink-0
                                    font-bold
                                    text-slate-100
                                "
                            >
                                Berlaku Sampai
                            </div>

                            <div class="flex-1 text-slate-300">

                                :

                                {{
                                    formatTanggal(
                                        detailMember.tanggal_expired
                                    )
                                }}

                            </div>

                        </div>

                    </div>


                    <!-- ================================================= -->
                    <!-- QR CODE -->
                    <!-- ================================================= -->

                    <div
                        class="
                            flex
                            w-full
                            justify-center
                            items-center
                            mt-8
                            mb-7
                        "
                    >

                        <div
                            class="
                                flex
                                items-center
                                justify-center
                                w-[200px]
                                h-[200px]
                            "
                        >

                            <img
                                v-if="qr"
                                :src="qr"
                                alt="QR Member"
                                class="
                                    block
                                    w-[180px]
                                    h-[180px]
                                    object-contain
                                    rounded-lg
                                    bg-white
                                    p-2
                                "
                            />

                            <div
                                v-else
                                class="
                                    flex
                                    items-center
                                    justify-center
                                    w-[180px]
                                    h-[180px]
                                    rounded-lg
                                    border
                                    border-slate-700
                                    bg-slate-800
                                    text-sm
                                    text-slate-400
                                "
                            >
                                QR belum tersedia
                            </div>

                        </div>

                    </div>


                    <!-- ================================================= -->
                    <!-- DOWNLOAD -->
                    <!-- ================================================= -->

                    <button
                        type="button"
                        @click="downloadMember"
                        class="
                            w-full
                            rounded-lg
                            bg-gradient-to-r
                            from-emerald-500
                            to-teal-500
                            px-4
                            py-3
                            font-semibold
                            text-slate-950
                            transition
                            hover:brightness-110
                        "
                    >
                        ⬇ Download Kartu Member
                    </button>


                    <!-- ================================================= -->
                    <!-- TUTUP -->
                    <!-- ================================================= -->

                    <button
                        type="button"
                        @click="tutupModal"
                        class="
                            mt-3
                            w-full
                            rounded-lg
                            border
                            border-slate-700
                            bg-slate-800
                            px-4
                            py-3
                            font-semibold
                            text-slate-300
                            transition
                            hover:bg-slate-700
                            hover:text-slate-100
                        "
                    >
                        Tutup
                    </button>

                </div>

            </div>

        </div>

    </div>

</template>


<script setup lang="ts">

import {
    ref,
    onMounted
} from 'vue'


import {
    EyeIcon,
    PencilIcon,
    TrashIcon
} from '@heroicons/vue/24/solid'


// =========================================================
// API
// =========================================================

const { $api } = useNuxtApp()

const router = useRouter()


// =========================================================
// STATE
// =========================================================

const members =
    ref<any[]>([])

const loading =
    ref(false)

const showModal =
    ref(false)

const detailMember =
    ref<any>({})

const qr =
    ref('')


// =========================================================
// FORMAT RUPIAH
// =========================================================

const formatRupiah = (angka: any) => {

    return new Intl.NumberFormat(
        'id-ID'
    ).format(
        Number(angka || 0)
    )

}


// =========================================================
// FORMAT TANGGAL
// =========================================================

const formatTanggal = (tanggal: any) => {

    if (!tanggal) {

        return '-'

    }


    const stringTanggal =
        String(tanggal)


    const cocok =
        stringTanggal.match(
            /^(\d{4})-(\d{2})-(\d{2})/
        )


    if (cocok) {

        return `${cocok[3]}/${cocok[2]}/${cocok[1]}`

    }


    const date =
        new Date(tanggal)


    if (
        isNaN(
            date.getTime()
        )
    ) {

        return '-'

    }


    return date.toLocaleDateString(
        'id-ID',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }
    )

}


// =========================================================
// CEK EXPIRED
// =========================================================

const sudahExpired = (member: any) => {

    if (!member?.tanggal_expired) {

        return false

    }


    const tanggalExpired =
        String(
            member.tanggal_expired
        ).substring(
            0,
            10
        )


    const sekarang =
        new Date()


    const tahun =
        sekarang.getFullYear()


    const bulan =
        String(
            sekarang.getMonth() + 1
        ).padStart(
            2,
            '0'
        )


    const hari =
        String(
            sekarang.getDate()
        ).padStart(
            2,
            '0'
        )


    const hariIni =
        `${tahun}-${bulan}-${hari}`


    return tanggalExpired < hariIni

}


// =========================================================
// CEK LUNAS
// =========================================================

const isLunas = (member: any) => {

    if (!member) {

        return false

    }


    // Kalau sudah expired,
    // jangan dianggap lunas.

    if (
        sudahExpired(member)
    ) {

        return false

    }


    const status =
        String(
            member.status || ''
        )
        .trim()
        .toLowerCase()


    if (
        status === 'lunas'
    ) {

        return true

    }


    const tagihan =
        Number(
            member.total_harga || 0
        )


    const dibayar =
        Number(
            member.jumlah_bayar || 0
        )


    if (
        tagihan > 0 &&
        dibayar >= tagihan
    ) {

        return true

    }


    return false

}


// =========================================================
// LOAD MEMBER
// =========================================================

const load = async () => {

    loading.value = true


    try {

        const res =
            await $api.get(
                '/member'
            )


        console.log(
            'DATA MEMBER:',
            res.data
        )


        if (
            !res.data
        ) {

            throw new Error(
                'Response API kosong'
            )

        }


        if (
            Array.isArray(
                res.data.data
            )
        ) {

            members.value =
                res.data.data

        }

        else if (
            Array.isArray(
                res.data
            )
        ) {

            members.value =
                res.data

        }

        else {

            members.value = []

        }

    }

    catch (error: any) {

        console.error(
            'GAGAL LOAD MEMBER:',
            error
        )


        alert(
            error?.response?.data?.message ||
            error?.message ||
            'Gagal mengambil data member'
        )

    }

    finally {

        loading.value = false

    }

}


// =========================================================
// DETAIL MEMBER
// =========================================================

const lihatDetail = async (id: number) => {

    try {

        const res =
            await $api.get(
                `/member/${id}`
            )


        console.log(
            'DETAIL RESPONSE:',
            res.data
        )


        if (
            !res.data
        ) {

            alert(
                'Data member tidak ditemukan'
            )

            return

        }


        detailMember.value =
            res.data.data ||
            res.data


        qr.value =
            detailMember.value?.qr ||
            ''


        showModal.value =
            true

    }

    catch (error: any) {

        console.error(
            'ERROR DETAIL MEMBER:',
            error
        )


        alert(
            error?.response?.data?.message ||
            error?.message ||
            'Gagal mengambil detail member'
        )

    }

}


// =========================================================
// TUTUP MODAL
// =========================================================

const tutupModal = () => {

    showModal.value =
        false

    detailMember.value =
        {}

    qr.value =
        ''

}


// =========================================================
// EDIT / PEMBAYARAN
// =========================================================

const edit = (id: number) => {

    router.push(
        `/petugas/member/edit/${id}`
    )

}


// =========================================================
// HAPUS MEMBER
// =========================================================

const hapus = async (id: number) => {

    const member =
        members.value.find(
            (item: any) =>
                Number(item.id) === Number(id)
        )


    // =====================================================
    // PENGAMAN TAMBAHAN
    // =====================================================
    // Walaupun tombol sudah disabled,
    // kita tetap cek di dalam fungsi.

    if (
        member &&
        isLunas(member)
    ) {

        alert(
            'Member yang sudah lunas tidak bisa dihapus.'
        )

        return

    }


    const yakin =
        window.confirm(
            'Yakin ingin menghapus member ini?'
        )


    if (!yakin) {

        return

    }


    try {

        await $api.delete(
            `/member/${id}`
        )


        alert(
            'Member berhasil dihapus'
        )


        await load()

    }

    catch (error: any) {

        console.error(
            'ERROR HAPUS:',
            error
        )


        alert(
            error?.response?.data?.message ||
            error?.message ||
            'Gagal menghapus member'
        )

    }

}


// =========================================================
// DOWNLOAD KARTU MEMBER
// =========================================================

const downloadMember = () => {

    if (
        !detailMember.value?.kode_member
    ) {

        alert(
            'Data member belum tersedia'
        )

        return

    }


    if (
        !qr.value
    ) {

        alert(
            'QR Code belum tersedia'
        )

        return

    }


    const canvas =
        document.createElement(
            'canvas'
        )


    const ctx =
        canvas.getContext(
            '2d'
        )


    if (!ctx) {

        alert(
            'Browser tidak mendukung canvas'
        )

        return

    }


    canvas.width =
        500

    canvas.height =
        850


    // =====================================================
    // BACKGROUND
    // =====================================================

    ctx.fillStyle =
        '#ffffff'

    ctx.fillRect(
        0,
        0,
        canvas.width,
        canvas.height
    )


    // =====================================================
    // BORDER
    // =====================================================

    ctx.strokeStyle =
        '#2563eb'

    ctx.lineWidth =
        8

    ctx.strokeRect(
        4,
        4,
        492,
        842
    )


    // =====================================================
    // JUDUL
    // =====================================================

    ctx.fillStyle =
        '#000000'

    ctx.textAlign =
        'center'

    ctx.font =
        'bold 30px Arial'

    ctx.fillText(
        'KARTU MEMBER PARKIR',
        250,
        50
    )


    // =====================================================
    // QR CODE
    // =====================================================

    const img =
        new Image()


    img.onload = () => {

        const qrSize =
            185


        const qrX =
            (
                canvas.width -
                qrSize
            ) / 2


        const qrY =
            80


        ctx.drawImage(
            img,
            qrX,
            qrY,
            qrSize,
            qrSize
        )


        // =================================================
        // DATA DI BAWAH QR
        // =================================================

        ctx.textAlign =
            'left'

        ctx.fillStyle =
            '#000000'

        ctx.font =
            '20px Arial'


        let y =
            315


        const jarak =
            40


        ctx.fillText(
            `Kode : ${
                detailMember.value.kode_member ||
                '-'
            }`,
            40,
            y
        )


        y += jarak


        ctx.fillText(
            `Nama : ${
                detailMember.value.nama_member ||
                '-'
            }`,
            40,
            y
        )


        y += jarak


        ctx.fillText(
            `Perusahaan : ${
                detailMember.value.nama_perusahaan ||
                '-'
            }`,
            40,
            y
        )


        y += jarak


        ctx.fillText(
            `Tagihan : Rp ${
                formatRupiah(
                    detailMember.value.total_harga
                )
            }`,
            40,
            y
        )


        y += jarak


        ctx.fillText(
            `Dibayar : Rp ${
                formatRupiah(
                    detailMember.value.jumlah_bayar
                )
            }`,
            40,
            y
        )


        y += jarak


        ctx.fillText(
            `Kembalian : Rp ${
                formatRupiah(
                    detailMember.value.kembalian
                )
            }`,
            40,
            y
        )


        y += jarak


        ctx.fillText(
            `Status : ${
                isLunas(
                    detailMember.value
                )
                    ? 'LUNAS'
                    : 'BELUM LUNAS'
            }`,
            40,
            y
        )


        y += jarak


        ctx.fillText(
            `Tanggal Bayar : ${
                formatTanggal(
                    detailMember.value.tanggal_bayar
                )
            }`,
            40,
            y
        )


        y += jarak


        ctx.fillText(
            `Berlaku Sampai : ${
                formatTanggal(
                    detailMember.value.tanggal_expired
                )
            }`,
            40,
            y
        )


        // =================================================
        // DOWNLOAD
        // =================================================

        const link =
            document.createElement(
                'a'
            )


        link.download =
            `member-${
                detailMember.value.kode_member
            }.png`


        link.href =
            canvas.toDataURL(
                'image/png'
            )


        link.click()

    }


    img.onerror = () => {

        alert(
            'QR Code gagal dimuat'
        )

    }


    img.src =
        qr.value

}


// =========================================================
// LOAD AWAL
// =========================================================

onMounted(() => {

    load()

})

</script>
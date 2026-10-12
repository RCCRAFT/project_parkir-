<template>

    <div
        class="
            min-h-screen
            bg-slate-100
            flex
            items-center
            justify-center
            p-5
        "
    >

        <div
            class="
                bg-white
                p-6
                rounded-2xl
                shadow-xl
                w-full
                max-w-[450px]
            "
        >

            <!-- JUDUL -->

            <h1
                class="
                    text-2xl
                    font-bold
                    mb-6
                    text-center
                "
            >
                Pembayaran Member
            </h1>


            <!-- DATA MEMBER -->

            <div
                class="
                    bg-slate-50
                    rounded-xl
                    p-4
                    mb-5
                    space-y-3
                "
            >

                <div>
                    <p class="text-xs text-gray-500">
                        Kode Member
                    </p>

                    <p class="font-bold">
                        {{ form.kode_member }}
                    </p>
                </div>


                <div>
                    <p class="text-xs text-gray-500">
                        Nama Member
                    </p>

                    <p class="font-bold">
                        {{ form.nama_member }}
                    </p>
                </div>


                <div>
                    <p class="text-xs text-gray-500">
                        Perusahaan
                    </p>

                    <p class="font-bold">
                        {{ form.nama_perusahaan }}
                    </p>
                </div>


                <div>
                    <p class="text-xs text-gray-500">
                        Tagihan Bulanan
                    </p>

                    <p class="font-bold text-blue-600 text-lg">
                        Rp {{ formatRupiah(form.total_harga) }}
                    </p>
                </div>


                <div>
                    <p class="text-xs text-gray-500">
                        Pembayaran Saat Ini
                    </p>

                    <p class="font-bold text-lg">
                        Rp {{ formatRupiah(form.jumlah_bayar) }}
                    </p>
                </div>


                <div>
                    <p class="text-xs text-gray-500">
                        Status
                    </p>

                    <span
                        class="
                            inline-block
                            px-3
                            py-1
                            rounded-full
                            text-xs
                            font-bold
                        "
                        :class="
                            statusLunas
                                ? 'bg-green-100 text-green-700'
                                : 'bg-red-100 text-red-700'
                        "
                    >
                        {{ statusLunas ? 'LUNAS' : 'BELUM LUNAS' }}
                    </span>
                </div>

            </div>


            <!-- INPUT PEMBAYARAN -->

            <label
                class="
                    block
                    font-semibold
                    mb-2
                "
            >
                Masukkan Pembayaran
            </label>


            <input
                v-model.number="jumlahBayar"
                type="number"
                min="0"
                :max="Number(form.total_harga || 0)"
                class="
                    w-full
                    p-3
                    rounded-xl
                    border
                    border-gray-300
                    mb-2
                    focus:outline-none
                    focus:ring-2
                    focus:ring-green-500
                "
                placeholder="Masukkan jumlah pembayaran"
            />


            <p class="text-sm text-gray-500 mb-5">
                Maksimal pembayaran:
                Rp {{ formatRupiah(form.total_harga) }}
            </p>


            <!-- KEMBALIAN -->

            <div
                v-if="jumlahBayar > Number(form.total_harga || 0)"
                class="
                    bg-green-50
                    rounded-xl
                    p-3
                    mb-4
                "
            >

                <p class="text-sm text-gray-500">
                    Kembalian
                </p>

                <p class="font-bold text-green-600">
                    Rp
                    {{
                        formatRupiah(
                            Number(jumlahBayar) -
                            Number(form.total_harga || 0)
                        )
                    }}
                </p>

            </div>


            <!-- PESAN -->

            <div
                v-if="
                    jumlahBayar > 0 &&
                    jumlahBayar < Number(form.total_harga || 0)
                "
                class="
                    bg-yellow-50
                    border
                    border-yellow-200
                    text-yellow-700
                    rounded-xl
                    p-3
                    mb-4
                    text-sm
                "
            >
                Pembayaran belum mencapai tagihan.
            </div>


            <!-- TOMBOL -->

            <button
                @click="updatePembayaran"
                :disabled="loading"
                class="
                    bg-green-600
                    hover:bg-green-700
                    disabled:bg-gray-400
                    text-white
                    w-full
                    py-3
                    rounded-xl
                    font-bold
                "
            >

                {{
                    loading
                        ? 'Menyimpan...'
                        : 'Simpan Pembayaran'
                }}

            </button>


            <!-- KEMBALI -->

            <button
                @click="kembali"
                class="
                    bg-gray-500
                    hover:bg-gray-600
                    text-white
                    w-full
                    py-3
                    rounded-xl
                    font-bold
                    mt-3
                "
            >
                Kembali
            </button>

        </div>

    </div>

</template>


<script setup>

const { $api } = useNuxtApp()

const router = useRouter()

const route = useRoute()


const loading = ref(false)

const jumlahBayar = ref(0)


const form = reactive({

    id: null,

    kode_member: '',

    nama_member: '',

    nama_perusahaan: '',

    total_harga: 150000,

    jumlah_bayar: 0,

    kembalian: 0,

    status: 'belum lunas',

    tanggal_bayar: null,

    tanggal_mulai: null,

    tanggal_expired: null,

    bulan_tagihan: null

})


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

const statusLunas = computed(() => {

    const bayar =
        Number(jumlahBayar.value || 0)

    const tagihan =
        Number(form.total_harga || 0)

    return bayar >= tagihan && tagihan > 0
})


/*
|--------------------------------------------------------------------------
| AMBIL MEMBER
|--------------------------------------------------------------------------
*/

const getData = async () => {

    try {

        const response =
            await $api.get(
                `/member/${route.params.id}`
            )


        if (
            !response.data ||
            response.data.status !== true
        ) {

            alert(
                response.data?.message ||
                'Data member tidak ditemukan'
            )

            router.push(
                '/petugas/member/'
            )

            return
        }


        Object.assign(
            form,
            response.data.data
        )


        jumlahBayar.value =
            Number(
                response.data.data.jumlah_bayar || 0
            )

    }

    catch (error) {

        console.error(
            'ERROR GET MEMBER:',
            error
        )

        alert(
            error?.response?.data?.message ||
            'Gagal mengambil data member'
        )

    }

}


/*
|--------------------------------------------------------------------------
| BAYAR
|--------------------------------------------------------------------------
*/

const updatePembayaran = async () => {

    const bayar =
        Number(jumlahBayar.value || 0)

    const tagihan =
        Number(form.total_harga || 0)


    if (bayar <= 0) {

        alert(
            'Masukkan jumlah pembayaran.'
        )

        return
    }


    if (bayar < tagihan) {

        alert(
            `Pembayaran kurang.\n\n` +
            `Tagihan: Rp ${formatRupiah(tagihan)}\n` +
            `Dibayar: Rp ${formatRupiah(bayar)}`
        )

        return
    }


    loading.value = true


    try {

        /*
        |--------------------------------------------------------------------------
        | KIRIM KE BACKEND
        |--------------------------------------------------------------------------
        */

        const response =
            await $api.put(

                `/member/${route.params.id}/pembayaran`,

                {
                    jumlah_bayar: bayar
                }

            )


        console.log(
            'HASIL PEMBAYARAN:',
            response.data
        )


        if (
            !response.data ||
            response.data.status !== true
        ) {

            throw new Error(
                response.data?.message ||
                'Pembayaran gagal.'
            )

        }


        /*
        |--------------------------------------------------------------------------
        | DATA HASIL BACKEND
        |--------------------------------------------------------------------------
        */

        const data =
            response.data.data


        /*
        |--------------------------------------------------------------------------
        | PASTIKAN BACKEND BENAR-BENAR LUNAS
        |--------------------------------------------------------------------------
        */

        if (
            String(data?.status || '')
                .trim()
                .toLowerCase() !== 'lunas'
        ) {

            alert(
                'Pembayaran tersimpan tetapi status belum LUNAS.'
            )

            return
        }


        /*
        |--------------------------------------------------------------------------
        | BERHASIL
        |--------------------------------------------------------------------------
        */

        alert(
            'Pembayaran berhasil!\n\n' +
            'Status: LUNAS\n' +
            'Dibayar: Rp ' +
            formatRupiah(data.jumlah_bayar) +
            '\nKembalian: Rp ' +
            formatRupiah(data.kembalian)
        )


        /*
        |--------------------------------------------------------------------------
        | KEMBALI KE DATA MEMBER
        |--------------------------------------------------------------------------
        */

        await router.push(
            '/petugas/member/'
        )

    }

    catch (error) {

        console.error(
            'ERROR PEMBAYARAN:',
            error
        )

        console.error(
            'RESPONSE ERROR:',
            error?.response?.data
        )


        alert(
            error?.response?.data?.message ||
            error?.data?.message ||
            error?.message ||
            'Pembayaran gagal.'
        )

    }

    finally {

        loading.value = false

    }

}


/*
|--------------------------------------------------------------------------
| KEMBALI
|--------------------------------------------------------------------------
*/

const kembali = () => {

    router.push(
        '/petugas/member/'
    )

}


/*
|--------------------------------------------------------------------------
| FORMAT RUPIAH
|--------------------------------------------------------------------------
*/

const formatRupiah = (angka) => {

    return new Intl.NumberFormat(
        'id-ID'
    ).format(
        Number(angka || 0)
    )

}


/*
|--------------------------------------------------------------------------
| LOAD
|--------------------------------------------------------------------------
*/

onMounted(() => {

    getData()

})

</script>
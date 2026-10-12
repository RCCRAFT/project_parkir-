<template>

  <div class="min-h-screen bg-gray-50 p-6">

    <!-- HEADER -->

    <div class="flex justify-between items-center mb-6">

      <div>

        <h1 class="text-2xl font-bold text-gray-800">
          Pembayaran Member
        </h1>

        <p class="text-sm text-gray-500 mt-1">
          Kelola tagihan member setiap bulan
        </p>

      </div>


      <button
        @click="loadMembers"
        :disabled="loading"
        class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-lg"
      >

        {{
          loading
            ? 'Memuat...'
            : '↻ Refresh'
        }}

      </button>

    </div>


    <!-- ERROR -->

    <div
      v-if="errorMessage"
      class="mb-5 bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-lg"
    >

      {{ errorMessage }}

    </div>


    <!-- TABLE -->

    <div class="bg-white rounded-xl shadow overflow-hidden">

      <div class="overflow-x-auto">

        <table class="min-w-full">

          <thead class="bg-blue-600 text-white">

            <tr>

              <th class="px-4 py-3 text-left">
                Kode Member
              </th>

              <th class="px-4 py-3 text-left">
                Nama Member
              </th>

              <th class="px-4 py-3 text-left">
                Perusahaan
              </th>

              <th class="px-4 py-3 text-left">
                Tagihan
              </th>

              <th class="px-4 py-3 text-left">
                Dibayar
              </th>

              <th class="px-4 py-3 text-left">
                Kembalian
              </th>

              <th class="px-4 py-3 text-left">
                Bulan
              </th>

              <th class="px-4 py-3 text-left">
                Tanggal Bayar
              </th>

              <th class="px-4 py-3 text-left">
                Status
              </th>

              <th class="px-4 py-3 text-center">
                Aksi
              </th>

            </tr>

          </thead>


          <tbody>

            <tr
              v-for="member in members"
              :key="member.id"
              class="border-b hover:bg-gray-50"
            >

              <td class="px-4 py-4 font-medium">
                {{ member.kode_member }}
              </td>


              <td class="px-4 py-4">
                {{ member.nama_member }}
              </td>


              <td class="px-4 py-4">
                {{ member.nama_perusahaan }}
              </td>


              <td class="px-4 py-4">
                Rp {{ rupiah(member.total_harga) }}
              </td>


              <td class="px-4 py-4">
                Rp {{ rupiah(member.jumlah_bayar) }}
              </td>


              <td class="px-4 py-4">
                Rp {{ rupiah(member.kembalian) }}
              </td>


              <td class="px-4 py-4">
                {{ formatBulan(member.bulan_tagihan) }}
              </td>


              <!-- TANGGAL BAYAR -->

              <td class="px-4 py-4 whitespace-nowrap">

                {{
                  formatTanggalWaktu(
                    member.tanggal_bayar
                  )
                }}

              </td>


              <td class="px-4 py-4">

                <span
                  :class="getStatusClass(member.status)"
                  class="inline-flex px-3 py-1 rounded-full text-xs font-bold"
                >

                  {{
                    getStatusText(
                      member.status
                    )
                  }}

                </span>

              </td>


              <td class="px-4 py-4 text-center">

                <button
                  v-if="!isLunas(member)"
                  @click="openPayment(member)"
                  class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg mr-2"
                >
                  Bayar
                </button>


                <button
                  v-else
                  disabled
                  class="bg-gray-400 text-white px-3 py-2 rounded-lg mr-2"
                >
                  Lunas
                </button>


                <button
                  @click="openDetail(member)"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg"
                >
                  Detail
                </button>

              </td>

            </tr>


            <tr v-if="loading">

              <td
                colspan="10"
                class="text-center py-10 text-gray-500"
              >

                Sedang mengambil data...

              </td>

            </tr>


            <tr
              v-if="
                !loading &&
                members.length === 0
              "
            >

              <td
                colspan="10"
                class="text-center py-10 text-gray-500"
              >

                Belum ada data member.

              </td>

            </tr>

          </tbody>

        </table>

      </div>

    </div>


    <!-- ================================================= -->
    <!-- MODAL PEMBAYARAN -->
    <!-- ================================================= -->

    <div
      v-if="showPayment"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
    >

      <div
        class="bg-white rounded-xl shadow-xl w-full max-w-md p-6"
      >

        <div class="flex justify-between items-center mb-5">

          <h2 class="text-xl font-bold">
            Pembayaran Member
          </h2>


          <button
            @click="closePayment"
            class="text-gray-500 hover:text-red-600 text-xl"
          >
            ✕
          </button>

        </div>


        <div
          v-if="selectedMember"
          class="space-y-4"
        >

          <div>

            <p class="text-sm text-gray-500">
              Kode Member
            </p>

            <p class="font-semibold">
              {{ selectedMember.kode_member }}
            </p>

          </div>


          <div>

            <p class="text-sm text-gray-500">
              Nama Member
            </p>

            <p class="font-semibold">
              {{ selectedMember.nama_member }}
            </p>

          </div>


          <div>

            <p class="text-sm text-gray-500">
              Tagihan Bulan
            </p>

            <p class="font-semibold">
              {{
                formatBulan(
                  selectedMember.bulan_tagihan
                )
              }}
            </p>

          </div>


          <div class="bg-blue-50 p-4 rounded-lg">

            <p class="text-sm text-gray-500">
              Total Tagihan
            </p>

            <p class="text-xl font-bold text-blue-700">
              Rp
              {{
                rupiah(
                  selectedMember.total_harga
                )
              }}
            </p>

          </div>


          <div>

            <label class="block text-sm font-medium mb-2">
              Jumlah Bayar
            </label>

            <input
              v-model.number="jumlahBayar"
              type="number"
              min="0"
              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Masukkan jumlah pembayaran"
            />

          </div>


          <div
            v-if="
              jumlahBayar >=
              Number(selectedMember.total_harga)
            "
            class="bg-green-50 p-4 rounded-lg"
          >

            <p class="text-sm text-gray-500">
              Kembalian
            </p>

            <p class="font-bold text-green-700">

              Rp
              {{
                rupiah(
                  Number(jumlahBayar) -
                  Number(selectedMember.total_harga)
                )
              }}

            </p>

          </div>


          <div
            v-if="
              jumlahBayar > 0 &&
              jumlahBayar <
              Number(selectedMember.total_harga)
            "
            class="bg-yellow-50 border border-yellow-200 text-yellow-700 p-3 rounded-lg text-sm"
          >

            Jumlah pembayaran belum mencapai total tagihan.

          </div>


          <div class="flex justify-end gap-2 pt-3">

            <button
              @click="closePayment"
              class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg"
            >
              Batal
            </button>


            <button
              @click="prosesPembayaran"
              :disabled="processing"
              class="px-5 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white rounded-lg"
            >

              {{
                processing
                  ? 'Memproses...'
                  : 'Bayar Sekarang'
              }}

            </button>

          </div>

        </div>

      </div>

    </div>


    <!-- ================================================= -->
    <!-- MODAL DETAIL -->
    <!-- ================================================= -->

    <div
      v-if="showDetail"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
    >

      <div
        class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto"
      >

        <div class="flex justify-between items-center mb-5">

          <h2 class="text-xl font-bold">
            Detail Member
          </h2>


          <button
            @click="closeDetail"
            class="text-gray-500 hover:text-red-600 text-xl"
          >
            ✕
          </button>

        </div>


        <div
          v-if="selectedMember"
          class="space-y-4"
        >

          <div class="text-center">

            <img
              v-if="selectedMember.qr"
              :src="selectedMember.qr"
              class="w-44 h-44 mx-auto object-contain"
              alt="QR Member"
            />

          </div>


          <div>

            <p class="text-xs text-gray-500">
              Kode Member
            </p>

            <p class="font-semibold">
              {{ selectedMember.kode_member }}
            </p>

          </div>


          <div>

            <p class="text-xs text-gray-500">
              Nama Member
            </p>

            <p class="font-semibold">
              {{ selectedMember.nama_member }}
            </p>

          </div>


          <div>

            <p class="text-xs text-gray-500">
              Perusahaan
            </p>

            <p class="font-semibold">
              {{ selectedMember.nama_perusahaan }}
            </p>

          </div>


          <div class="grid grid-cols-2 gap-4">

            <div>

              <p class="text-xs text-gray-500">
                Bulan Tagihan
              </p>

              <p class="font-semibold">
                {{
                  formatBulan(
                    selectedMember.bulan_tagihan
                  )
                }}
              </p>

            </div>


            <div>

              <p class="text-xs text-gray-500">
                Status
              </p>

              <span
                :class="
                  getStatusClass(
                    selectedMember.status
                  )
                "
                class="inline-flex px-3 py-1 rounded-full text-xs font-bold"
              >

                {{
                  getStatusText(
                    selectedMember.status
                  )
                }}

              </span>

            </div>

          </div>


          <div>

            <p class="text-xs text-gray-500">
              Tagihan
            </p>

            <p class="font-semibold">
              Rp
              {{
                rupiah(
                  selectedMember.total_harga
                )
              }}
            </p>

          </div>


          <div>

            <p class="text-xs text-gray-500">
              Dibayar
            </p>

            <p class="font-semibold">
              Rp
              {{
                rupiah(
                  selectedMember.jumlah_bayar
                )
              }}
            </p>

          </div>


          <div>

            <p class="text-xs text-gray-500">
              Kembalian
            </p>

            <p class="font-semibold">
              Rp
              {{
                rupiah(
                  selectedMember.kembalian
                )
              }}
            </p>

          </div>


          <!-- TANGGAL BAYAR -->

          <div class="bg-green-50 border border-green-200 rounded-lg p-3">

            <p class="text-xs text-gray-500">
              Tanggal Bayar
            </p>

            <p class="font-bold text-green-700">

              {{
                formatTanggalWaktu(
                  selectedMember.tanggal_bayar
                )
              }}

            </p>

          </div>


          <div>

            <p class="text-xs text-gray-500">
              Masa Berlaku
            </p>

            <p class="font-semibold">

              {{
                formatTanggal(
                  selectedMember.tanggal_mulai
                )
              }}

              -

              {{
                formatTanggal(
                  selectedMember.tanggal_expired
                )
              }}

            </p>

          </div>


          <button
            @click="closeDetail"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg"
          >
            Tutup
          </button>

        </div>

      </div>

    </div>

  </div>

</template>


<script setup>

const config =
  useRuntimeConfig()


const members =
  ref([])


const loading =
  ref(false)


const errorMessage =
  ref('')


const showPayment =
  ref(false)


const showDetail =
  ref(false)


const selectedMember =
  ref(null)


const jumlahBayar =
  ref(0)


const processing =
  ref(false)


/*
|--------------------------------------------------------------------------
| RUPIAH
|--------------------------------------------------------------------------
*/

const rupiah = (value) => {

  return Number(
    value || 0
  ).toLocaleString(
    'id-ID'
  )

}


/*
|--------------------------------------------------------------------------
| BULAN
|--------------------------------------------------------------------------
*/

const formatBulan = (bulan) => {

  if (!bulan) {
    return '-'
  }


  const bagian =
    String(bulan).split('-')


  if (
    bagian.length !== 2
  ) {

    return bulan

  }


  const tahun =
    bagian[0]


  const nomor =
    Number(bagian[1])


  const namaBulan = [

    'Januari',

    'Februari',

    'Maret',

    'April',

    'Mei',

    'Juni',

    'Juli',

    'Agustus',

    'September',

    'Oktober',

    'November',

    'Desember'

  ]


  if (
    nomor < 1 ||
    nomor > 12
  ) {

    return bulan

  }


  return `${namaBulan[nomor - 1]} ${tahun}`

}


/*
|--------------------------------------------------------------------------
| TANGGAL
|--------------------------------------------------------------------------
*/

const formatTanggal = (tanggal) => {

  if (!tanggal) {
    return '-'
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


/*
|--------------------------------------------------------------------------
| TANGGAL + JAM
|--------------------------------------------------------------------------
*/

const formatTanggalWaktu = (
  tanggal
) => {

  if (!tanggal) {
    return '-'
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


  return date.toLocaleString(
    'id-ID',
    {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',

      hour: '2-digit',
      minute: '2-digit'
    }
  )

}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

const isLunas = (
  member
) => {

  return String(
    member?.status || ''
  )
    .trim()
    .toLowerCase() === 'lunas'

}


const getStatusText = (
  status
) => {

  return String(
    status || ''
  )
    .trim()
    .toLowerCase() === 'lunas'
      ? 'LUNAS'
      : 'BELUM LUNAS'

}


const getStatusClass = (
  status
) => {

  return String(
    status || ''
  )
    .trim()
    .toLowerCase() === 'lunas'
      ? 'bg-green-100 text-green-700'
      : 'bg-red-100 text-red-700'

}


/*
|--------------------------------------------------------------------------
| LOAD
|--------------------------------------------------------------------------
*/

const loadMembers =
  async () => {

    loading.value =
      true

    errorMessage.value =
      ''


    try {

      const response =
        await $fetch(
          `${config.public.apiBase}/member`,
          {
            method: 'GET'
          }
        )


      if (
        response?.status === true
      ) {

        members.value =
          Array.isArray(
            response.data
          )
            ? response.data
            : []

      } else {

        members.value =
          []

        errorMessage.value =
          response?.message ||
          'Data member tidak ditemukan.'

      }

    }

    catch (error) {

      console.error(
        'ERROR LOAD:',
        error
      )


      errorMessage.value =
        error?.data?.message ||
        'Gagal mengambil data member.'

    }

    finally {

      loading.value =
        false

    }

}


/*
|--------------------------------------------------------------------------
| OPEN PAYMENT
|--------------------------------------------------------------------------
*/

const openPayment = (
  member
) => {

  selectedMember.value = {
    ...member
  }


  jumlahBayar.value =
    0


  showPayment.value =
    true

}


/*
|--------------------------------------------------------------------------
| CLOSE PAYMENT
|--------------------------------------------------------------------------
*/

const closePayment = () => {

  showPayment.value =
    false

  selectedMember.value =
    null

  jumlahBayar.value =
    0

}


/*
|--------------------------------------------------------------------------
| PROSES PEMBAYARAN
|--------------------------------------------------------------------------
*/

const prosesPembayaran =
  async () => {

    if (
      !selectedMember.value
    ) {

      return

    }


    const bayar =
      Number(
        jumlahBayar.value || 0
      )


    const tagihan =
      Number(
        selectedMember.value.total_harga || 0
      )


    if (
      bayar <= 0
    ) {

      alert(
        'Masukkan jumlah pembayaran.'
      )

      return

    }


    if (
      bayar < tagihan
    ) {

      alert(
        `Pembayaran kurang.\n\n` +
        `Tagihan: Rp ${rupiah(tagihan)}\n` +
        `Dibayar: Rp ${rupiah(bayar)}`
      )

      return

    }


    processing.value =
      true


    try {

      /*
      |--------------------------------------------------------------------------
      | PENTING:
      | API MENGGUNAKAN PUT
      |--------------------------------------------------------------------------
      */

      const response =
        await $fetch(

          `${config.public.apiBase}/member/${selectedMember.value.id}/pembayaran`,

          {

            method: 'PUT',

            body: {

              jumlah_bayar:
                bayar

            }

          }

        )


      console.log(
        'HASIL PEMBAYARAN:',
        response
      )


      if (
        response?.status !== true
      ) {

        throw new Error(
          response?.message ||
          'Pembayaran gagal.'
        )

      }


      /*
      |--------------------------------------------------------------------------
      | AMBIL DATA HASIL
      |--------------------------------------------------------------------------
      */

      const data =
        response.data


      /*
      |--------------------------------------------------------------------------
      | UPDATE TABLE
      |--------------------------------------------------------------------------
      */

      const index =
        members.value.findIndex(
          item =>
            Number(item.id) ===
            Number(data.id)
        )


      if (
        index !== -1
      ) {

        members.value[index] = {

          ...members.value[index],

          ...data

        }

      }


      /*
      |--------------------------------------------------------------------------
      | TUTUP PAYMENT
      |--------------------------------------------------------------------------
      */

      closePayment()


      /*
      |--------------------------------------------------------------------------
      | REFRESH DARI DATABASE
      |--------------------------------------------------------------------------
      */

      await loadMembers()


      /*
      |--------------------------------------------------------------------------
      | PESAN
      |--------------------------------------------------------------------------
      */

      alert(
        `Pembayaran berhasil!\n\n` +

        `Status: LUNAS\n` +

        `Dibayar: Rp ${rupiah(
          data.jumlah_bayar
        )}\n` +

        `Kembalian: Rp ${rupiah(
          data.kembalian
        )}\n` +

        `Tanggal Bayar: ${
          formatTanggalWaktu(
            data.tanggal_bayar
          )
        }`
      )

    }

    catch (error) {

      console.error(
        'ERROR PEMBAYARAN:',
        error
      )


      alert(
        error?.data?.message ||
        error?.message ||
        'Pembayaran gagal.'
      )

    }

    finally {

      processing.value =
        false

    }

}


/*
|--------------------------------------------------------------------------
| DETAIL
|--------------------------------------------------------------------------
*/

const openDetail =
  async (member) => {

    try {

      const response =
        await $fetch(

          `${config.public.apiBase}/member/${member.id}`,

          {
            method: 'GET'
          }

        )


      if (
        response?.status === true
      ) {

        selectedMember.value = {
          ...response.data
        }

      } else {

        selectedMember.value = {
          ...member
        }

      }

    }

    catch (error) {

      console.error(
        'ERROR DETAIL:',
        error
      )


      selectedMember.value = {
        ...member
      }

    }


    showDetail.value =
      true

}


/*
|--------------------------------------------------------------------------
| CLOSE DETAIL
|--------------------------------------------------------------------------
*/

const closeDetail =
  () => {

    showDetail.value =
      false

    selectedMember.value =
      null

}


/*
|--------------------------------------------------------------------------
| LOAD AWAL
|--------------------------------------------------------------------------
*/

onMounted(() => {

  loadMembers()

})

</script>
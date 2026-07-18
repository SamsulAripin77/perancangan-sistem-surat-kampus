ok pertanyaan bagaimana jika di masa depan sistem multi unit atau multi admin atau multi role di terapkan dimana 1 user admin unit hanya bisa mengelola surat di unit dia saja atau bahkan 1 admin bisa melakukan disposisi ke unit lain contoh cara kerja nya mungkin nanti masing-masing program studi atau unit atau pejabat mengelola surat dia sendiri,bagaimana saran anda terkait sistem ini, dan bagaimana cara kerja di phase 1

    kalo saya sih simple nya selain admin dia hanya bisa lihat surat yang dia crate atau yang ada di unit dia saja,btw nantinya saya akan menggunakan spatie permission bagus nya gimana ?

ok ada pertanyaan bukankah yang nama nya surat keluar itu surat yang dibuat oleh kampus dan disimpan hasil scan nya ke arsip, tapi bagaimana dengan sistem kita dimana admin sebenarnya bisa cetak surat dari sistem bukan cuma buat surat permohonan tapi secara general untuk berbagai surat yang biasa dibuat di word bahkan dokumen lain selama itu pake sistem placeholder, nah gimana untuk surat keluar yang dibuat dari sistem apakah flow nya di cetak dulu baru input scan manual sebagai surat masuk atau gimana ?


Jelaskan cara kerja masa berlaku surat di sisem opensid dan sistem surat kampus kita


flow kasar cara kerja: 

## menu dafar template & tambah template
    - http://ciengang.test/index.php/surat_master
    - role : admin
    - input klasifikasi surat/kategori, input nama surat, upload template, checkbox persyaratan, input data tambahan, masa berlaku, sediakan di layanan mandiri bolean
    - bisa simulasi input untuk check placeholder
    - bisa download template yang sudah diupload juga
    - untuk template format bisa docx dan .rtf

## menu cetak surat
    - http://ciengang.test/index.php/surat
    - ini cetak surat oleh admin untuk berbagai keperluan
    - ini one-to-one dengan daftar template
    - kalo di opensid kebanyak flow nya pilih nama mahasiwa dulu tapi bagiamana dengan disini
    - terus hasil nya masuk ke data surat cetak saya rasa menyatu dengan permohonan bedanya hanya tidak punya permohonan id saja
    - untuk masa berlau bisa dioverride
    - untuk sistem penanda tangan bisa suport lebih dari satu penda tangan dengan sistem cerdas mendeteksi placeholder untuk tdd seperti untuk nama, jabatan, nidn, foto ttd
    - harus di catat surat ini di crate oleh admin siapa
    - untuk dropdown nama, jawaban, nidn otomatis mapping berdasarkan data jabatan tapi tidak dependen dalam artian di simpan dalam snapshot nya, dalam artian jika saya pilih ttd_nama_1 maka jabatan dan nidn otomatis terisi tapi tetap bisa di ganti atau bisa diedit langsung ( hemm bisakan sistem nya dropdown tapi bisa di edit text)

## menu arsip/surat cetak/log surat
    - http://ciengang.test/index.php/keluar
    - isi nya log surat/ surat tercetak / campuran dari permohonan dan cetak surat
    - ada filter jenis surat / kategori surat/ template surat

## menu daftar pesyaratan 
    - http://ciengang.test/index.php/surat_mohon
    - list daftar persyaratan surat

## surat keluar : 
    - http://ciengang.test/index.php/surat_keluar
    - field : nomor, tgl, ditujukan kepada, isi singkat ( deskripsi), file scan, kategori
    - kaya nya perlu log perubahan kalo berkas scan diganti

## surat masuk : 
    - http://ciengang.test/index.php/surat_masuk
    - field : tanggal terima,tgl surat,  no surat, pengirim, isi singkat, dispoisisi (multi checkbox), isi disposisi

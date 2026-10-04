/*
 Navicat Premium Dump SQL

 Source Server         : DEV - 40.22
 Source Server Type    : PostgreSQL
 Source Server Version : 140017 (140017)
 Source Host           : localhost:5432
 Source Catalog        : db_smartloannextgeneration
 Source Schema         : public

 Target Server Type    : PostgreSQL
 Target Server Version : 140017 (140017)
 File Encoding         : 65001

 Date: 29/09/2026 13:23:41
*/


-- ----------------------------
-- Table structure for cfg_jnsproduk
-- ----------------------------
DROP TABLE IF EXISTS "public"."cfg_jnsproduk";
CREATE TABLE "public"."cfg_jnsproduk" (
  "jnsprodukid" int4 NOT NULL,
  "jnsproduknm" varchar(50) COLLATE "pg_catalog"."default",
  "deskripsi" text COLLATE "pg_catalog"."default",
  "image" varchar(150) COLLATE "pg_catalog"."default",
  "sts_aktif" int2 DEFAULT 0,
  "short_desc" varchar(250) COLLATE "pg_catalog"."default"
)
;

-- ----------------------------
-- Records of cfg_jnsproduk
-- ----------------------------
INSERT INTO "public"."cfg_jnsproduk" VALUES (2, 'KUR', 'program pembiayaan bagi pelaku UMKM yang membutuhkan modal usaha dengan bunga ringan dan tanpa agunan tambahan.', 'https://192.168.40.22/smartloan_nextgeneration/fe/assets/img/gallery/poster-kmg.jpg', 0, NULL);
INSERT INTO "public"."cfg_jnsproduk" VALUES (5, 'PRA PENSIUN', 'Kredit yang diberikan secara perseorangan kepada PNS Aktif Otonom Daerah (Pemprov/Pemko/Pemkab se-Sumatera Utara) yang akan memasuki masa pensiun dan akan memiliki Surat Keputusan Pensiun (SKEP) yang pembayaran gajinya melalui PT Bank Sumut.', 'https://smartloan-nextgeneration.banksumut.co.id/fe/assets/img/gallery/poster-prapensiun.jpg', 1, 'kredit yang diberikan secara perseorangan kepada PNS Aktif Otonom Daerah (Pemprov/Pemko/Pemkab se-Sumatera Utara) yang akan memasuki masa pensiun dan akan memiliki Surat Keputusan Pensiun (SKEP) yang pembayaran gajinya melalui PT Bank Sumut');
INSERT INTO "public"."cfg_jnsproduk" VALUES (6, 'PENSIUN', 'Kredit yang diberikan secara perseorangan  kepada para penerima manfaat pensiun  yang telah memiliki Surat Keputusan Pensiun (SKEP).', 'https://smartloan-nextgeneration.banksumut.co.id/fe/assets/img/gallery/poster-pensiun.jpg', 1, 'Kredit yang diberikan secara perseorangan kepada para penerima manfaat pensiun yang telah memiliki Surat Keputusan Pensiun (SKEP) dari Instansi/Lembaga/BUMN/BUMD/BHMN/BLUD/Perusahaan tempatnya bekerja.');
INSERT INTO "public"."cfg_jnsproduk" VALUES (1, 'KMG/PMG', 'Kredit Multi Guna disingkat dengan KMG yang selanjutnya cukup disebut dengan kredit adalah kredit yang diberikan secara perseorangan kepada Pegawai dengan kriteria CPNS, PNS, PPPK, Pegawai tetap di Dinas/Instansi/Lembaga/ BUMN/BUMD/BHMN/BLUD/ Perusahaan Swasta, Pejabat Publik, Lembaga Negara/Daerah, Direksi BUMN/BUMD, Komisaris/Dewan Pengawas BUMN/BUMD, Kepala Desa, Perangkat Desa, Kepala Lingkungan dan Badan Permusyawaratan Desa (BPD) yang sumber pengembaliannya dari penghasilan tetap setiap bulannya dan/atau penghasilan lainnya, dengan tujuan untuk membiayai keperluan yang bersifat konsumtif, investasi atau modal kerja yang permohonan kreditnya langsung secara individu atau melalui persetujuan Dinas/ Instansi/ Lembaga/Perusahaan Swasta tempat pemohon bertugas.', 'https://smartloan-nextgeneration.banksumut.co.id/fe/assets/img/gallery/poster-kmg2.jpg', 1, 'Kredit yang diberikan secara perseorangan kepada Pegawai dengan kriteria CPNS, PNS, PPPK, Pegawai tetap di Dinas/Instansi/Lembaga/ BUMN/BUMD/BHMN/BLUD/ Perusahaan Swasta, Pejabat Publik, Lembaga Negara/Daerah, Direksi BUMN/BUMD');
INSERT INTO "public"."cfg_jnsproduk" VALUES (12, 'MONITORING KREDIT', NULL, NULL, 0, NULL);
INSERT INTO "public"."cfg_jnsproduk" VALUES (11, 'BUCKET  NOMINATIF', NULL, NULL, 0, NULL);
INSERT INTO "public"."cfg_jnsproduk" VALUES (13, 'KUR BERKAH', NULL, NULL, 1, NULL);
INSERT INTO "public"."cfg_jnsproduk" VALUES (14, 'KMG MERDEKA', 'Kredit Multi Guna disingkat dengan KMG yang selanjutnya cukup disebut dengan kredit adalah kredit yang diberikan secara perseorangan kepada Pegawai dengan kriteria CPNS, PNS, PPPK, Pegawai tetap di Dinas/Instansi/Lembaga/ BUMN/BUMD/BHMN/BLUD/ Perusahaan Swasta, Pejabat Publik, Lembaga Negara/Daerah, Direksi BUMN/BUMD, Komisaris/Dewan Pengawas BUMN/BUMD, Kepala Desa, Perangkat Desa, Kepala Lingkungan dan Badan Permusyawaratan Desa (BPD) yang sumber pengembaliannya dari penghasilan tetap setiap bulannya dan/atau penghasilan lainnya, dengan tujuan untuk membiayai keperluan yang bersifat konsumtif, investasi atau modal kerja yang permohonan kreditnya langsung secara individu atau melalui persetujuan Dinas/ Instansi/ Lembaga/Perusahaan Swasta tempat pemohon bertugas.', 'https://smartloan-nextgeneration.banksumut.co.id/fe/assets/img/gallery/poster-kmg2.jpg', 1, 'Kredit yang diberikan secara perseorangan kepada Pegawai dengan kriteria CPNS, PNS, PPPK, Pegawai tetap di Dinas/Instansi/Lembaga/ BUMN/BUMD/BHMN/BLUD/ Perusahaan Swasta, Pejabat Publik, Lembaga Negara/Daerah, Direksi BUMN/BUMD');

-- ----------------------------
-- Indexes structure for table cfg_jnsproduk
-- ----------------------------
CREATE INDEX "idx_cfg_jnsproduk" ON "public"."cfg_jnsproduk" USING btree (
  "jnsproduknm" COLLATE "pg_catalog"."default" "pg_catalog"."text_ops" ASC NULLS LAST
);

-- ----------------------------
-- Primary Key structure for table cfg_jnsproduk
-- ----------------------------
ALTER TABLE "public"."cfg_jnsproduk" ADD CONSTRAINT "cfg_jnsproduk_pkey" PRIMARY KEY ("jnsprodukid");

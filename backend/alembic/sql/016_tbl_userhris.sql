CREATE TABLE IF NOT EXISTS tbl_userhris (
    userid VARCHAR(50) PRIMARY KEY,
    npp VARCHAR(50),
    nrik VARCHAR(50),
    nama VARCHAR(200),
    no_hp VARCHAR(50),
    user_email VARCHAR(150),
    id_unit_kerja VARCHAR(50),
    nm_unit_kerja VARCHAR(200),
    branchid VARCHAR(50),
    id_jabatan VARCHAR(50),
    nm_jabatan VARCHAR(200),
    id_kel_jabatan VARCHAR(50),
    nama_kel_jabatan VARCHAR(200),
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    stsauth INTEGER DEFAULT 0,
    secret_key TEXT,
    stsbest INTEGER DEFAULT 0,
    password TEXT
);

CREATE INDEX IF NOT EXISTS idx_tbl_userhris_npp ON tbl_userhris(npp);
CREATE INDEX IF NOT EXISTS idx_tbl_userhris_branchid ON tbl_userhris(branchid);
CREATE INDEX IF NOT EXISTS idx_tbl_userhris_id_unit_kerja ON tbl_userhris(id_unit_kerja);

-- Insert initial sample from docs/tbl_userhris.sql
INSERT INTO tbl_userhris (
    userid, npp, nrik, nama, no_hp, user_email,
    id_unit_kerja, nm_unit_kerja, branchid, id_jabatan, nm_jabatan,
    id_kel_jabatan, nama_kel_jabatan, created_at, updated_at,
    stsauth, secret_key, stsbest, password
) VALUES (
    'u2870', '2870', '2870.20111985.01042013', 'Denovriyanto Harefa', '085372803253',
    'denovriyantoharefa@banksumut.co.id', '665', 'Divisi Teknologi Informasi', '001',
    '6406', 'IT Solution & Development Non Government Specialist', '251',
    'Professional Assistant Manager', '2025-08-13 10:38:40.792514', '2026-09-24 16:45:37.010854',
    0, NULL, 0, NULL
) ON CONFLICT (userid) DO NOTHING;

-- Insert sample 1776 converted from /hris/inqMasterPegawaiByKondisi
INSERT INTO tbl_userhris (
    userid, npp, nrik, nama, no_hp, user_email,
    id_unit_kerja, nm_unit_kerja, branchid, id_jabatan, nm_jabatan,
    id_kel_jabatan, nama_kel_jabatan, created_at, updated_at,
    stsauth, secret_key, stsbest, password
) VALUES (
    'u1776', '1776', '1776.13081982.15032008', 'Faisal Agus Nugraha', '085275876482',
    'faisal.nugraha@banksumut.co.id', '665', 'Divisi Teknologi Informasi', '001',
    '6455', 'Pemimpin Bidang Pengembangan Aplikasi & Sistem Integrasi Non Government', '170',
    'Pemimpin Bidang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP,
    0, NULL, 0, '0bf7669faf2fa3d7ea7adab06456ff29'
) ON CONFLICT (userid) DO NOTHING;

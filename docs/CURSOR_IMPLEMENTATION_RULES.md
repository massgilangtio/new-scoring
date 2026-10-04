# CURSOR IMPLEMENTATION RULES

## Mandatory Technology
- Frontend/UI: CodeIgniter 4 + PHP
- Backend/API: Python + FastAPI
- Database: PostgreSQL

## Responsibility
### CI4
Presentation, routing halaman, form, UI component, API client, rendering.

### Python FastAPI
Business logic, validation, scoring engine, workflow, approval, configuration, versioning, audit, notification, reporting.

### PostgreSQL
Persistent transactional/configuration/historical storage.

## Communication
`CI4 -> REST API/JSON -> FastAPI -> PostgreSQL`

Business logic jangan diduplikasi di CI4.

## Scoring
- Engine berada di FastAPI.
- `Value × Weight`.
- Total weight = 100 sebelum activation.
- Tidak ada manual score/result override.
- Configuration wajib versioned.

## Database
- PostgreSQL.
- Perubahan schema melalui migration.
- PK/FK/unique/index sesuai kebutuhan.
- Historical data tidak berubah akibat konfigurasi baru.

## Security
- Authorization wajib ditegakkan di backend.
- Branch restriction wajib di backend.
- Jangan percaya role/branch dari frontend.
- Jangan hard-code password/token/secret/API key.
- Action penting wajib diaudit.

## Development Workflow
1. Analisis requirement.
2. Identifikasi impact.
3. Tampilkan rencana.
4. Jika menyangkut business behavior, minta konfirmasi.
5. Implementasi.
6. Test.
7. Security review.
8. Dokumentasi.

Jangan mengerjakan seluruh aplikasi sekaligus.

# CURSOR MASTER PROMPT — New Scoring Credit System

## 1. TECHNOLOGY STACK — WAJIB

Gunakan stack berikut sebagai keputusan teknologi utama dan jangan menggantinya tanpa instruksi eksplisit:

### Frontend / Presentation
- CodeIgniter 4 (CI4)
- PHP
- Bertanggung jawab atas UI, page routing, form, session sesuai kebutuhan, rendering, dan pemanggilan REST API.

### Backend Service / API
- Python
- FastAPI
- Bertanggung jawab atas business logic, REST API, Scoring Engine, validation, workflow, approval, versioning, audit, notification, dan reporting.

### Database
- PostgreSQL
- Menjadi persistent data storage utama.

### Arsitektur
```text
Browser
   |
   v
CodeIgniter 4 (Frontend / Presentation)
   |
   | REST API / JSON
   v
Python FastAPI (Backend Service)
   |
   v
PostgreSQL
```

### Aturan
1. Jangan mengganti CI4 dengan Next.js/React/Laravel/Vue.
2. Jangan mengganti Python FastAPI dengan Node.js atau backend lain.
3. Jangan mengganti PostgreSQL dengan MySQL/MariaDB/SQLite.
4. Business logic utama berada di Python Backend.
5. Scoring Engine wajib berada di Python Backend.
6. CI4 tidak menghitung score dan tidak menduplikasi business rules backend.
7. CI4 berkomunikasi dengan backend melalui REST API/JSON.

## 2. SOURCE OF TRUTH
- `PRD_New_Scoring_Credit_System.pdf` = Business Requirement
- `BUSINESS_RULES.md` = Business Rules
- `CURSOR_MASTER_PROMPT.md` = Architecture & Development Direction
- `CURSOR_IMPLEMENTATION_RULES.md` = Technical Rules
- `CURSOR_TASKS.md` = Development Sequence

Jika ada ambiguity bisnis, jangan mengarang. Tandai `[NEED BUSINESS CONFIRMATION]`.

## 3. SEBELUM CODING
Jangan langsung coding. Baca seluruh `/docs`, lalu analisis:
- struktur CI4
- struktur Python/FastAPI
- PostgreSQL dan tabel existing
- authentication/RBAC
- API
- reusable components
- module existing
- konflik dan gap terhadap PRD

Output:
1. Repository Summary
2. Current Technology Stack
3. CI4 Architecture
4. Python FastAPI Architecture
5. PostgreSQL Structure
6. Existing Modules/API
7. Authentication & RBAC
8. Reusable Components
9. Gap Analysis
10. Risks/Conflicts
11. Target Architecture
12. Proposed Database/ERD
13. API Architecture
14. UI/Menu Architecture
15. Development Roadmap
16. Business Confirmation Needed

Setelah itu STOP dan tunggu instruksi.

## 4. CORE RULES
- Scoring Engine configurable, bukan hard-coded.
- Formula MVP: `Score Parameter = Value × Weight`.
- `Total Score = SUM(Score Parameter)`.
- Total weight per product/version harus 100 sebelum activation.
- Score/result tidak boleh di-override manual.
- Historical scoring harus menyimpan snapshot yang diperlukan.
- Jangan hard-code product, role, permission, branch, atau scoring parameter.
- Jangan bypass workflow.
- Jangan menghapus audit trail.
- Jangan menyimpan secret/API key di source code.

# New Scoring Credit System — Cursor Development Pack

## Technology Stack
- Frontend/UI: **CodeIgniter 4 (CI4) + PHP**
- Backend Service/API: **Python + FastAPI**
- Database: **PostgreSQL**

## Architecture
```text
Browser
   |
   v
CodeIgniter 4
   |
   | REST API / JSON
   v
Python FastAPI
   |
   v
PostgreSQL
```

## Urutan membaca
1. PRD_New_Scoring_Credit_System.pdf
2. BUSINESS_RULES.md
3. CURSOR_MASTER_PROMPT.md
4. CURSOR_IMPLEMENTATION_RULES.md
5. CURSOR_TASKS.md
6. README.md

## Cara menggunakan
Letakkan dokumen di `/docs` repository.
Gunakan `CURSOR_MASTER_PROMPT.md` sebagai prompt awal.
Cursor wajib melakukan repository analysis sebelum coding.

## Prinsip utama
CI4 menangani presentation/UI.
Python FastAPI menangani business logic/API/scoring.
PostgreSQL menjadi database utama.
Scoring Engine berada di Python.
Historical scoring immutable.
Workflow dan audit ditegakkan di backend.

# BUSINESS RULES — New Scoring Credit System

## Scoring
- Parameter scoring configurable per product/version.
- MVP menggunakan Choice/Category.
- Score Parameter = Value × Weight.
- Total Score = SUM seluruh Score Parameter.
- Total Weight harus = 100 sebelum activation.
- Semua scoring parameter wajib diisi.
- Score/result tidak boleh diubah manual.
- Threshold berasal dari configuration.

## Version
- Tepat satu active scoring version per product.
- Version lama tetap tersimpan.
- Historical transaction menggunakan version saat transaksi.
- Perubahan configuration tidak boleh mengubah historical result.

## Dynamic Fields
MVP: Text, Number, Date, Dropdown, Textarea, Radio/Single Choice, Checkbox/Multiple Choice.
Memiliki mandatory/optional, active/inactive, dan order.

## Debtor
- NIK unique dan tepat 16 digit numerik.
- Import Excel mendukung upsert berdasarkan NIK.
- Reviewer/Approver terbatas branch sendiri.
- Admin IT dapat seluruh branch.

## Transaction & Workflow
- Reviewer memilih product dan debtor.
- Dua wizard: Input Scoring dan Review & Confirmation.
- Submit mengunci transaction.
- Status: Draft, Submitted, Waiting for Approver Assignment, Approved, Returned, Rejected.
- Approve/Reject final; Return dapat diperbaiki lalu resubmit.
- Approval note wajib.

## Approval
- Approver aktif harus dari branch yang sama.
- Reassignment sebelum decision membutuhkan reason dan audit.

## Duplicate
Jika setting aktif, Approved/Rejected dapat diduplikasi.
Transaction baru memakai active version saat duplicate dibuat.
Source transaction immutable dan relasi "Duplicated From" disimpan.

## Request Scoring Ulang
Jika NIK + product sudah memiliki scoring, tampilkan warning.
Request membutuhkan reason dan approval satu kali.
Setelah transaksi baru dibuat, request menjadi Consumed.

## Audit
Simpan actor, role, timestamp, action, object, before/after, dan reason jika diperlukan.
Audit tidak boleh dihapus/diubah secara diam-diam.

## Notification
In-app wajib. WhatsApp optional.
Event: sent to approver, approved, returned, rejected.

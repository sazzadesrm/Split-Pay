# Receipt Storage and Access Control

## Upload validation (`App\Services\ReceiptUploadService`)

1. Reject on any non-`UPLOAD_ERR_OK` PHP upload error.
2. Reject files over 10 MB (`MAX_RECEIPT_UPLOAD_SIZE`).
3. Detect the real MIME type server-side with `finfo(FILEINFO_MIME_TYPE)` — the client-supplied MIME type and file extension are never trusted. Only `image/jpeg`, `image/png`, `image/webp` and `application/pdf` are accepted.
4. Generate a random storage filename: `bin2hex(random_bytes(24))` plus the extension implied by the validated MIME type. The original filename is stored only as metadata (`original_filename`), never used as a path.
5. Store the file under `storage/receipts/YYYY/MM/<random>.<ext>`, **outside** `public/` — the web server can never serve it directly.
6. Up to 10 files per expense, enforced in `ExpenseController::handleReceiptUploads()`.

If the database insert that records the receipt's metadata fails, the
already-moved file is not left orphaned — the expense create/update path
runs inside a single transaction, and the caller is responsible for
cleanup on `Throwable` (see `ExpenseService::create/update`).

## Access control (`App\Controllers\ReceiptController`)

Receipts are only ever served through:

- `GET /receipts/{id}/view` (inline)
- `GET /receipts/{id}/download` (attachment)

Both routes:

1. Require authentication and active-team membership (`AuthMiddleware`, `TeamAccessMiddleware`).
2. Load the receipt and its parent expense, and confirm the receipt's `team_id` matches the session's active team.
3. Re-run `ExpensePolicy::canView()` against the parent expense — a receipt is never more visible than its expense. Viewers are denied receipt access entirely by default (Appendix D observation #5).
4. Resolve the stored relative path to an absolute path with `realpath()` and verify it is still inside the storage root, blocking path traversal (`../../../etc/passwd` etc.) even if a `file_path` value were ever manipulated.
5. Verify the file exists on disk before streaming it.
6. Send `X-Content-Type-Options: nosniff` and the validated MIME type — the browser is never allowed to sniff/execute the file as something else.

There is no public URL for any receipt file. Deleting a receipt is a soft
delete (`deleted_at`); the metadata row and (unless a retention job is
enabled) the physical file are both retained for audit purposes.

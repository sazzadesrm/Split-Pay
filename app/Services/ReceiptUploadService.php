<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Receipt;
use InvalidArgumentException;
use RuntimeException;

final class ReceiptUploadService
{
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    private Receipt $receipts;
    private ActivityLogService $activity;
    private string $basePath;
    private int $maxSize;

    public function __construct()
    {
        $this->receipts = new Receipt();
        $this->activity = new ActivityLogService();
        $config = config('app');
        $this->basePath = rtrim($config['receipt_path'], '/');
        $this->maxSize = $config['max_receipt_upload_size'];
    }

    /**
     * Validates and stores a single uploaded receipt file. Returns the
     * metadata row to be inserted (caller wraps in the expense transaction).
     */
    public function store(array $file, int $expenseId, int $uploadedBy, int $teamId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Upload failed. Please try again.');
        }
        if ($file['size'] > $this->maxSize) {
            throw new InvalidArgumentException('File exceeds the 10 MB limit.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new InvalidArgumentException('Unsupported file type. Allowed: JPG, PNG, WEBP, PDF.');
        }

        $extension = self::ALLOWED_MIME[$mime];
        $storedFilename = bin2hex(random_bytes(24)) . '.' . $extension;
        $subDir = date('Y/m');
        $dir = $this->basePath . '/' . $subDir;
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create storage directory.');
        }
        $destination = $dir . '/' . $storedFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Could not store the uploaded file.');
        }

        return [
            'expense_id' => $expenseId,
            'original_filename' => mb_substr(basename((string) $file['name']), 0, 255),
            'stored_filename' => $storedFilename,
            'file_path' => 'receipts/' . $subDir . '/' . $storedFilename,
            'mime_type' => $mime,
            'file_size' => (int) $file['size'],
            'uploaded_by' => $uploadedBy,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function resolveAbsolutePath(string $relativePath): ?string
    {
        $storageRoot = dirname($this->basePath);
        $full = realpath($storageRoot . '/' . $relativePath);
        $storageRealpath = realpath($storageRoot);
        if ($full === false || $storageRealpath === false || !str_starts_with($full, $storageRealpath)) {
            return null;
        }
        return $full;
    }

    public function delete(int $receiptId, int $teamId, int $actorId): void
    {
        $receipt = $this->receipts->findWithExpense($receiptId);
        if ($receipt === null || (int) $receipt['team_id'] !== $teamId) {
            throw new RuntimeException('Receipt not found.');
        }
        $this->receipts->softDelete($receiptId);
        $this->activity->log($teamId, $actorId, 'receipt.deleted', 'receipt', $receiptId, 'Receipt deleted.');
    }
}

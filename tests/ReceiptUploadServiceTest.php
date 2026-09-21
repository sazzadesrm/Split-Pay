<?php
declare(strict_types=1);

namespace Tests;

use App\Services\ReceiptUploadService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ReceiptUploadServiceTest extends TestCase
{
    private ReceiptUploadService $service;

    protected function setUp(): void
    {
        $this->service = new ReceiptUploadService();
    }

    public function testRejectsUploadError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->store(['error' => UPLOAD_ERR_INI_SIZE, 'size' => 0, 'tmp_name' => '', 'name' => 'x.jpg'], 1, 1, 1);
    }

    public function testRejectsOversizedFile(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sp');
        file_put_contents($tmp, str_repeat('a', 10));
        $this->expectException(InvalidArgumentException::class);
        try {
            $this->service->store(['error' => UPLOAD_ERR_OK, 'size' => 999999999, 'tmp_name' => $tmp, 'name' => 'x.jpg'], 1, 1, 1);
        } finally {
            @unlink($tmp);
        }
    }

    public function testRejectsUnsupportedMimeType(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sp');
        file_put_contents($tmp, '#!/bin/sh' . PHP_EOL . 'echo hi');
        $this->expectException(InvalidArgumentException::class);
        try {
            $this->service->store(['error' => UPLOAD_ERR_OK, 'size' => 20, 'tmp_name' => $tmp, 'name' => 'script.sh'], 1, 1, 1);
        } finally {
            @unlink($tmp);
        }
    }

    public function testResolveAbsolutePathBlocksTraversal(): void
    {
        $result = $this->service->resolveAbsolutePath('../../../../etc/passwd');
        $this->assertNull($result);
    }
}

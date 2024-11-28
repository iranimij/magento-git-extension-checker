<?php

declare(strict_types=1);

namespace Iranimij\GitExtensionChecker\Model;

class Scan
{
    public function performScan(string $moduleName, string $modulePath): array
    {
        // Implement the scan logic here
        return [
            'module' => $moduleName,
            'path' => $modulePath,
            'status' => 'success'
        ];
    }
}

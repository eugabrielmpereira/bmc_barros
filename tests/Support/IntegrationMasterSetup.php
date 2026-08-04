<?php

declare(strict_types=1);

namespace Tests\Support;

use Core\Database\MasterDatabase;

final class IntegrationMasterSetup
{
    public static function ensureMasterSeeded(): void
    {
        try {
            MasterDatabase::ensureDatabaseExists();
            $pdo = MasterDatabase::getConnection();
            $stmt = $pdo->query("SHOW TABLES LIKE 'tenants'");
            if ($stmt === false || $stmt->fetchColumn() === false) {
                passthru('php ' . escapeshellarg(dirname(__DIR__, 2) . '/migrate_master.php') . ' run', $code);
                if ($code !== 0) {
                    return;
                }
            }
            passthru('php ' . escapeshellarg(dirname(__DIR__, 2) . '/migrate_master.php') . ' seed', $seedCode);
        } catch (\Throwable) {
            // Testes marcam skip se mestre indisponível.
        }
    }
}

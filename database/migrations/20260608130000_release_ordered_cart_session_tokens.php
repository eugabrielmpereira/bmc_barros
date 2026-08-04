<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Carrinhos finalizados não devem reter session_token (evita colisão UNIQUE no próximo checkout).
 */
final class ReleaseOrderedCartSessionTokens extends Migration
{
    public function up(): void
    {
        $this->exec("UPDATE carts SET session_token = NULL WHERE status = 'ordered' AND session_token IS NOT NULL");
    }

    public function down(): void
    {
        // Não restaura tokens liberados — operação de correção de dados.
    }
}

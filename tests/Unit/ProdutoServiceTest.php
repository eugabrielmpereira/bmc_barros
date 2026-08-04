<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Products\Repositories\ProductRepository;
use Erp\Products\Services\ProductService;
use PDO;
use PHPUnit\Framework\TestCase;

final class ProdutoServiceTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $this->pdo->exec(
            'CREATE TABLE categorias (id INTEGER PRIMARY KEY, nome TEXT NOT NULL);
             CREATE TABLE produtos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                categoria_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                descricao TEXT,
                short_description TEXT,
                long_description TEXT,
                preco REAL,
                imagem TEXT,
                estoque_atual INTEGER DEFAULT 0,
                status TEXT DEFAULT \'ativo\'
             );'
        );
        $this->pdo->exec("INSERT INTO categorias (id, nome) VALUES (1, 'Cimento')");
    }

    public function testCreateRejectsMissingNome(): void
    {
        $service = new ProductService(new ProductRepository($this->pdo));

        $result = $service->create([
            'nome' => '',
            'categoria_id' => '1',
            'preco' => '',
            'estoque_atual' => '0',
            'status' => 'ativo',
        ]);

        $this->assertFalse($result['success']);
        $this->assertContains('Nome do produto é obrigatório.', $result['errors']);
    }

    public function testCreatePersistsValidProduct(): void
    {
        $service = new ProductService(new ProductRepository($this->pdo));

        $result = $service->create([
            'nome' => 'Tijolo',
            'descricao' => '8 furos',
            'categoria_id' => '1',
            'preco' => '1.50',
            'estoque_atual' => '10',
            'status' => 'ativo',
        ]);

        $this->assertTrue($result['success']);
        $row = $this->pdo->query('SELECT nome FROM produtos')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Tijolo', $row['nome']);
    }

    public function testDeleteRemovesProduct(): void
    {
        $repo = new ProductRepository($this->pdo);
        $repo->create([
            'categoria_id' => 1,
            'nome' => 'Argamassa',
            'descricao' => '',
            'preco' => null,
            'imagem' => null,
            'estoque_atual' => 5,
            'status' => 'ativo',
        ]);

        $service = new ProductService($repo);
        $result = $service->delete(1);

        $this->assertTrue($result['success']);
        $this->assertNull($repo->find(1));
    }
}

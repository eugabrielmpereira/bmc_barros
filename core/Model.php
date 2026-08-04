<?php

declare(strict_types=1);

namespace Core;

abstract class Model
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
}
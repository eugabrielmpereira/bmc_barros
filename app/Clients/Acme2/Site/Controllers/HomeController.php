<?php

declare(strict_types=1);

namespace Clients\Acme2\Site\Controllers;

use Core\Controller;

final class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('client::home', ['client' => client_config()]);
    }
}

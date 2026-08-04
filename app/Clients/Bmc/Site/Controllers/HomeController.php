<?php

declare(strict_types=1);

namespace Clients\Bmc\Site\Controllers;

use Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('client::home', ['client' => client_config()]);
    }
}

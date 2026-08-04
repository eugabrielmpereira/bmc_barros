<?php

declare(strict_types=1);

namespace Clients\Bmc\Site\Controllers;

use Core\Controller;

final class LegalController extends Controller
{
    public function privacy(): void
    {
        $this->view('client::legal/privacy', ['client' => client_config()]);
    }

    public function terms(): void
    {
        $this->view('client::legal/terms', ['client' => client_config()]);
    }
}

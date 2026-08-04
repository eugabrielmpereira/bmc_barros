<?php

declare(strict_types=1);

namespace Clients\Bmc\Site\Controllers;

use Erp\Commerce\Controllers\PublicCartController;

final class CartController extends PublicCartController
{
    public function placeOrder(): void
    {
        $accepted = filter_var($_POST['accept_terms'] ?? false, FILTER_VALIDATE_BOOL)
            || (string) ($_POST['accept_terms'] ?? '') === '1';

        if (!$accepted) {
            $this->renderCheckout(
                ['É necessário aceitar os Termos de Uso e a Política de Privacidade para continuar.'],
                $_POST,
                ['accept_terms' => 'Aceite os termos para continuar.']
            );

            return;
        }

        parent::placeOrder();
    }
}

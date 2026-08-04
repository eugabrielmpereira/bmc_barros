<?php

declare(strict_types=1);

namespace Erp\Finance\Gateways;

use Erp\Finance\Contracts\PaymentGatewayInterface;
use Erp\Finance\Gateways\Asaas\AsaasPixGateway;

final class PaymentGatewayFactory
{
    public static function resolve(): ?PaymentGatewayInterface
    {
        $gateway = AsaasPixGateway::class;
        $instance = new $gateway();

        return $instance->isEnabled() ? $instance : null;
    }
}

<?php

declare(strict_types=1);

namespace Erp\Commerce\Controllers;

use Core\Controller;
use Core\Security\Csrf;
use Erp\Orders\Services\OrderTrackingService;

abstract class PublicOrderTrackingController extends Controller
{
    public function __construct(
        private ?OrderTrackingService $trackingService = null
    ) {
        $this->trackingService ??= new OrderTrackingService();
    }

    public function index(): void
    {
        $this->view('client::commerce/track-order', [
            'client' => client_config(),
            'reference' => trim((string) ($_GET['ref'] ?? '')),
            'result' => null,
            'error' => flash('tracking_error'),
        ]);
    }

    public function lookup(): void
    {
        Csrf::requireValidPostOrAbort();

        $reference = strtoupper(trim((string) ($_POST['public_reference'] ?? '')));
        $phoneDigits = trim((string) ($_POST['phone_last_digits'] ?? ''));
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        $lookup = $this->trackingService->lookup($reference, $phoneDigits, $ip);

        if (!$lookup['success']) {
            $this->view('client::commerce/track-order', [
                'client' => client_config(),
                'reference' => $reference,
                'result' => null,
                'error' => $lookup['message'],
            ]);

            return;
        }

        $this->view('client::commerce/track-order', [
            'client' => client_config(),
            'reference' => $reference,
            'result' => $lookup['result'],
            'error' => null,
        ]);
    }
}

<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController
 *
 * Semua controller Fleet Service extend class ini.
 * Auto-load helper fleet_response dan url.
 */
abstract class BaseController extends Controller
{
    protected IncomingRequest|CLIRequest $request;

    /**
     * Helper yang diload otomatis untuk semua controller.
     * @var list<string>
     */
    protected $helpers = ['fleet_response', 'url'];

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
    }

    // ─── Utility ─────────────────────────────────────────────────

    /**
     * Ambil body JSON dari request. Return array kosong jika gagal.
     *
     * @return array<string, mixed>
     */
    protected function getJsonBody(): array
    {
        $data = $this->request->getJSON(true);
        return is_array($data) ? $data : [];
    }

    /**
     * Ambil integer dari segment URL, return null jika bukan angka.
     */
    protected function getIdParam(mixed $id): ?int
    {
        return is_numeric($id) && (int) $id > 0 ? (int) $id : null;
    }
}

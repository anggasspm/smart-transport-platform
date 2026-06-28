<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    private const WRITE_METHODS = ['POST', 'PATCH', 'PUT', 'DELETE'];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! in_array(strtoupper($request->getMethod()), self::WRITE_METHODS)) {
            return;
        }

        $role         = $request->getHeaderLine('x-user-role');
        $requiredRole = $arguments[0] ?? 'admin';

        if (empty($role)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Unauthorized: token required for this action',
                    'data'    => null,
                ]);
        }

        if ($role !== $requiredRole) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Forbidden: role admin required',
                    'data'    => null,
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
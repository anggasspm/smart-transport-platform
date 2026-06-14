<?php

declare(strict_types=1);

namespace App\Traits;

use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ApiResponseTrait
 *
 * Provides standardised JSON response helpers for Fleet Service controllers.
 *
 * Usage (in any controller that extends BaseController):
 *   use App\Traits\ApiResponseTrait;
 */
trait ApiResponseTrait
{
    // -------------------------------------------------------------------------
    // Success
    // -------------------------------------------------------------------------

    /**
     * 200 / 201 success response.
     *
     * @param mixed  $data    Primary response payload (array, object, or null).
     * @param string $message Human-readable message.
     * @param int    $code    HTTP status code (default 200).
     * @param array  $extra   Optional extra top-level keys merged into the envelope.
     */
    protected function success(
        mixed  $data    = null,
        string $message = 'OK',
        int    $code    = ResponseInterface::HTTP_OK,
        array  $extra   = []
    ): Response {
        $body = array_merge([
            'status'  => 'success',
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ], $extra);

        return $this->response
            ->setStatusCode($code)
            ->setContentType('application/json')
            ->setJSON($body);
    }

    // -------------------------------------------------------------------------
    // Error
    // -------------------------------------------------------------------------

    /**
     * Generic error response.
     *
     * @param string     $message Human-readable error message.
     * @param int        $code    HTTP status code (default 400).
     * @param mixed|null $errors  Optional structured validation errors or details.
     * @param array      $extra   Optional extra top-level keys merged into the envelope.
     */
    protected function error(
        string $message = 'Bad Request',
        int    $code    = ResponseInterface::HTTP_BAD_REQUEST,
        mixed  $errors  = null,
        array  $extra   = []
    ): Response {
        $body = array_merge([
            'status'  => 'error',
            'code'    => $code,
            'message' => $message,
            'errors'  => $errors,
        ], $extra);

        return $this->response
            ->setStatusCode($code)
            ->setContentType('application/json')
            ->setJSON($body);
    }

    // -------------------------------------------------------------------------
    // Convenience shorthands
    // -------------------------------------------------------------------------

    /** 201 Created */
    protected function created(mixed $data = null, string $message = 'Created'): Response
    {
        return $this->success($data, $message, ResponseInterface::HTTP_CREATED);
    }

    /** 404 Not Found */
    protected function notFound(string $message = 'Resource not found'): Response
    {
        return $this->error($message, ResponseInterface::HTTP_NOT_FOUND);
    }

    /** 422 Unprocessable Entity — validation failures */
    protected function validationError(mixed $errors, string $message = 'Validation failed'): Response
    {
        return $this->error($message, ResponseInterface::HTTP_BAD_REQUEST, $errors);
    }

    /** 500 Internal Server Error */
    protected function serverError(string $message = 'Internal Server Error'): Response
    {
        return $this->error($message, ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }

    /** 503 Service Unavailable */
    protected function serviceUnavailable(string $message = 'Service Unavailable', array $extra = []): Response
    {
        return $this->error($message, ResponseInterface::HTTP_SERVICE_UNAVAILABLE, null, $extra);
    }
}

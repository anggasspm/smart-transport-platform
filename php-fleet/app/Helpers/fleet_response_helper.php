<?php

/**
 * fleet_response_helper.php
 *
 * Helper fungsi response JSON standar untuk Fleet Service.
 * Load otomatis via Config/Autoload.php atau manual: helper('fleet_response')
 */

if (! function_exists('fleet_success')) {
    /**
     * Response sukses standar.
     *
     * @param mixed                $data
     * @param string               $message
     * @param int                  $code     HTTP status code
     * @param array<string, mixed> $extra    Key tambahan di root response
     */
    function fleet_success(
        mixed $data,
        string $message = 'OK',
        int $code = 200,
        array $extra = []
    ): \CodeIgniter\HTTP\Response {
        $body = array_merge([
            'status'    => 'success',
            'code'      => $code,
            'data'      => $data,
            'message'   => $message,
            'timestamp' => date(DATE_ATOM),
            'service'   => 'fleet-service',
        ], $extra);

        return response()
            ->setStatusCode($code)
            ->setContentType('application/json')
            ->setBody(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

if (! function_exists('fleet_error')) {
    /**
     * Response error standar.
     *
     * @param string               $message
     * @param int                  $code     HTTP status code
     * @param array<string, mixed>|null $errors  Validation errors, dsb.
     * @param mixed                $data
     */
    function fleet_error(
        string $message = 'Error',
        int $code = 400,
        ?array $errors = null,
        mixed $data = null
    ): \CodeIgniter\HTTP\Response {
        $body = [
            'status'    => 'error',
            'code'      => $code,
            'data'      => $data,
            'message'   => $message,
            'timestamp' => date(DATE_ATOM),
            'service'   => 'fleet-service',
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()
            ->setStatusCode($code)
            ->setContentType('application/json')
            ->setBody(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

if (! function_exists('fleet_not_found')) {
    /**
     * Shorthand 404.
     */
    function fleet_not_found(string $resource = 'Resource'): \CodeIgniter\HTTP\Response
    {
        return fleet_error("{$resource} tidak ditemukan.", 404);
    }
}

if (! function_exists('fleet_validation_error')) {
    /**
     * Shorthand 422 Validation Failed.
     *
     * @param array<string, mixed> $errors
     */
    function fleet_validation_error(array $errors): \CodeIgniter\HTTP\Response
    {
        return fleet_error('Validation failed.', 422, $errors);
    }
}

if (! function_exists('fleet_server_error')) {
    /**
     * Shorthand 500.
     */
    function fleet_server_error(string $message = 'Internal server error.'): \CodeIgniter\HTTP\Response
    {
        return fleet_error($message, 500);
    }
}

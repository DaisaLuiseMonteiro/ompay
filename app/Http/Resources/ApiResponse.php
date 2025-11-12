<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponse extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = 'data';

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @param  string  $message
     * @param  bool  $success
     * @return void
     */
    public function __construct($resource = null, string $message = '', bool $success = true)
    {
        parent::__construct($resource);
        
        if ($message) {
            $this->additional(['message' => $message]);
        }
        
        $this->additional(['success' => $success]);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    /**
     * Create a success response.
     *
     * @param  mixed  $resource
     * @param  string  $message
     * @return static
     */
    public static function success($resource = null, string $message = 'Opération réussie')
    {
        return new static($resource, $message, true);
    }

    /**
     * Create an error response.
     *
     * @param  string  $message
     * @param  int  $status
     * @param  array  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error(string $message = 'Une erreur est survenue', int $status = 400, array $errors = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}

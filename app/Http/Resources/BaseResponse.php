<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * Response-body encryption (formerly gated by IS_NEED_ENCRYPT) was removed:
 * it re-encrypted the payload with a server-held key, which is transport
 * obfuscation, not confidentiality (the server already holds that key) —
 * TLS covers transport, and financial fields are already end-to-end
 * encrypted by the client before they ever reach this response.
 */
class BaseResponse extends JsonResource
{
    public $status;

    public $message;

    public function __construct($status, $message, $resource = null)
    {
        parent::__construct($resource);
        $this->status = $status;
        $this->message = $message;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'data' => $this->resource,
        ];
    }
}

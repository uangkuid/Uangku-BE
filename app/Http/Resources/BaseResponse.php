<?php

namespace App\Http\Resources;

use App\Helpers\EncryptionHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResponse extends JsonResource
{
    public $status;
    public $message;
    public $isNeedEncrypt;

    public function __construct($status, $message, $resource = null)
    {
        parent::__construct($resource);
        $this->status  = $status;
        $this->message = $message;
        $this->isNeedEncrypt = env('IS_NEED_ENCRYPT', false); // Ambil nilai dari .env
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     * @throws \Exception
     */
    public function toArray($request)
    {
        $payload = null;

        if ($this->resource != null) {
            if ($this->isNeedEncrypt) {
                $secret = env('MAIN_SECRET_KEY') . env('MAIN_SALT_KEY');
                $payload = EncryptionHelper::encrypt(json_encode($this->resource), $secret);
            } else {
                $payload = $this->resource;
            }
        }

        return [
            'status'   => $this->status,
            'message'   => $this->message,
            'data'      => $payload
        ];
    }
}

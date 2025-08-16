<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Exception;
use App\Helpers\EncryptionHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaginationResponse extends JsonResource
{
    public $status;
    public $message;
    public $isNeedEncrypt;
    public $page;
    public $totalPage;
    public $totalData;

    public function __construct(
        int    $status,
        string $message,
        int    $page,
        int    $totalPage,
        int    $totalData,
               $resource = null
    )
    {
        parent::__construct($resource);
        $this->status = $status;
        $this->message = $message;
        $this->page = $page;
        $this->totalData = $totalData;
        $this->totalPage = $totalPage;
        $this->isNeedEncrypt = env('IS_NEED_ENCRYPT', false); // Ambil nilai dari .env
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     * @throws Exception
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
            'status' => $this->status,
            'message' => $this->message,
            'data' => $payload,
            'meta' => [
                "current_page" => $this->page,
                "total_data" => $this->totalData,
                "total_page" => $this->totalPage
            ]
        ];
    }
}

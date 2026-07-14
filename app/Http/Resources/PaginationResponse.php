<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * Response-body encryption (formerly gated by IS_NEED_ENCRYPT) was removed —
 * see BaseResponse for why.
 */
class PaginationResponse extends JsonResource
{
    public $status;

    public $message;

    public $page;

    public $totalPage;

    public $totalData;

    public function __construct(
        int $status,
        string $message,
        int $page,
        int $totalPage,
        int $totalData,
        $resource = null
    ) {
        parent::__construct($resource);
        $this->status = $status;
        $this->message = $message;
        $this->page = $page;
        $this->totalData = $totalData;
        $this->totalPage = $totalPage;
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
            'meta' => [
                'current_page' => $this->page,
                'total_data' => $this->totalData,
                'total_page' => $this->totalPage,
            ],
        ];
    }
}

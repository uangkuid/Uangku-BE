<?php

namespace App\Http\Resources\Models;

use App\Repositories\S3\S3Repository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * {
     * "id": "0198d6dc-a9f7-736b-a7af-61f8e85a3f33",
     * "users": "0198c731-2815-7049-a497-095cc596822e",
     * "categories": "0198c731-2c0f-73b0-8547-a46731daa867",
     * "transaction_type": "0198c731-2be4-72fd-92ba-c1fa5ba0aec3",
     * "amount": "LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlJQklqQU5CZ2txaGtpRzl3MEJBUUVGQUFPQ0FROEFNSUlCQ2dLQ0FRRUF5SytZcWovRlVQUUtFTm5sUGhWNQo1b2dtUkQwM3RXYzVhU29ld1oyakx4dEVmVlB4UDVEblg4bGF0dWI3M0gxd3VxK2lsYjlMU3lBbjBaeUlXaHBECk50M3BGaDVjVzhra3l4b1lZcHI2Ly9CcEs4V0pJWVREaEJUYkJ5YmxEUGtpSzU2OVVQRGlvcGRGcldmbitVdkwKQng0RlBrSWRycU1IVXQ4NDRTeW0vOXRlNWJlc1JXaVE3OGdpUDdDY0NBMFZuR2krOEFTdWE4RGZoZEozdmVLNgpIdUR2Q1ZOZmp2NVExNU1aeGdHN3dBazd6VUdRalV6VnpwcTFHWGZCWDJqS2VkVjBjMlMrUloreXpxL1JNcDlZCjE3UFFja2tMand2ZkZBM1M2QU5kdDRmcXR6TjhoTk1meDRFSDZoMEpJOUhrMVhWMStrVmMvYVY5M1I0UVgrUWIKelFJREFRQUIKLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0tCg==",
     * "note": null,
     * "sub_categories": null,
     * "created_at": "2025-08-23T19:17:22+07:00",
     * "updated_at": "2025-08-23T19:17:22+07:00",
     * "category_name": "Salary",
     * "sub_category_name": null,
     * "wallet_name": "QkTyzIbpnGz5jereJgV7D5xPx6I8EyRAxYNFSrB1Qgpz5ZLKIRFVHKqLOnnO3DaSaBYT2QW8baXPRg3Q4PrVQQl+trv6qzx3yKATS1rQZyQZ1og7nQdkAoL779RdWuV7q0s30xtX3NpM/vZsJxkzsRyLkUJXOhuNW5BeTQ6IW5dOq/w25PmWP3i31WzZ4CN7IhNMuwStyHkH+7vNVgoxgTLlD6ABAtog2pGLKupct0LvIucL4STkUJoTw6wEF1ZPKwroprRBww04VDkQqH5xtcP8gB73Y7b6H7VWA5ap9ZcUCMoz1zP6NW1IH+hnb/H5eWTKonHLppXbWf9MSbnf3w=="
     * }
     */
    public function toArray(Request $request): array
    {
        $subCategory = null;

        if ($this->sub_categories) {
            $subCategory = [
                "id" => $this->sub_categories,
                "name" => $this->sub_category_name,
            ];
        }

        $icon = null;

        if (!empty($this->category_icon)) {
            $icon = app(S3Repository::class)->getData("category", $this->category_icon);
        }

        return [
            "id" => $this->id,
            "users" => $this->users,
            "categories" => [
                "id" => $this->categories,
                "name" => $this->category_name,
                "icon" => $icon,
            ],
            "transaction_type" => $this->transaction_type,
            "amount" => $this->amount,
            "note" => $this->note,
            "sub_categories" => $subCategory,
            "wallet" => [
                "id" => $this->wallet_id    ,
                "name" => $this->wallet_name,
            ],
            "updated_at" => $this->updated_at,
            "created_at" => $this->created_at,
        ];
    }
}

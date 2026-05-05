<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sale = $this->whenLoaded('sale');
        $branch = $this->relationLoaded('sale') ? $this->sale?->getRelation('branch') : null;

        return [
            'id' => $this->id,
            'sale_id' => $this->sale_id,
            'product_id' => $this->product_id,
            'branch_id' => $this->sale?->branch_id,
            'branch' => $branch ? new BranchResource($branch) : null,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'tax_amount' => $this->tax_amount,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

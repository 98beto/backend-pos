<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedCartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->relationLoaded('items') ? $this->items : collect();
        $subtotal = (float) $items->sum('subtotal');
        $taxAmount = (float) $items->sum('tax_amount');
        $totalAmount = $subtotal + $taxAmount - (float) $this->discount_amount;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'customer_id' => $this->customer_id,
            'cash_session_id' => $this->cash_session_id,
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'status' => $this->status,
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'discount_amount' => $this->discount_amount,
            'total_amount' => round($totalAmount, 2),
            'notes' => $this->notes,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'cash_session' => new CashSessionResource($this->whenLoaded('cashSession')),
            'items' => SavedCartItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

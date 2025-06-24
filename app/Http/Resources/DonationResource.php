<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'donor_name' => $this->donor_name,
            'display_name' => $this->display_name,
            'message' => $this->message,
            'is_anonymous' => $this->is_anonymous,
            'show_in_list' => $this->show_in_list,
            'amount' => $this->payment->amount ?? 0,
            'formatted_amount' => $this->formatted_amount,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'payment' => $this->whenLoaded('payment', function () {
                return [
                    'id' => $this->payment->id,
                    'status' => $this->payment->status,
                    'payment_reference' => $this->payment->payment_reference,
                ];
            }),
        ];
    }
}

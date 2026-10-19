<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isReceivable = (string) $this->creditor_id === (string) $request->user()->getKey();
        $counterparty = $isReceivable ? $this->debtor : $this->creditor;

        return [
            'id' => $this->id,
            'direction' => $isReceivable ? 'receivable' : 'payable',
            'visibility' => $this->visibility,
            'agreement_status' => $this->agreement_status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'debt_date' => $this->debt_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'description' => $this->description,
            'counterparty' => $counterparty
                ? (new UserResource($counterparty))->resolve($request)
                : [
                    'id' => null,
                    'name' => $this->counterparty_name,
                    'phone' => $this->counterparty_phone,
                ],
            'schedules' => $this->schedules->sortBy('sequence')->values()->map(fn ($schedule): array => [
                'id' => $schedule->id,
                'sequence' => $schedule->sequence,
                'amount' => $schedule->amount,
                'due_date' => $schedule->due_date->toDateString(),
            ])->values(),
            'balance' => $this->balance,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

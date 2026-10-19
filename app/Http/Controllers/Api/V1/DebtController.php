<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DebtRequest;
use App\Http\Resources\Api\V1\DebtResource;
use App\Models\Debt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DebtController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Debt::class);

        $userId = $request->user()->getKey();
        $debts = Debt::query()
            ->where(function ($query) use ($userId): void {
                $query->where('created_by', $userId)
                    ->orWhere(function ($query) use ($userId): void {
                        $query->where('visibility', 'shared')
                            ->where('agreement_status', 'accepted')
                            ->where(function ($query) use ($userId): void {
                                $query->where('creditor_id', $userId)->orWhere('debtor_id', $userId);
                            });
                    });
            })
            ->withCurrentBalance()
            ->with(['creditor', 'debtor', 'schedules'])
            ->latest('id')
            ->paginate(20);

        return DebtResource::collection($debts)->response()->header('Cache-Control', 'no-store');
    }

    public function store(DebtRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $debt = DB::transaction(function () use ($data, $user): Debt {
            $debt = $user->createdDebts()->create($this->debtAttributes($data, $user));
            $debt->schedules()->createMany($data['schedules'] ?? []);

            return $debt;
        });

        return $this->resourceResponse($debt->getKey(), 201);
    }

    public function show(Debt $debt): JsonResponse
    {
        Gate::authorize('view', $debt);

        return $this->resourceResponse($debt->getKey());
    }

    public function update(DebtRequest $request, Debt $debt): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($data, $user, $debt): void {
            $lockedDebt = Debt::query()->lockForUpdate()->findOrFail($debt->getKey());
            Gate::authorize('update', $lockedDebt);
            $lockedDebt->update($this->debtAttributes($data, $user));
            $lockedDebt->schedules()->delete();
            $lockedDebt->schedules()->createMany($data['schedules'] ?? []);
        });

        return $this->resourceResponse($debt->getKey());
    }

    public function destroy(Debt $debt): JsonResponse
    {
        DB::transaction(function () use ($debt): void {
            $lockedDebt = Debt::query()->lockForUpdate()->findOrFail($debt->getKey());
            Gate::authorize('delete', $lockedDebt);
            $lockedDebt->update(['agreement_status' => 'cancelled']);
        });

        return response()->json(['data' => null, 'message' => 'Debt cancelled.'])
            ->header('Cache-Control', 'no-store');
    }

    private function debtAttributes(array $data, User $user): array
    {
        $counterpartyId = $data['counterparty_id'] ?? null;
        $isReceivable = $data['direction'] === 'receivable';

        unset($data['direction'], $data['counterparty_id'], $data['schedules']);

        return [
            ...$data,
            'due_date' => $data['due_date'] ?? null,
            'description' => $data['description'] ?? null,
            'created_by' => $user->getKey(),
            'creditor_id' => $isReceivable ? $user->getKey() : $counterpartyId,
            'debtor_id' => $isReceivable ? $counterpartyId : $user->getKey(),
            'counterparty_name' => $counterpartyId === null ? $data['counterparty_name'] : null,
            'counterparty_phone' => $counterpartyId === null ? $data['counterparty_phone'] : null,
            'agreement_status' => $data['visibility'] === 'shared' ? 'pending' : 'draft',
        ];
    }

    private function resourceResponse(int $id, int $status = 200): JsonResponse
    {
        $debt = Debt::query()
            ->withCurrentBalance()
            ->with(['creditor', 'debtor', 'schedules'])
            ->findOrFail($id);

        return (new DebtResource($debt))->response()
            ->setStatusCode($status)
            ->header('Cache-Control', 'no-store');
    }
}

<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Debt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->isMethod('post')
            ? Gate::allows('create', Debt::class)
            : Gate::allows('update', $this->route('debt'));
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(['receivable', 'payable'])],
            'visibility' => ['required', Rule::in(['private', 'shared'])],
            'counterparty_id' => ['nullable', 'integer', 'different:'.(string) $this->user()->getKey(), 'exists:users,id', 'prohibited_with:counterparty_name,counterparty_phone'],
            'counterparty_name' => ['required_without:counterparty_id', 'prohibited_with:counterparty_id', 'string', 'max:255'],
            'counterparty_phone' => ['required_without:counterparty_id', 'prohibited_with:counterparty_id', 'string', 'regex:/^\+[1-9][0-9]{1,14}$/D'],
            'amount' => ['required', 'string', 'regex:/^\d{1,15}(?:\.\d{1,4})?$/D', $this->positiveDecimal()],
            'currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/D'],
            'debt_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:debt_date'],
            'description' => ['nullable', 'string', 'max:10000'],
            'schedules' => ['sometimes', 'array'],
            'schedules.*' => ['array:sequence,amount,due_date'],
            'schedules.*.sequence' => ['required', 'integer', 'min:1', 'distinct'],
            'schedules.*.amount' => ['required', 'string', 'regex:/^\d{1,15}(?:\.\d{1,4})?$/D', $this->positiveDecimal()],
            'schedules.*.due_date' => ['required', 'date', 'after_or_equal:debt_date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $schedules = $validator->getData()['schedules'] ?? [];
            if ($schedules === []) {
                return;
            }

            $total = '0';
            foreach ($schedules as $schedule) {
                $total = $this->addIntegers($total, $this->toMinorUnits($schedule['amount']));
            }

            if ($total !== $this->toMinorUnits($validator->getData()['amount'])) {
                $validator->errors()->add('schedules', 'The installment amounts must add up to the debt amount.');
            }
        });
    }

    private function positiveDecimal(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value) && $this->toMinorUnits($value) === '0') {
                $fail("The {$attribute} must be greater than zero.");
            }
        };
    }

    private function toMinorUnits(string $amount): string
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ltrim(ltrim($whole, '0').str_pad($fraction, 4, '0'), '0') ?: '0';
    }

    private function addIntegers(string $left, string $right): string
    {
        $left = strrev($left);
        $right = strrev($right);
        $length = max(strlen($left), strlen($right));
        $sum = '';
        $carry = 0;

        for ($index = 0; $index < $length; $index++) {
            $digit = (isset($left[$index]) ? (int) $left[$index] : 0)
                + (isset($right[$index]) ? (int) $right[$index] : 0)
                + $carry;
            $sum .= (string) ($digit % 10);
            $carry = intdiv($digit, 10);
        }

        if ($carry > 0) {
            $sum .= (string) $carry;
        }

        return strrev($sum);
    }
}

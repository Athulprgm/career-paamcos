<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\DepositScheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFdCommissionSchemeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->deposit_scheme_id === 'NEW' || ! is_numeric($this->deposit_scheme_id)) {
            $this->merge(['deposit_scheme_id' => null]);
        } else {
            $scheme = DepositScheme::find((int) $this->deposit_scheme_id);
            $isExplicitClone = $this->has('clone_from')
                || $this->input('mode') === 'clone';

            if ($scheme && ! $isExplicitClone) {
                $isCreatingNewVariant = $this->input('action_mode') === 'create_new'
                    && $this->filled('name')
                    && trim((string) $this->input('name')) !== trim((string) $scheme->name);

                if ($isCreatingNewVariant) {
                    $this->merge([
                        'deposit_scheme_id' => null,
                        'action_mode' => 'create_new',
                    ]);
                } else {
                    $this->merge([
                        'deposit_scheme_id' => $scheme->id,
                        'action_mode' => 'update_existing',
                    ]);
                }
            } elseif ($isExplicitClone) {
                $this->merge([
                    'deposit_scheme_id' => null,
                    'action_mode' => 'create_new',
                ]);
            }
        }

        if (! $this->filled('payout_year')) {
            $this->merge(['payout_year' => 1]);
        }

        if (! $this->filled('code') && $this->filled('name')) {
            $this->merge([
                'code' => DepositScheme::generateSchemeCode(
                    $this->input('scheme_type', 'FD'),
                    $this->input('name', ''),
                    $this->input('tenure', 1),
                    $this->input('tenure_unit', 'YEARS')
                ),
            ]);
        } elseif ($this->filled('code')) {
            $this->merge([
                'code' => strtoupper(trim((string) $this->input('code'))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isUpdate = $this->filled('deposit_scheme_id');
        $ignoreId = $isUpdate ? (int) $this->deposit_scheme_id : null;

        return [
            'deposit_scheme_id' => ['nullable', 'integer', 'exists:deposit_schemes,id'],
            'action_mode' => ['nullable', 'string', 'in:create_new,update_existing'],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', Rule::unique('deposit_schemes', 'code')->ignore($ignoreId)],
            'scheme_type' => ['nullable', 'string', 'max:50'],
            'collection_frequency' => ['nullable', 'string', 'max:50'],
            'interest_payout_frequency' => ['nullable', 'string', 'max:50'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'senior_citizen_extra_rate' => ['nullable', 'numeric', 'min:0', 'max:10', 'regex:/^\d+(\.\d{1,2})?$/'],
            'tenure' => ['required', 'integer', 'min:1', 'max:1200'],
            'tenure_unit' => ['required', 'string', Rule::in([
                DepositScheme::TENURE_DAYS,
                DepositScheme::TENURE_MONTHS,
                DepositScheme::TENURE_YEARS,
            ])],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gte:min_amount'],
            'status' => ['required', 'string', Rule::in([
                DepositScheme::STATUS_ACTIVE,
                DepositScheme::STATUS_INACTIVE,
            ])],
            'promotion_target_percentage' => ['nullable', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:500'],

            // Dynamic ranks validation (supports per-year commissions)
            'payout_year' => ['nullable', 'integer', 'min:1', 'max:50'],
            'ranks' => ['required', 'array'],
            'ranks.*.rank_id' => ['required', 'integer', 'exists:ranks,id'],
            'ranks.*.is_eligible' => ['nullable', 'boolean'],
            'ranks.*.commission_percentage' => ['nullable', 'numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'years_data' => ['nullable', 'string'],
            'tenure_rates' => ['nullable', 'array'],
            'tenure_rates.*.tenure' => ['nullable', 'integer', 'min:1'],
            'tenure_rates.*.tenure_unit' => ['nullable', 'string'],
            'tenure_rates.*.interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tenure_rates.*.senior_citizen_extra_rate' => ['nullable', 'numeric', 'min:0', 'max:10'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'The FD Scheme Code has already been taken. Scheme codes must be unique.',
            'interest_rate.regex' => 'Interest rate must be a valid number with at most 2 decimal places.',
            'senior_citizen_extra_rate.regex' => 'Senior citizen extra rate must be a valid number with at most 2 decimal places.',
            'senior_citizen_extra_rate.min' => 'Senior citizen extra rate cannot be negative.',
            'senior_citizen_extra_rate.max' => 'Senior citizen extra rate cannot exceed 10%.',
            'ranks.*.commission_percentage.regex' => 'Rank commission percentage must have at most 2 decimal places.',
            'ranks.*.commission_percentage.min' => 'Commission percentage cannot be negative.',
            'ranks.*.commission_percentage.max' => 'Individual commission percentage cannot exceed 100%.',
            'promotion_target_percentage.regex' => 'Promotion target credit percentage must have at most 2 decimal places.',
            'promotion_target_percentage.min' => 'Promotion target credit percentage cannot be negative.',
            'promotion_target_percentage.max' => 'Promotion target credit percentage cannot exceed 100%.',
            'max_amount.gte' => 'Maximum deposit amount must be greater than or equal to minimum amount.',
        ];
    }
}

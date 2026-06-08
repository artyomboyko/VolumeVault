<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesBackupDestination;
use Illuminate\Foundation\Http\FormRequest;

class StoreDestinationRequest extends FormRequest
{
    use ValidatesBackupDestination;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->baseRules(), $this->providerRules(true));
    }
}

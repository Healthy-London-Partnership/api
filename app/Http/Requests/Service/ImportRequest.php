<?php

namespace App\Http\Requests\Service;

use App\Models\Organisation;
use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->user()) {
            return $this->user()->isOrganisationAdmin(Organisation::findOrFail($this->organisation_id)) || $this->user()->isLocalAdmin();
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'spreadsheet' => [
                'required',
                'regex:/^data:application\/[a-z\-\.]+;base64,/',
            ],
            'organisation_id' => [
                'required',
                'exists:organisations,id',
            ],
        ];
    }
}

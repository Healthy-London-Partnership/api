<?php

namespace App\Http\Requests\Search;

use App\Search\CriteriaQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'query' => [
                'string',
                'min:3',
                'max:255',
            ],
            'category' => [
                'string',
                'min:1',
                'max:255',
            ],
            'persona' => [
                'string',
                'min:1',
                'max:255',
            ],
            'is_free' => [
                'required_without_all:query,type,category,persona,wait_time,location,is_national',
                'boolean',
            ],
            'is_national' => [
                'required_without_all:query,type,category,persona,wait_time,location,is_free',
                'boolean',
            ],
            'order' => [
                Rule::in([CriteriaQuery::ORDER_RELEVANCE, CriteriaQuery::ORDER_DISTANCE]),
            ],
            'location' => [
                'required_if:order,distance',
                'array',
            ],
            'location.lat' => [
                'required_with:location',
                'numeric',
                'min:-90',
                'max:90',
            ],
            'location.lon' => [
                'required_with:location',
                'numeric',
                'min:-180',
                'max:180',
            ],
        ];
    }
}

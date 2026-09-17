<?php

namespace App\Http\Requests;

use App\Support\ContentRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        $definition = ContentRegistry::get($this->route('resource'));
        $rules = [];
        foreach ($definition['fields'] as $field => $spec) {
            $rules[$field] = $spec['rules'];
            if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
                array_unshift($rules[$field], 'sometimes');
            }
            if ($spec['unique'] ?? false) {
                $rules[$field][] = Rule::unique($definition['table'], $field)->ignore($this->route('id'));
            }
            if ($spec['type'] === 'multi-reference') {
                $rules[$field.'.*'] = ['integer', 'distinct', Rule::exists($spec['reference'], 'id')];
                if ($spec['reference'] === 'media') {
                    $rules[$field.'.*'][] = Rule::exists('media', 'id')->where(fn ($query) => $query->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp']));
                }
            }
            if (($spec['reference'] ?? null) === 'media' && $spec['type'] === 'reference') {
                $pdf = $field === 'pdf_media_id' || $definition['table'] === 'resumes';
                $rules[$field][] = Rule::exists('media', 'id')->where(fn ($query) => $query->whereIn('mime_type', $pdf ? ['application/pdf'] : ['image/jpeg', 'image/png', 'image/webp']));
            }
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $record = $this->route('id') ? ContentRegistry::query($this->route('resource'))->findOrFail($this->route('id')) : null;
            foreach (['ended_on' => 'started_on', 'expires_on' => 'issued_on'] as $end => $start) {
                $endValue = $this->exists($end) ? $this->input($end) : $record?->getAttribute($end);
                $startValue = $this->exists($start) ? $this->input($start) : $record?->getAttribute($start);
                if ($endValue && $startValue && strtotime($endValue) < strtotime($startValue)) {
                    $validator->errors()->add($end, 'The end date must be on or after the start date.');
                }
            }
        }];
    }
}

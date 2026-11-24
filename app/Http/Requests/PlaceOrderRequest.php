<?php

namespace App\Http\Requests;

use App\Enums\OrderPaymentMethod;
use App\Enums\OrderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');

        if (is_string($items)) {
            $decoded = json_decode($items, true);

            if (is_array($decoded)) {
                $this->merge(['items' => $decoded]);
            }
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = OrderType::tryFrom((string) $this->input('order_type')) ?? OrderType::Delivery;
        $methods = OrderPaymentMethod::valuesForType($type);

        return [
            'order_type' => ['required', Rule::in(array_keys(OrderType::options()))],
            'payment_method' => ['required', Rule::in($methods)],
            'customer_name' => ['required', 'string', 'min:2', 'max:100'],
            'customer_phone' => ['required', 'string', 'min:7', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'customer_email' => ['nullable', 'email', 'max:150'],
            'delivery_address' => $type === OrderType::Delivery
                ? ['required', 'string', 'max:300']
                : ['nullable', 'string', 'max:300'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_proof' => [
                'required_if:payment_method,'.OrderPaymentMethod::GCash->value,
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.note' => ['nullable', 'string', 'max:200'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'customer_phone.regex' => 'Enter a phone number the rider can call, e.g. 0917 123 4567.',
            'delivery_address.required' => 'We need an address to deliver to.',
            'items.required' => 'Your cart is empty.',
            'payment_proof.required_if' => 'Upload a screenshot of your GCash receipt to place the order.',
            'payment_proof.image' => 'The receipt must be a JPG, PNG or WebP image.',
            'payment_proof.max' => 'That screenshot is larger than 5 MB. Please choose a smaller one.',
        ];
    }
}

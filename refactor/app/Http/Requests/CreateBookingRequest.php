<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize()
    {
        if ($this->__authenticatedUser->user_type !== env('CUSTOMER_ROLE_ID')) {
            //This is just to make sure that the relevant message is returned
            throw new HttpResponseException(response()->json([
                'status' => 'fail',
                'message' => 'Translator can not create booking',
            ], 422));
        }
        return true;
    }

    public function rules()
    {
        return [
            'from_language_id' => 'required',
            'immediate' => 'required|in:yes,no',
            'due_date' => 'required_if:immediate,no|filled|date_format:m/d/Y',
            'due_time' => 'required_if:immediate,no|filled|date_format:H:i',
            'customer_phone_type' => 'required_if:immediate,no',
            'customer_physical_type' => 'required_if:immediate,no',
            'duration' => 'required_if:immediate,yes|filled',
        ];
    }

    public function messages()
    {
        return [
            'from_language_id.required' => 'Du måste fylla in alla fält',
            'immediate.required' => 'You must fill in all fields for immediate.',
            'immediate.in' => 'The immediate field must be either yes or no.',
            'due_date.required_if' => 'You must fill in all fields for due_date.',
            'due_date.date_format' => 'The due_date field must be in the format m/d/Y.',
            'due_time.required_if' => 'You must fill in all fields for due_time.',
            'due_time.date_format' => 'The due_time field must be in the format H:i.',
            'customer_phone_type.required_if' => 'You must make a selection here for customer_phone_type.',
            'customer_physical_type.required_if' => 'You must make a selection here for customer_physical_type.',
            'duration.required' => 'You must fill in all fields for duration.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'fail',
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }

    protected function passedValidation()
    {
        $data = $this->all();
        $jobFor = $data['job_for'];

        if (in_array('normal', $jobFor, true))
            $data['certified'] = 'normal';
        elseif (in_array('certified', $jobFor, true))
            $data['certified'] = 'yes';
        elseif (in_array('certified_in_law', $jobFor, true))
            $data['certified'] = 'law';
        elseif (in_array('certified_in_health', $jobFor, true))
            $data['certified'] = 'health';

        if (in_array('normal', $jobFor, true) && in_array('certified', $jobFor, true))
            $data['certified'] = 'both';
        elseif (in_array('normal', $jobFor, true) && in_array('certified_in_law', $jobFor, true))
            $data['certified'] = 'n_law';
        elseif (in_array('normal', $jobFor, true) && in_array('certified_in_health', $jobFor, true))
            $data['certified'] = 'n_health';


        switch ($this->__authenticatedUser->userMeta->consumer_type) {
            case 'rwsconsumer':
                $data['job_type'] = 'rws';
                break;
            case 'ngo':
                $data['job_type'] = 'unpaid';
                break;
            case 'paid':
                $data['job_type'] = 'paid';
                break;
        }

        $data['b_created_at'] = now()->format('Y-m-d H:i:s');
        $data['customer_phone_type'] = isset($this->customer_phone_type) ? 'yes' : 'no';
        $data['customer_physical_type'] = isset($this->customer_physical_type) ? 'yes' : 'no';
        $data['gender'] = in_array('male', $jobFor, true) ? 'male' : (in_array('female', $jobFor) ? 'female' : null);


        $this->merge($data);
    }
}

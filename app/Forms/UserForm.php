<?php

namespace App\Forms;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class UserForm extends FormRequest {

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
          'name' => 'required|string',
          'email' => 'required|string|email',
        ];
    }

    public function save()
    {
        $user = new User();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->password = bcrypt("asd");
        $user->save();
        return [
          'name' => $user->name,
          'email' => $user->email,
        ];
    }
}

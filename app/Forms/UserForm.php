<?php

namespace App\Forms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
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

    public function saveOrUpdate(?int $id = null)
    {
        if($this->isMethod('post'))
        {
            $user = new User();
            $user->password = bcrypt("asd");
        }
        else
        {
            $user = User::find($id);
        }
        $user->name = $this->name;
        $user->email = $this->email;
        $user->save();

        return [
          'name' => $user->name,
          'email' => $user->email,
        ];
    }
}

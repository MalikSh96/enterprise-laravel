<?php

namespace App\Forms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserForm extends FormRequest {

    public function authorize()
    {
        if($this->isMethod('post')) 
            return $this->user()->can('create', User::class);
        return $this->user()->can('update', User::class); 
        // return $this->user()->can('update', $this->user()->id); //does not work
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

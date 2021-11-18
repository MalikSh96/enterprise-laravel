<?php

namespace App\Forms;

use OpenApi\Generator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="User",
 *      required={
 *      "name",
 *      "email"
 *     }
 * )
 */
class UserForm extends FormRequest {
  
    /** @OA\Property() */
    public string $name;
    /** @OA\Property() */
    public string $email;

    public function authorize()
    {

    }

    public function rules()
    {
      return [
        'name' => 'required|string',
        'email' => 'required|string',
      ];
    }

    public function save()
    {
      $user = new User();
      $user->name = $this->name;
      $user->email = $this->email;
      $user->save();
      return [
        'name' = $user->name,
        'email' => $user->email
      ]
    }
}

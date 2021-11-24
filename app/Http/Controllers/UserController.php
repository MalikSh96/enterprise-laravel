<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use OpenApi\Generator;
use App\Forms\UserForm as Form;

/**
 * @OA\Swagger(
 *     schemes={"https"},
 *     host="mywebsite.com",
 *     basePath="",
 *     @OA\Info(
 *         version="1.0.0",
 *         title="My Website",
 *         description="Put Markdown Here [a Link](https://www.google.com)",
 *         @OA\Contact(
 *             email="my@email"
 *         ),
 *     ),
 * )
 */
class UserController extends Controller
{
    /**
    * @OA\Get(path="/api/users", description="Get all users", operationId="",
    *   @OA\Response(response=200, description="OK",
    *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User"))
    *   ),
    *   @OA\Response(response=404, description="Not Found")
    * )
    */
    public function index(Request $request)
    {
        return User::all();
    }

    /**
    * @OA\Post(path="/api/users", description="Create user", operationId="",
    *   @OA\RequestBody(@OA\JsonContent(ref="#/components/schemas/User"), required=true,description="The creation of a user"),
    *   @OA\Response(response=200, description="OK",
    *     @OA\JsonContent(ref="#/components/schemas/User")
    *   ),
    *   @OA\Response(response=422, description="Unprocessable Entity / Validation Failed")
    * )
    */
    public function store(Form $request)
    {
        return $request->save();
    }
}

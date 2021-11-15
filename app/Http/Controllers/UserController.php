<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use OpenApi\Generator;

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
  *   @OA\Response(response=200, description="Get all contracts",
  *     @OA\JsonContent(type="string")
  *   ),
  *   @OA\Response(response=401, description="Unauthorized"),
  *   @OA\Response(response=404, description="Not Found")
  * )
  */
  public function index(Request $request)
  {
      return User::all();
  }
}

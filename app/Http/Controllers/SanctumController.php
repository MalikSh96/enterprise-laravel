<?php

namespace App\Http\Controllers;

use OpenApi\Generator;
use App\Forms\TokenForm as Form;

class SanctumController extends Controller
{
    /**
    * @OA\Post(path="/api/auth", tags={"Retrieve Authorization Token"},
    *   summary="Post your email and password and we will return a token. Use the token in the 'Authorization' header like so 'Bearer YOUR_TOKEN'",
    *   operationId="",
    *   description="",
    *   @OA\RequestBody(
    *       required=true,
    *       description="The Token Request",
    *       @OA\JsonContent(
    *        @OA\Property(property="email",type="string",example="your@email.com"),
    *        @OA\Property(property="password",type="string",example="YOUR_PASSWORD"),
    *       )
    *   ),
    *   @OA\Response(
    *     response=200,
    *     description="OK",
    *     @OA\JsonContent(ref="#/components/schemas/TokenRequest")
    *   ),
    *   @OA\Response(response=422, description="The provided credentials are incorrect.")
    * )
    */
    public function create(Form $request)
    {
        return $request->generate();
    }
}

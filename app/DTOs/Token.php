<?php

/**
 * @OA\Schema(
 *     schema="TokenRequest",
 *      required={
 *      "email",
 *      "password"
 *     }
 * )
 */
class Token {

    /** @OA\Property() */
    public string $email;

    /** @OA\Property() */
    public string $password;
}

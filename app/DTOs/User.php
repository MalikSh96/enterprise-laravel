<?php

/**
 * @OA\Schema(
 *     schema="User",
 *      required={
 *      "name",
 *      "email"
 *     }
 * )
 */
class User {

    /** @OA\Property() */
    public string $name;

    /** @OA\Property() */
    public string $email;
}

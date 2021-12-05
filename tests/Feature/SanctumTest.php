<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;
use GuzzleHttp\Psr7\{ServerRequest, Utils};
use League\OpenAPIValidation\PSR7\{OperationAddress, RoutedServerRequestValidator, ValidatorBuilder};
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidBody;
use Illuminate\Support\Facades\DB;

class SanctumTest extends TestCase
{
    private string $tokenEndpoint = "/api/auth";

    private OperationAddress $address;
    private RoutedServerRequestValidator $validator;

    private User $user;

    private array $mockPayload = [
        'email' => 'updating@email.com',
        'password' => 'asd'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->address = new OperationAddress($this->tokenEndpoint, 'post');
        $this->validator = (new ValidatorBuilder)
            ->fromJson(file_get_contents('http://127.0.0.1:8000/api/swagger.json'))
            ->getRoutedRequestValidator();

        $this->user = User::find(1);
    }

    public function test_that_token_gets_created_serverside()
    {
        DB::table('personal_access_tokens')->where('tokenable_id', $this->user->id)->delete();

        $userInput = [
            'email' => $this->user->email,
            'password' => 'asd',
        ];

        if(!$this->user || !Hash::check($userInput['password'], $this->user->password))
            dd('You did not supply the actual password of the user');

        $this->postJson($this->tokenEndpoint, $userInput)->assertStatus(200);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
        ]);
    }

    public function test_that_swagger_validates_correctly_filled_payload_for_token_creation()
    {
        $request = $this->wrapServerRequest($this->mockPayload);

        try {
            $this->validator->validate($this->address, $request);
            $this->addToAssertionCount(1);
        } catch (InvalidBody $e) {
            $latestException = $e->getMessage();
            $previousException = $e->getPrevious()->getMessage();
            $exceptionLocation = implode(".", $e->getPrevious()->dataBreadCrumb()->buildChain());
            $this->fail("$latestException $previousException $exceptionLocation");
        }
    }

    public function createStatusProvider()
    {
        return [
            'Missing password' => [422, ['email' =>  $this->mockPayload['email']], 'password'],
            'Missing email' => [422, ['password' => $this->mockPayload['password']], 'email'],
        ];
    }

    /**
    * @dataProvider createStatusProvider
    */
    public function test_that_swagger_and_serverside_fails_if_payload_is_not_filled_correct($status, array $payload, string $expectedKeyOfFailure)
    {
        DB::table('personal_access_tokens')->where('tokenable_id', $this->user->id)->delete();

        $request = $this->wrapServerRequest($payload);
        try {
            $this->validator->validate($this->address, $request);
        } catch (InvalidBody $e) {
            $latestException = $e->getMessage();
            $previousException = $e->getPrevious()->getMessage();
            $exceptionLocation = implode(".", $e->getPrevious()->dataBreadCrumb()->buildChain());
            $expectedKeyOfFailure == $exceptionLocation ? $this->addToAssertionCount(1) : $this->fail("$latestException $previousException $exceptionLocation");
        }

        $response = $this->postJson($this->tokenEndpoint, $payload);
        $response->assertStatus($status, $response);
        $response->assertJsonValidationErrors([$expectedKeyOfFailure]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
        ]);
    }

    private function wrapServerRequest(array $payload): ServerRequest
    {
        return (new ServerRequest('post', $this->tokenEndpoint))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor(json_encode($payload)));
    }
}

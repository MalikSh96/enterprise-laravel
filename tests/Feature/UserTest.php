<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\User;
use GuzzleHttp\Psr7\{ServerRequest, Utils};
use League\OpenAPIValidation\PSR7\{OperationAddress, RoutedServerRequestValidator, ValidatorBuilder};
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidBody;

class UserTest extends TestCase
{
    private string $userEndpoint = "/api/users";

    private OperationAddress $address;
    private RoutedServerRequestValidator $validator;

    private array $mockPayload = [
      'name' => 'fake_name',
      'email' => 'fake_email@test.com'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->address = new OperationAddress($this->userEndpoint, 'post');
        $this->validator = (new ValidatorBuilder)
            ->fromJson(file_get_contents('http://127.0.0.1:8000/api/swagger.json'))
            ->getRoutedRequestValidator();
    }

    public function test_all_users_getting_retrieved()
    {
        $userIdsInDatabase = User::pluck('id');
        $response = $this->json('GET', $this->userEndpoint);
        $response->assertOk();
        $userIdsInResponse = collect(json_decode($response->content()))->pluck('id');
        $this->assertTrue($userIdsInResponse->diff($userIdsInDatabase)->isEmpty());
    }

    public function createStatusProvider()
    {
        return [
            'Incorrect name' => [422, array_merge($this->mockPayload, ['name' => 1234]), 'name'],
            'Incorrect email' => [422, array_merge($this->mockPayload, ['email' => 'asdasd']), 'email'],
            'Missing email' => [422, ['name' => $this->mockPayload['name'] ], 'email'],
            'Missing name' => [422,  ['email' => $this->mockPayload['email'] ], 'name'],
        ];
    }

    /**
     * @dataProvider createStatusProvider
     */
    public function test_that_swagger_and_serverside_fails_if_payload_is_not_correct($status, array $payload, string $expectedKeyOfFailure)
    {
        $initialCount = User::all()->count();

        $request = $this->wrapServerRequest($payload);

        try {
            $this->validator->validate($this->address, $request);
        } catch (InvalidBody $e) {
            $latestException = $e->getMessage();
            $previousException = $e->getPrevious()->getMessage();
            $exceptionLocation = implode(".", $e->getPrevious()->dataBreadCrumb()->buildChain());
            $expectedKeyOfFailure == $exceptionLocation ? $this->addToAssertionCount(1) : $this->fail("$latestException $previousException $exceptionLocation");
        }

        $response = $this->postJson($this->userEndpoint, $payload);
        $response->assertStatus($status, $response);
        $response->assertJsonValidationErrors([$expectedKeyOfFailure]);
        $this->assertDatabaseCount('users', $initialCount);
    }

    public function test_that_correct_payload_can_be_created_serverside()
    {
        $uniqueTimestamp = microtime(true);
        $mockPayloadUser = [
            'name' => "{$uniqueTimestamp}-fake-name",
            'email' => "{$uniqueTimestamp}test@email.com",
        ];
        $response = $this->postJson($this->userEndpoint, $mockPayloadUser);
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'name' => $mockPayloadUser['name'],
            'email' => $mockPayloadUser['email'],
        ]);
    }

    public function test_that_swagger_validates_good_payload()
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

    private function wrapServerRequest(array $payload): ServerRequest
    {
        return (new ServerRequest('post', $this->userEndpoint))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor(json_encode($payload)));
    }
}

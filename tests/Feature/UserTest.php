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
use Laravel\Sanctum\Sanctum;

class UserTest extends TestCase
{
    private string $userEndpoint = "/api/users";

    private OperationAddress $address;
    private RoutedServerRequestValidator $validator;

    private array $mockPayload = [
      'name' => 'fake_name',
      'email' => 'fake_email@test.com'
    ];

    private User $privilegedUser;
    private User $nonPrivilegedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = (new ValidatorBuilder)
            ->fromJson(file_get_contents('http://127.0.0.1:8000/api/swagger.json'))
            ->getRoutedRequestValidator();

        $this->privilegedUser = User::find(1);
        $this->nonPrivilegedUser = User::find(2);
    }

    public function test_all_users_getting_retrieved()
    {
        Sanctum::actingAs($this->privilegedUser);
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
        $request = $this->wrapServerRequest($payload, 'post');

        try {
            $this->validator->validate($this->address, $request);
        } catch (InvalidBody $e) {
            $latestException = $e->getMessage();
            $previousException = $e->getPrevious()->getMessage();
            $exceptionLocation = implode(".", $e->getPrevious()->dataBreadCrumb()->buildChain());
            $expectedKeyOfFailure == $exceptionLocation ? $this->addToAssertionCount(1) : $this->fail("$latestException $previousException $exceptionLocation");
        }

        Sanctum::actingAs($this->privilegedUser);
        $response = $this->postJson($this->userEndpoint, $payload);
        $response->assertStatus($status, $response);
        $response->assertJsonValidationErrors([$expectedKeyOfFailure]);
        $this->assertDatabaseCount('users', $initialCount);
    }

    public function test_that_correct_payload_cannot_be_created_due_to_unathorization()
    {
        $initialCount = User::all()->count();
        $response = $this->postJson($this->userEndpoint, $this->mockPayload);
        $response->assertStatus(401, $response);
        $this->assertDatabaseCount('users', $initialCount);
    }

    public function test_that_existing_user_cannot_be_updated_due_to_unathorization()
    {
        $user = User::find(1);
        $updatedMockPayloadUser = [
            'name' => 'unauthorizedNameChange',
            'email' => 'updating@email.com',
        ];

        $response = $this->patchJson("{$this->userEndpoint}/{$user->id}", $updatedMockPayloadUser);
        $response->assertStatus(401);

        $this->assertDatabaseMissing('users', [
            'name' => $updatedMockPayloadUser['name'],
            'email' => $updatedMockPayloadUser['email']
        ]);
    }

    public function test_that_user_cannot_see_list_of_all_users_due_to_unauthorization()
    {
        $userIdsInDatabase = User::pluck('id');
        $response = $this->json('GET', $this->userEndpoint);
        $response->assertStatus(401);
        $userIdsInResponse = collect(json_decode($response->content()))->pluck('id');
        $this->assertFalse($userIdsInResponse->diff($userIdsInDatabase)->isEmpty());
    }

    public function test_that_correct_payload_can_be_created_serverside()
    {
        $uniqueTimestamp = microtime(true);
        $mockPayloadUser = [
            'name' => "{$uniqueTimestamp}-fake-name",
            'email' => "{$uniqueTimestamp}test@email.com",
        ];

        Sanctum::actingAs($this->privilegedUser);
        $response = $this->postJson($this->userEndpoint, $mockPayloadUser);
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'name' => $mockPayloadUser['name'],
            'email' => $mockPayloadUser['email'],
        ]);
    }

    public function test_that_swagger_validates_good_payload()
    {
        $request = $this->wrapServerRequest($this->mockPayload, 'post');

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

    public function test_that_serverside_updates_user()
    {
        $user = User::find(3);
        $updatedMockPayloadUser = [
            'name' => 'updated_name',
            'email' => 'updated@email.com',
        ];

        Sanctum::actingAs($this->privilegedUser);
        $response = $this->patchJson("{$this->userEndpoint}/{$user->id}", $updatedMockPayloadUser);
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'name' => $updatedMockPayloadUser['name'],
            'email' => $updatedMockPayloadUser['email']
        ]);
    }

    public function test_that_swagger_reaches_correct_endpoint_method()
    {
        $updatedMockPayloadUser = [
            'name' => 'changedName',
            'email' => 'changing@email.com',
        ];
        $address = new OperationAddress("{$this->userEndpoint}/{userId}", 'patch');

        $request = (new ServerRequest('patch', "{$this->userEndpoint}/1"))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor(json_encode($updatedMockPayloadUser)));

        try {
            $this->validator->validate($address, $request);
            $this->addToAssertionCount(1);
        } catch (InvalidBody $e) {
            $latestException = $e->getMessage();
            $previousException = $e->getPrevious()->getMessage();
            $exceptionLocation = implode(".", $e->getPrevious()->dataBreadCrumb()->buildChain());
            $this->fail("$latestException $previousException $exceptionLocation");
        }
    }

    private function wrapServerRequest(array $payload, string $requestMethod): ServerRequest
    {
        $this->address = new OperationAddress($this->userEndpoint, $requestMethod);
        return (new ServerRequest('post', $this->userEndpoint))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor(json_encode($payload)));
    }
}

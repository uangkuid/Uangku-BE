<?php

namespace Tests\Feature\Api;

use App\Enums\UserStatus;
use App\Helpers\TokenHelper;
use App\Models\User;
use App\Models\UserSeasons;
use App\Services\UserSession\UserSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class JwtHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_and_refresh_tokens_carry_their_own_type(): void
    {
        $user = User::factory()->create();

        // Order matters: an access token issued AFTER a refresh token must not
        // inherit its claims.
        $first = JWTAuth::fromUser($user);
        $refresh = TokenHelper::generateRefreshToken($user);
        $second = JWTAuth::fromUser($user);
        $viaGuard = auth('api')->login($user);

        $this->assertSame('refresh', $this->claims($refresh)['typ']);

        foreach ([$first, $second, $viaGuard] as $access) {
            $claims = $this->claims($access);
            $this->assertSame('access', $claims['typ']);
            $this->assertSame(config('jwt.ttl') * 60, $claims['exp'] - $claims['iat']);
        }
    }

    public function test_refresh_token_lifetime_comes_from_config(): void
    {
        config(['jwt.refresh_token_ttl_days' => 3]);

        $claims = $this->claims(TokenHelper::generateRefreshToken(User::factory()->create()));

        $this->assertEqualsWithDelta(3 * 86400, $claims['exp'] - $claims['iat'], 5);
    }

    public function test_access_token_is_accepted_on_protected_route(): void
    {
        $user = User::factory()->create();

        $this->send('GET', '/api/user', bearer: JWTAuth::fromUser($user))->assertStatus(200);
    }

    public function test_refresh_token_is_rejected_as_bearer(): void
    {
        $user = User::factory()->create();

        $this->send('GET', '/api/user', bearer: TokenHelper::generateRefreshToken($user))
            ->assertStatus(401)
            ->assertJson(['status' => 401, 'data' => null]);
    }

    public function test_token_without_type_claim_is_rejected(): void
    {
        $user = User::factory()->create();

        // Same shape as the tokens issued before `typ` existed: validly signed,
        // claim absent altogether.
        $claims = $this->claims(JWTAuth::fromUser($user));
        unset($claims['typ']);
        $legacy = JWTAuth::manager()->getJWTProvider()->encode($claims);

        $this->assertArrayNotHasKey('typ', $this->claims($legacy));
        $this->send('GET', '/api/user', bearer: $legacy)->assertStatus(401);
    }

    public function test_every_auth_api_route_requires_an_access_token(): void
    {
        $user = User::factory()->create();
        $refresh = TokenHelper::generateRefreshToken($user);
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            $middleware = $route->gatherMiddleware();

            if (! in_array('auth:api', $middleware, true)) {
                continue;
            }

            $label = $route->methods()[0].' '.$route->uri();
            $this->assertContains('token.access', $middleware, "{$label} is missing token.access");

            $uri = '/'.preg_replace('/\{[^}]+\}/', (string) Str::uuid(), $route->uri());
            $response = $this->send($route->methods()[0], $uri, bearer: $refresh);

            $this->assertSame(401, $response->getStatusCode(), "{$label} accepted a refresh token");
            $checked++;
        }

        $this->assertGreaterThan(30, $checked);
    }

    public function test_refresh_works_without_a_bearer_token(): void
    {
        $user = User::factory()->create();
        $old = $this->issueSession($user);

        $response = $this->refresh($old);

        $response->assertStatus(200)->assertJsonStructure(['data' => ['token', 'refresh_token']]);
        $new = $response->json('data.refresh_token');
        $this->assertNotSame($old, $new);
        $this->assertSame('refresh', $this->claims($new)['typ']);
        $this->assertSame('access', $this->claims($response->json('data.token'))['typ']);
        $this->assertSame(1, UserSeasons::where('users', $user->id)->count());

        // Both halves of the new pair work.
        $this->send('GET', '/api/user', bearer: $response->json('data.token'))->assertStatus(200);
        $this->refresh($new)->assertStatus(200);
    }

    public function test_refresh_works_with_an_expired_access_token(): void
    {
        $user = User::factory()->create();
        $refresh = $this->issueSession($user);
        $access = JWTAuth::fromUser($user);

        $this->travel(config('jwt.ttl') + 5)->minutes();

        $this->send('GET', '/api/user', bearer: $access)->assertStatus(401);
        $this->refresh($refresh, bearer: $access)->assertStatus(200);
    }

    public function test_refresh_blacklists_the_bearer_access_token_sent_along(): void
    {
        $user = User::factory()->create();
        $access = JWTAuth::fromUser($user);

        $this->refresh($this->issueSession($user), bearer: $access)->assertStatus(200);

        $this->send('GET', '/api/user', bearer: $access)->assertStatus(401);
    }

    public function test_refresh_leaves_another_users_access_token_alone(): void
    {
        $other = JWTAuth::fromUser(User::factory()->create());

        $this->refresh($this->issueSession(User::factory()->create()), bearer: $other)->assertStatus(200);

        $this->send('GET', '/api/user', bearer: $other)->assertStatus(200);
    }

    public function test_refresh_ignores_an_unusable_bearer_token(): void
    {
        $this->refresh($this->issueSession(User::factory()->create()), bearer: 'garbage')
            ->assertStatus(200);
    }

    public function test_access_token_cannot_be_used_to_refresh(): void
    {
        $user = User::factory()->create();
        $access = JWTAuth::fromUser($user);
        // Even with a matching session row, the type alone must reject it.
        app(UserSessionService::class)->create(['users' => $user->id, 'refresh_token' => $access]);

        $this->refresh($access)->assertStatus(401);

        $this->assertSame(1, UserSeasons::where('users', $user->id)->count());
    }

    public function test_reusing_a_rotated_refresh_token_revokes_every_session(): void
    {
        $user = User::factory()->create();
        $bystander = User::factory()->create();
        $stolen = $this->issueSession($user);
        $otherDevice = $this->issueSession($user);
        $this->issueSession($bystander);

        $rotated = $this->refresh($stolen)->assertStatus(200)->json('data.refresh_token');

        $this->refresh($stolen)->assertStatus(401);

        $this->assertSame(0, UserSeasons::where('users', $user->id)->count());
        $this->assertSame(1, UserSeasons::where('users', $bystander->id)->count());
        $this->refresh($rotated)->assertStatus(401);
        $this->refresh($otherDevice)->assertStatus(401);
    }

    public function test_expired_refresh_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $refresh = $this->issueSession($user);

        $this->travel(config('jwt.refresh_token_ttl_days') * 24 + 1)->hours();

        $this->refresh($refresh)
            ->assertStatus(401)
            ->assertJson(['message' => 'Refresh token expired']);
    }

    public function test_refresh_token_with_a_forged_signature_revokes_nothing(): void
    {
        $user = User::factory()->create();
        $refresh = $this->issueSession($user);
        [$header, $payload] = explode('.', TokenHelper::generateRefreshToken($user));

        $this->refresh("{$header}.{$payload}.forged-signature")->assertStatus(401);

        $this->refresh($refresh)->assertStatus(200);
    }

    public function test_suspended_user_cannot_refresh(): void
    {
        $user = User::factory()->create();
        $refresh = $this->issueSession($user);
        $user->update(['status' => UserStatus::Suspended]);

        $this->refresh($refresh)->assertStatus(403);

        $this->assertSame(0, UserSeasons::where('users', $user->id)->count());
    }

    public function test_deleted_user_cannot_refresh(): void
    {
        $user = User::factory()->create();
        $refresh = $this->issueSession($user);
        $user->delete();

        $this->refresh($refresh)->assertStatus(401);
    }

    public function test_refresh_token_endpoint_is_throttled(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->refresh('not-a-jwt')->assertStatus(401);
        }

        $this->refresh('not-a-jwt')->assertStatus(429);
    }

    private function issueSession(User $user): string
    {
        $refreshToken = TokenHelper::generateRefreshToken($user);
        app(UserSessionService::class)->create(['users' => $user->id, 'refresh_token' => $refreshToken]);

        return $refreshToken;
    }

    private function refresh(string $refreshToken, ?string $bearer = null): TestResponse
    {
        return $this->send('POST', '/api/auth/refresh-token', ['refresh_token' => $refreshToken], $bearer);
    }

    /**
     * Send a request with a clean authentication state. The guard and the JWT
     * instances otherwise remember the previous request's token and user for
     * the whole test.
     *
     * @param  array<string, mixed>  $data
     */
    private function send(string $method, string $uri, array $data = [], ?string $bearer = null): TestResponse
    {
        $this->app['auth']->forgetGuards();
        $this->app->forgetScopedInstances();
        Facade::clearResolvedInstance('tymon.jwt.auth');
        $this->flushHeaders();

        if ($bearer !== null) {
            $this->withToken($bearer);
        }

        return $this->json($method, $uri, $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function claims(string $token): array
    {
        return json_decode(base64_decode(strtr(explode('.', $token)[1], '-_', '+/')), true);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Helpers\TokenHelper;
use App\Models\User;
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

# Laravel Sanctum with Vue 3 SPA

Pattern for integrating Laravel Sanctum authentication with a Vue 3 Single Page Application.

## Setup Steps

### 1. Install Sanctum
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 2. Configure Sanctum
In `config/sanctum.php`:
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),
'guard' => ['web', 'api'],
```

In `.env`:
```
SESSION_DRIVER=cookie
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

### 3. Update Kernel.php
Add Sanctum middleware to `api` middleware group in `app/Http/Kernel.php`:
```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

### 4. Vue 3 Auth Composition
Create `resources/js/stores/auth.js`:
```js
import { ref } from 'vue';

const user = ref(null);
const token = ref(localStorage.getItem('token') || '');

export function useAuth() {
    const isAuthenticated = () => !!token.value;
    const getUser = () => user.value;

    const setToken = (newToken) => {
        token.value = newToken;
        if (newToken) {
            localStorage.setItem('token', newToken);
        } else {
            localStorage.removeItem('token');
        }
    };

    const setUser = (newUser) => {
        user.value = newUser;
    };

    const clearAuth = () => {
        setToken(null);
        setUser(null);
    };

    const login = async (credentials) => {
        try {
            const res = await fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(credentials),
                credentials: 'include' // Important for Sanctum cookies
            });

            if (!res.ok) throw new Error('Login failed');

            const data = await res.json();
            setToken(data.token);
            setUser(data.user);
            return data;
        } catch (error) {
            throw error;
        }
    };

    const logout = async () => {
        try {
            await fetch('/api/logout', {
                method: 'POST',
                credentials: 'include'
            });
        } finally {
            clearAuth();
        }
    };

    const fetchUser = async () => {
        if (!isAuthenticated()) return null;
        try {
            const res = await fetch('/api/user', {
                credentials: 'include'
            });
            if (!res.ok) {
                clearAuth();
                return null;
            }
            const data = await res.json();
            setUser(data);
            return data;
        } catch (error) {
            clearAuth();
            return null;
        }
    };

    return {
        isAuthenticated,
        user: readonly(user),
        token: readonly(token),
        login,
        logout,
        fetchUser,
        setUser,
        clearAuth
    };
}
```

### 5. API Routes with Sanctum
In `routes/api.php`:
```php
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Protected API routes
    Route::apiResource('products', ProductController::class);
    // ... other routes
});
```

### 6. AuthController Methods
In `app/Http/Controllers/Api/AuthController.php`:
```php
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Always generate a new token for SPA
        $user->tokens()->delete(); // Revoke old tokens
        $token = $user->createToken('spa-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles.permissions'), // Load roles/permissions if using spatie/laravel-permission
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user()->load('roles.permissions'));
    }
}
```

### 7. Vue Router Auth Guards
In `resources/js/app.js`, enhance the router.beforeEach:
```js
router.beforeEach((to, from, next) => {
    const { isAuthenticated, fetchUser, user } = useAuth();

    if (to.meta.requiresAuth && !isAuthenticated()) {
        next('/login');
    } else if (to.meta.requiresGuest && isAuthenticated()) {
        next('/');
    } else if (to.meta.requiresAuth && isAuthenticated()) {
        // For protected routes, ensure we have fresh user data
        fetchUser().then(() => {
            if (to.meta.requiresAdmin) {
                const hasAdminRole = user.value?.roles?.some(role => role.name === 'admin');
                if (!hasAdminRole) {
                    next('/'); // Redirect to home if not admin
                } else {
                    next();
                }
            } else {
                next();
            }
        }).catch(() => {
            next('/login'); // If fetch fails, redirect to login
        });
    } else {
        next();
    }
});
```

### 8. Critical Pitfalls

#### ❌ Missing `credentials: 'include'` in fetch
Vue `fetch()` requests to Laravel Sanctum endpoints need `credentials: 'include'` to send/receive cookies. Without it, authentication won't persist.

#### ❌ Not revoking old tokens on login
If you don't revoke old tokens when a user logs in again, you can accumulate many tokens. Consider deleting old tokens before creating new ones, or implement token expiration.

#### ❌ Using localStorage for token (XSS risk)
While convenient, localStorage is vulnerable to XSS attacks. For higher security, consider using httpOnly cookies only (but this requires handling CSRF differently).

#### ❌ Sanctum stateful domains mismatch
Ensure `SANCTUM_STATEFUL_DOMAINS` in `.env` includes your frontend domain (e.g., localhost:5173 for Vite dev server, or your production domain).

#### ❌ Missing CSRF protection for stateful requests
When using Sanctum with stateful authentication (cookies), you still need CSRF protection for state-changing requests (POST/PUT/PATCH/DELETE). Include the CSRF token from the meta tag in your headers.

#### ❌ Not loading relationships in user response
If you use roles/permissions (with spatie/laravel-permission), remember to eager load them in your AuthController to avoid N+1 queries:
```php
return response()->json($request->user()->load('roles.permissions'));
```
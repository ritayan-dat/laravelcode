<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\TrustProxies::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        '/api/main/login' => \App\Http\Middleware\Main\Login::class,
        'authtoken_user' => \App\Http\Middleware\Main\AuthTokenUser::class,
        'authtoken_admin' => \App\Http\Middleware\Main\AuthTokenAdmin::class,
        'authtoken_manager' => \App\Http\Middleware\Main\AuthTokenManager::class,
        '/api/panel/admin/add_admin' => \App\Http\Middleware\Admins\AddAdmin::class,
        '/api/panel/admin/delete_admin' => \App\Http\Middleware\Admins\DeleteAdmin::class,
        '/api/panel/admin/add_user' => \App\Http\Middleware\Admins\AddUser::class,
        '/api/panel/admin/delete_user' => \App\Http\Middleware\Admins\DeleteUser::class,
        '/api/panel/admin/change_user_data' => \App\Http\Middleware\Admins\ChangeUserData::class,
        '/api/panel/admin/add_manager' => \App\Http\Middleware\Admins\AddManager::class,
        '/api/panel/admin/delete_manager' => \App\Http\Middleware\Admins\DeleteManager::class,
        '/api/panel/create_campaign' => \App\Http\Middleware\Campaign\CreateCampaign::class,
        '/api/panel/campaign/delete' => \App\Http\Middleware\Campaign\DeleteCampaign::class,
        '/api/forgot_password' => \App\Http\Middleware\Main\ForgotPassword::class,
        '/api/panel/user/create_contact' => \App\Http\Middleware\Contacts\CreateContact::class,
        '/api/panel/contact/delete_contact' => \App\Http\Middleware\Contacts\DeleteContact::class,
        'cors' => \App\Http\Middleware\Cors::class,
    ];
}

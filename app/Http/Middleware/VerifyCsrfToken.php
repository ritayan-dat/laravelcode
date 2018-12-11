<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
//        '/api/main/login',
    '/contacts/add_contacts',
        '/stripe/checkout',
        '/api/panel/admin/stat_monthly',
        '/api/panel/admin/stat_year',
        '/api/panel/admin/stat_type_payment',
        '/api/panel/admin/stat_chart_users',
        '/api/panel/admin/stat_activity',
        '/api/panel/user/stat_activity',
        '/api/panel/user/coupon_used_send',
        '/api/panel/user/coupon_profit',
        '/contacts/add_contacts_embed',
        '/message-forward',
    ];
}

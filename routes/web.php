<?php
    use Illuminate\Support\Facades\Artisan;
    use App\Cron\CronController;
    use App\Cron\StripeController;
    use App\Cron\SocialCronController;
    use App\Cron\RinglessvmDeliverCron;
    use App\Cron\ContactlimitCronController;
    use App\Cron\ReminderCronController;
    use App\Cron\CronRepeatController;
    use App\Cron\ContactBirthdayCron;
    use App\Cron\HolidayDateChangeCron;
//----------------------------------
//--------- Main pages ---------//
    Route::get('/', function (){
        return redirect('login');
    });

   
    Route::get('/panel', function (){
        return redirect('/panel/user');
    });
    Route::get('/login', 'oAuth\oAuthController@login_view');
    Route::get('/logout', 'oAuth\oAuthController@logout');
//----------------------------------
//---------  Manager pages ---------//
    Route::group(['middleware' => 'authtoken_manager'], function() {
        Route::get('/panel/manager', function () {
            return redirect('/panel/manager/profile');
        });
    });

//----------------------------------
//---------  Admin pages ---------//
    Route::group(['middleware' => 'authtoken_admin'], function() {
        Route::get('/panel/admin', 'Admins\AdminsController@main_view');
        Route::get('/panel/admin/admins', 'Admins\AdminsController@add_admins_view');
        Route::post('/api/panel/admin/add_admin',[
           'middleware' => '/api/panel/admin/add_admin',
           'uses' =>  'Admins\AdminsController@add_admin'
        ]);
    });
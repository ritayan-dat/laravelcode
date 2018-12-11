<?php

namespace App\Http\Middleware\Main;

use Closure;
use Exception;
use App\Model\Sessions;
use Illuminate\Support\Facades\DB;
class AuthTokenUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
            if(!Sessions::check_exists_session_data()) {
                return redirect('/login');
//            }else if(!$this->find_acc(Sessions::get_users_id_by_token(Sessions::get_token()))){
//                Sessions::delete_key();
//                return redirect('/login');
            }else{
                if(!Sessions::check_type_session('users_id')){
                    if(Sessions::check_type_session('admins_id')){
                        return redirect('/panel/admin');
                    }elseif(Sessions::check_type_session('managers_id')){
                        return redirect('/panel/manager');
                    }else{
                        return redirect('/login');
                    }
                }else{
                    $users_id = Sessions::get_users_id_by_token(Sessions::get_token());
                    $users_data = DB::table('users')->where('id','=',$users_id)->select('is_active','is_paid')->first();
                    if($users_data->is_paid==0){
                        return redirect('/payment');
                    }
                }
            }
            return $next($request);
    }
}

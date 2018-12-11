<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
class Sessions extends Model{
    protected $table = 'sessions';
    protected $hidden = ['token','ip','user_agent'];
    protected $dateFormat = 'U';
    protected $fillable = ['id','users_id','admins_id','managers_id','token','ip','user_agent'];
    /**
     * @return bool
     */
    public static function check_exists_session_data(){
        if (Session::has('token_key')) {
            $key  = Session::get('token_key');
            $result = self::where('token','=',$key)->exists();
            if(!$result){
                return false;
            }else{
                return true;
            }
        }else{
            return false;
        }
    }


    /**
     * @param $type
     *
     * @return bool
     */
    public static function check_type_session($type){
        $key  = Session::get('token_key');
        $result = self::where('token','=',$key)->first();
        if($result->$type != NULL){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $token
     *
     * @return mixed
     */
    public static function get_users_id_by_token($token){
        $result = self::where('token','=',$token)->first();
        return $result->users_id;
    }

    /**
     * @param $token
     *
     * @return mixed
     */
    public static function get_admins_id_by_token($token){
        $result = self::where('token','=',$token)->first();
        return $result->admins_id;
    }

    /**
     * @param $token
     *
     * @return mixed
     */
    public static function get_managers_id_by_token($token){
        $result = self::where('token','=',$token)->first();
        return $result->managers_id;
    }

    /**
     * @return mixed
     */
    public static function generate_token_key(){
        return Hash::make(md5(str_random(16)));
    }

    /**
     * @return mixed
     */
    public static function get_token(){
        return Session::get('token_key');
    }

    /**
     * @param $token
     * @param null $users_id
     * @param null $admins_id
     */
    public static function set_token_key($token, $users_id=NULL, $admins_id=NULL,$managers_id=NULL){
        Sessions::create([
            'admins_id'=>$admins_id,
            'users_id'=>$users_id,
            'managers_id'=> $managers_id,
            'token'=>$token,
            'ip'=>\Request::ip(),
            'user_agent'=>\Request::header('User-Agent')
        ]);
        Session::put('token_key',$token);
    }

    /**
     *Delete key
     */
    public static function delete_key(){
        $key  = Session::get('token_key');
        DB::table('sessions')->where('token','=',$key)->delete();
        Session::flush();
    }
}

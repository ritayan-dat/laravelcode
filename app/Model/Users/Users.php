<?php

namespace App\Model\Users;

use Illuminate\Database\Eloquent\Model;
use Image;
use DB;


class Users extends Model
{
    protected $table = 'users';
    protected $dateFormat = 'U';
    protected $fillable = ['id','created_by','on_call_phone_number','first_name','last_name','email','mobile_phone',
        'alt_phone','street_address','suite','state','city','postal_code','birthday_mounth','disclaimer',
        'username','password','industry','email_signature', 'postcard_signature','base_price',
        'postcard_price','rvm_price','text_price','is_paid','is_active', 'logo','picture','type',
        'unique_id','include_function','postcard_footer','return_address','address_side_content','use_logo_image','email_footer','email_networking','email_referral','email_elevator','email_prospect','email_disclaimer','temporary_password','last_paid','twilio_number','stripe_custid','contact_limit','company_name'];

    /*public function upload_file($file){
        $name = time().str_random(5).'.'.$file->getClientOriginalExtension();
        $destinationPath = public_path('photos');
        $file->move($destinationPath, $name);
        return asset('photos/'.$name);
    }*/
    public function upload_file($file){
        $name = time().str_random(5).'.'.$file->getClientOriginalExtension();
        $destinationPath = public_path('photos');

        $img = Image::make($file->getRealPath());
        $img->resize(250, 250, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath.'/'.$name);

       // $file->move($destinationPath, $name);
        return asset('photos/'.$name);
    }

    public function delete_photo($src){
        if(!empty($src)){
            $str = str_replace('https://crm.yobibyte.in.ua/public/','',$src);
            if(file_exists(public_path($str))) {
                unlink(public_path($str));
            }
        }
    }

    public function edit_mobile($mobile){
        $mobile = str_replace(" ","",$mobile);
        $mobile = str_replace("-","",$mobile);
        $mobile = str_replace("(","",$mobile);
        $mobile = str_replace(")","",$mobile);
        return $mobile;
    }
    public static function get_val($id,$stripe_id){
       
        $data =  DB::update('update users set stripe_custid = ? where id = ?',[$stripe_id,$id]);
        if(empty($data)){
            return false;
        }else{
            return $data;
        }
    }
    public static function get_value($from,$to){
        
        $data = DB::table('payments')
                        ->leftJoin('users', 'payments.users_id', '=', 'users.id')
                        ->whereBetween('payments.created_at', [$from, $to])
                        ->where('payments.is_paid', 1)
                        ->groupBy('payments.users_id')
                        ->selectRaw('users.first_name,users.email,users.stripe_custid,payments.id,payments.users_id,payments.price, sum(price) as sum')
                        ->get();
        if(empty($data)){
            return false;
        }else{
            return $data;
        }
    }
    public static function get_value_by_users($from,$to,$users_id){
        
        $data = DB::table('payments')
                        ->leftJoin('users', 'payments.users_id', '=', 'users.id')
                        ->whereBetween('payments.created_at', [$from, $to])
                        ->where('payments.is_paid', 1)
                        ->where('payments.users_id', $users_id)
                        ->groupBy('payments.users_id')
                        ->selectRaw('users.first_name,users.email,users.stripe_custid,payments.id,payments.users_id,payments.price, sum(price) as sum')
                        ->get();
        if(empty($data)){
            return false;
        }else{
            return $data;
        }
    }
    public static function insert_invoice($invoicearr){
        
        $data = DB::table('invoice')->insert($invoicearr);;
        if(empty($data)){
            return false;
        }else{
            return true;
        }
    }

    
   
}

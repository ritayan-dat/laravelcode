<?php

namespace App\Http\Controllers\Campaign;

use App\Model\Activity\ActivityReminder;
use App\Model\Contacts\Contacts;
use App\Model\PhoneNumbers\PhoneNumbers;
use App\Model\Sessions;
use App\Model\Users\Users;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Campaign\UniversalCampaign;
use App\Model\Campaign\UniversalCampaignEmail;
use App\Model\Campaign\UniversalCampaignRvmd;
use App\Model\Campaign\UniversalCampaignText;
use App\Model\Campaign\UniversalCampaignPostcard;
use Codedge\Fpdf\Fpdf\Fpdf;
use App\Model\Admins\Activity\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;

use App\Model\VideoTutorial\VideoTutorial;

use App\Model\Settings\SettingsModel;
use PDF;
use Image;
use App\Model\ReviewBuilder\ReviewBuilder;
use File;
use App\Model\Contacts\ContactsDopField;

class UniversalCampaignController extends Controller
{
    private $data,$campaign,$campaign_email,$campaign_text,$campaign_rvmd,$campaign_postcard;

    public function __construct(Request $request) {
        $this->data = $request->post();
        $this->campaign = new UniversalCampaign();
        $this->campaign_email = new UniversalCampaignEmail();
        $this->campaign_text = new UniversalCampaignText();
        $this->campaign_rvmd = new UniversalCampaignRvmd();
        $this->campaign_postcard = new UniversalCampaignPostcard();
    }

    public function campaign_add_user(){
        $users_id = Sessions::get_users_id_by_token(Sessions::get_token());
        $holiday_list =  DB::table('holyday_list')->get();      
        $exists_campaign = $this->campaign->where('type_acc','=','admin')->select('id','name')->get();
        $include = Users::where('id','=',$users_id)->select('twilio_number','include_function')->first();
        $number = $include->twilio_number;
        $video_tutorial = VideoTutorial::where('section','=','Universal campaign add')->select('link')->get();
        $settings = SettingsModel::where('id','=',1)->select('*')->first();
        $signature = Users::where('id','=',$users_id)->select('email_signature','email_footer','email_networking','email_referral','email_elevator','email_prospect','email_disclaimer','logo','picture')->first();

        $customField =  ContactsDopField::where('users_id','=',$users_id)->select('name')->groupBy('name')->get();

        return view('user.campaign_overview_add',[
            'include'=>$include->include_function,
            'campaigns'=>$exists_campaign,
            'users_id'=>$users_id,
            'video_tutorial'=> $video_tutorial,
            'settings'=> $settings,
            'number'=>$number,
            'signature'=>$signature,
            'holiday_list'=>$holiday_list,
            'customField'=>$customField
            ]);
    }

    public function get_exists_template(){
        $users_id = Sessions::get_users_id_by_token(Sessions::get_token());
        switch ($this->data['type']){
            case 'email':
            $data = $this->campaign_email->where('type_acc','=','admin')->select('text')->get();
            return response()->json(['success'=>true,'data'=>$data],200);
            break;

            case 'text':
            $data = $this->campaign_text->where('type_acc','=','admin')->select('text')->get();
            return response()->json(['success'=>true,'data'=>$data],200);
            break;

            case 'rvmd':
            $data = $this->campaign_rvmd->where('type_acc','=','admin')->select('src','record_name')->get();
            return response()->json(['success'=>true,'data'=>$data],200);
            break;
        }
    }


    public function campaign_user_view(){
        $users_id = Sessions::get_users_id_by_token(Sessions::get_token());
        $campaigns = $this->campaign->where('users_id','=',$users_id)->get();
        if(!empty($campaigns)) {
            foreach ($campaigns as $campaign) {
                $campaign->contacts_count = Contacts::where('assigned_campaign_id','=',$campaign->id)
                ->where('assigned_campaign_type','=','universal')
                ->count();
            }
        }
        return view('user.campaign_overview',['campaigns'=>$campaigns,'users_id'=>$users_id]);
    }

    public function get_campaign_data(){
        $campaign_data = $this->campaign->where('id','=',$this->data['campaign_id'])
        ->select('name', 'type_of_campaign','date','date_type')
        ->first();
        $campaign_text = UniversalCampaignText::where('universal_campaign_id', '=', $this->data['campaign_id'])->orderBy('delay_day','ASC')->get();
        $campaign_email = UniversalCampaignEmail::where('universal_campaign_id', '=', $this->data['campaign_id'])->orderBy('delay_day','ASC')->get();
        $campaign_rvmd = UniversalCampaignRvmd::where('universal_campaign_id', '=', $this->data['campaign_id'])->orderBy('delay_day','ASC')->get();
        $campaign_postcard = UniversalCampaignPostcard::where('universal_campaign_id', '=', $this->data['campaign_id'])->orderBy('delay_day','ASC')->get();
        /**********************19-06-2018************************/

        $users_id = Sessions::get_users_id_by_token(Sessions::get_token());
        $signature = Users::where('id','=',$users_id)->select('email_signature','email_footer','email_networking','email_referral','email_elevator','email_prospect','email_disclaimer','logo','picture')->first();
        /**********************19-06-2018************************/

        return response()->json(['success'=>true,
            'campaign_data'=>$campaign_data,
            'campaign_text'=>$campaign_text,
            'campaign_email'=>$campaign_email,
            'campaign_rvmd'=>$campaign_rvmd,
            'campaign_postcard'=>$campaign_postcard,
            ],200);
    }

    public function view_email_content(){
        $users_id = Sessions::get_users_id_by_token(Sessions::get_token());
        $campaign_email = UniversalCampaignEmail::where('id', '=', $this->data['id'])->orderBy('delay_day','ASC')->first();
        $updated_subject = $this->replace_contact_data($campaign_email->subject,$users_id);
        $updated_text =  $this->replace_logo_picture($this->replace_contact_data($campaign_email->text,$users_id),$users_id);
        return response()->json(['success'=>true,
            'updated_text'=>$updated_text,'updated_subject'=>$updated_subject
            ],200);
    }
    public function view_text_content(){
        $users_id = Sessions::get_users_id_by_token(Sessions::get_token());
        $campaign_text = UniversalCampaignText::where('id', '=', $this->data['id'])->orderBy('delay_day','ASC')->first();
        $updated_text = $this->replace_contact_data($campaign_text->text,$users_id);
        return response()->json(['success'=>true,
            'updated_text'=>$updated_text
            ],200);
    }
    public function replace_logo_picture($text,$users_id)
    {
        $users_data = Users::where('id','=',$users_id)->first();
        if(stristr($text, '{logo}'))
        {
            $text = str_replace('{logo}', '<img src="'.$users_data->picture.'">', $text);
        }
        if(stristr($text, '{picture}'))
        {
            $text = str_replace('{picture}', '<img src="'.$users_data->logo.'">', $text);
        }
        return $text;
    }
    public function create_campaign(){

        $insert_data = [
        'name'=>$this->data['name'],
        'type_acc'=>$this->data['type_acc'],
        'current_date'=>date('Y-m-d')
        ];

        if(!empty($this->data['import_campaing_id'])){
            $insert_data = array_merge($insert_data,['assigned_campaign_id'=>$this->data['import_campaing_id']]);
        }else{
            $insert_data = array_merge($insert_data,['assigned_campaign_id'=>0]);
        }

        /****************05-06-2018***********************/
        if(!empty($this->data['type_of_campaign_hidden'])){
            $insert_data = array_merge($insert_data,['type_of_campaign'=>$this->data['type_of_campaign_hidden']]);
        }else{
            $insert_data = array_merge($insert_data,['type_of_campaign'=>$this->data['type_of_campaign']]);
        }

        /****************05-06-2018***********************/

        if(!empty($this->data['assigned_number'])){
            $insert_data = array_merge($insert_data,['assigned_number'=>$this->data['assigned_number']]);
        }
        if(!empty($this->data['activity_reminder'])){
            $insert_data = array_merge($insert_data,['activity_reminder'=>$this->data['activity_reminder']]);
        }
        if(!empty($this->data['admins_id'])){
            $insert_data = array_merge($insert_data,['admins_id'=>$this->data['admins_id']]);
            $insert_data = array_merge($insert_data,['assigned_campaign_id'=> 0 ]);
            Activity::create_activity([
                'admins_id'=>$this->data['admins_id'],
                'type'=>'admin',                
                'message'=> 'Administrator was created universal campaign'
                ]);
        }else{
            $insert_data = array_merge($insert_data,['users_id'=>$this->data['users_id']]);
            Activity::create_activity([
                'admins_id'=>$this->data['users_id'],
                'type'=>'user',
                'message'=> 'Created universal campaign'
                ]);
        }

        $campaign_data = $this->campaign->create_new($insert_data);
        if(!empty($this->data['activity_reminder']) && !empty($this->data['users_id'])) {
            $days = date('Y-m-d', strtotime('+' . $this->data['activity_reminder'] . ' day'));
            ActivityReminder::create([
                'users_id' => $this->data['users_id'],
                'message' => 'Activity reminder for ' . $this->data['name'] . ' campaign!',
                'date' => $days,
                'campaign_id' => $campaign_data->id,
                'campaign_type' => 'universal'
                ]);
        }
//////////////////////////////////////////////////    
        if(!empty($this->data['import_campaing_id'])){ 

            $users_id = Sessions::get_users_id_by_token(Sessions::get_token());
            $signature = Users::where('id','=',$users_id)->select('email_signature','email_footer','email_networking','email_referral','email_elevator','email_prospect','email_disclaimer','logo','picture')->first();
            /******campaign_email********/           
            $campaign_email = UniversalCampaignEmail::where('universal_campaign_id', '=', $this->data['import_campaing_id'])->orderBy('delay_day','ASC')->get();
            if(!empty($campaign_email)){
                foreach($campaign_email as $value){
//echo $campaign_email->email_signature; die;
                    $finalText = str_replace('{email_signature}', $signature->email_signature, $value->text);
                    $finalText = str_replace('{email_footer}', $signature->email_footer, $finalText);
                    $finalText = str_replace('{email_networking}', $signature->email_networking, $finalText);
                    $finalText = str_replace('{email_referral}', $signature->email_referral, $finalText);
                    $finalText = str_replace('{email_elevator}', $signature->email_elevator, $finalText);
                    $finalText = str_replace('{email_prospect}', $signature->email_prospect, $finalText);
                    $finalText = str_replace('{email_disclaimer}', $signature->email_disclaimer, $finalText);
                    $insert_data = [
                    'universal_campaign_id'=> $campaign_data->id,
                    'subject' =>$value->subject,
                    'text' => $value->text,
                    'delay_day'=> $value->delay_day,
                    'type_acc'=> $this->data['type_acc'],
                    'message_name'=>$value->message_name,
                    'repeat_every_year'=>$value->repeat_every_year,
                    'email_attachment' =>$value->email_attachment,
                    ];
                    $email_data = $this->campaign_email->create_new($insert_data);
                }
            }              
            /**********campaign_text*********/
            $campaign_text = UniversalCampaignText::where('universal_campaign_id', '=', $this->data['import_campaing_id'])->orderBy('delay_day','ASC')->get();
            if(!empty($campaign_text)){
                foreach($campaign_text as $value){
                    $insert_data_text = [
                    'text' => $value->text,
                    'delay_day'=>$value->delay_day,
                    'type_acc'=>$this->data['type_acc'],
                    'text_name'=>$value->text_name,
                    'universal_campaign_id'=>$campaign_data->id,
                    'repeat_every_year'=>$value->repeat_every_year,
                    ];            
                    $text_data = $this->campaign_text->create_new($insert_data_text);
                }
            }            
            /***************campaign_voice**************/
            $campaign_rvmd = UniversalCampaignRvmd::where('universal_campaign_id', '=', $this->data['import_campaing_id'])->orderBy('delay_day','ASC')->get();
            if(!empty($campaign_rvmd)){
                foreach($campaign_rvmd as $value){
                    $insert_data_rvmd = [
                    'src' =>$value->src,
                    'delay_day' => $value->delay_day,
                    'type_acc' =>$this->data['type_acc'],
                    'repeat_every_year'=> $value->repeat_every_year,
                    'record_name'=> $value->record_name,
                    'universal_campaign_id'=>$campaign_data->id,
                    ];  

                    $rvmd_data = $this->campaign_rvmd->create_new($insert_data_rvmd);
                }
            }
            /**************post card message*************/ 
            $campaign_postcard = UniversalCampaignPostcard::where('universal_campaign_id', '=', $this->data['import_campaing_id'])->orderBy('delay_day','ASC')->get();
            $users = Users::where('id','=',$users_id)->first();
//dd($users->postcard_footer);
            if(!empty($campaign_postcard)){
                foreach($campaign_postcard as $value){
                    $front_html = str_replace('{return_address}', $users->return_address , $value->front_html);
                    $front_html = str_replace('{address_side_content}', wordwrap( $users->address_side_content, 40, "\n", true ) , $front_html);
                    if($users->use_logo_image==0)
                    {
                        $front_html = str_replace('{picture}', '<img src="'.$users->logo.'" style="width:100%">' , $front_html); 
                        $front_html = str_replace('{logo}', '' , $front_html); 
                    }
                    else
                    {
                        $front_html = str_replace('{logo}', '<img src="'.$users->picture.'" style="width:100%">' , $front_html); 
                        $front_html = str_replace('{picture}', '' , $front_html); 
                    }
//$front_html = str_replace('{picture}', '<img src="'.$users->logo.'" style="width:100%">' , $front_html); 
//$front_html = str_replace('{logo}', '<img src="'.$users->picture.'" style="width:100%">' , $front_html); 
                    $front_html = str_replace('{postcard_footer}', $users->postcard_footer , $front_html); 
                    $front_html = str_replace('{editor_content}', $this->replace_contact_data($value->editor_content,$users_id) , $front_html);
//dd($front_html);
                    /********front end html to image***************/
                    $name = $this->campaign_postcard->generate_name();
                    $photo_name=$name.'.jpg';
                    $full_img_src=public_path('postcard/' . $photo_name);
                    $full_pdf_src=public_path('postcard/' . $photo_name.'.pdf');

                    $output = PDF::loadHTML($front_html)->setPaper('a4', 'landscape')->setWarnings(false)->save($full_pdf_src);

                    $pdf = new \Spatie\PdfToImage\Pdf($full_pdf_src);
                    $pdf->saveImage($full_img_src);

                    $img = Image::make($full_img_src)->resize(800, 600)->save( $full_img_src );
                    /********front end html to image***************/
                    $insert_data = [
                    'delay_day'=>$value->delay_day,
                    'type_acc'=>$this->data['type_acc'],
                    'postcard_name'=>$value->postcard_name,
                    'repeat_every_year'=>$value->repeat_every_year,
                    'front_src'=>url('postcard/'.$photo_name),
                    'back_src'=>$value->back_src,
                    'universal_campaign_id'=>$campaign_data->id,
                    'front_html'=>$value->front_html,
                    'editor_content'=>$value->editor_content,
                    ];

                    $postcard_data = $this->campaign_postcard->create_new($insert_data);

                }
            }
//newly inserted data
            $campaign_postcard_new = UniversalCampaignPostcard::where('universal_campaign_id', '=', $campaign_data->id)->orderBy('delay_day','ASC')->get();            

            $campaign_email_new = UniversalCampaignEmail::where('universal_campaign_id', '=', $campaign_data->id )->orderBy('delay_day','ASC')->get();

            $campaign_text_new = UniversalCampaignText::where('universal_campaign_id', '=', $campaign_data->id)->orderBy('delay_day','ASC')->get();

            $campaign_rvmd_new= UniversalCampaignRvmd::where('universal_campaign_id', '=', $campaign_data->id)->orderBy('delay_day','ASC')->get();

            return response()->json(['success'=>true,'campaign_id'=>$campaign_data->id,'campaign_email'=>$campaign_email,'campaign_text'=>$campaign_text,'campaign_rvmd'=>$campaign_rvmd,'campaign_postcard'=>$campaign_postcard,'campaign_email_new'=>$campaign_email_new,'campaign_text_new'=>$campaign_text_new,'campaign_rvmd_new'=>$campaign_rvmd_new,'campaign_postcard_new'=>$campaign_postcard_new],200);
        }else{

            return response()->json(['success'=>true,'campaign_id'=>$campaign_data->id],200);
        }   
/////////////////////////////////////////////////////////
    }

    public function edit_campaign_data(){
        $insert_data=[];
        if(!empty($this->data['users_id'])){
            $where = [['users_id','=',$this->data['users_id']]];
        }elseif (!empty($this->data['admins_id'])){
            $where = [['admins_id','=',$this->data['admins_id']]];
        }
        if(!empty($this->data['name'])){
            if($this->campaign->check_exists_name($where,$this->data['name'],$this->data['universal_campaign_id'],$this->data['type_acc'])) {
                $insert_data = array_merge($insert_data, ['name' => $this->data['name']]);
            }else{
                return response()->json(['success'=>false,'message'=>'Campaign with current name is already exists!'],200);
            }
        }
        if(!empty($this->data['type_of_campaign'])){
            $insert_data = array_merge($insert_data,['type_of_campaign'=>$this->data['type_of_campaign']]);
        }
        if(!empty($this->data['assigned_number'])){
            $insert_data = array_merge($insert_data,['assigned_number'=>$this->data['assigned_number']]);
        }
        if(!empty($this->data['activity_reminder'])){
            $insert_data = array_merge($insert_data,['activity_reminder'=>$this->data['activity_reminder']]);
        }
        $insert_data = array_merge($insert_data,['date' =>'']);
        $insert_data = array_merge($insert_data,['date' =>'']);
        $this->campaign->update_data($insert_data,[
            'id'=>$this->data['universal_campaign_id']
            ]);
        if(!empty($this->data['activity_reminder']) && !empty($this->data['users_id'])) {
            $days = date('Y-m-d', strtotime('+' . $this->data['activity_reminder'] . ' day'));
            if (!$activity_data = ActivityReminder::check_exists([
                ['campaign_id', '=', $this->data['universal_campaign_id']]
                , ['campaign_type', '=', 'universal'],[ 'users_id', '=', $this->data['users_id']]])) {
                ActivityReminder::create([
                    'users_id' => $this->data['users_id'],
                    'message' => 'Activity reminder for ' . $this->data['name'] . ' universal campaign!',
                    'date' => $days,
                    'campaign_id' => $this->data['universal_campaign_id'],
                    'campaign_type' => 'universal'
                    ]);
        }else{
            ActivityReminder::where('id','=',$activity_data->id)->update([
                'date'=>$days
                ]);
        }
    }
    return response()->json(['success'=>true,'message'=>'success edit campaign data'],200);
}

public function universal_campaign_edit_admin_view(){
    if($data = $this->campaign->check_exists([['id','=',$this->data['id']]])) {
        $admins_id = Sessions::get_admins_id_by_token(Sessions::get_token());
        $campaign = UniversalCampaign::where('id', '=', $this->data['id'])->first();
        $campaign_text = UniversalCampaignText::where('universal_campaign_id', '=', $this->data['id'])->orderByRaw('CAST(delay_day AS DECIMAL) ASC')->get();
        $campaign_email = UniversalCampaignEmail::where('universal_campaign_id', '=', $this->data['id'])->orderByRaw('CAST(delay_day AS DECIMAL) ASC')->get();
        $campaign_rvmd = UniversalCampaignRvmd::where('universal_campaign_id', '=', $this->data['id'])->orderByRaw('CAST(delay_day AS DECIMAL) ASC')->get();
        $campaign_postcard = UniversalCampaignPostcard::where('universal_campaign_id', '=', $this->data['id'])->orderByRaw('CAST(delay_day AS DECIMAL) ASC')->get();
        $holiday_list =  DB::table('holyday_list')->get();
        return view('admin.universal_campaign_edit', [
            'campaign' => $campaign,
            'campaign_text' => $campaign_text,
            'campaign_email' => $campaign_email,
            'campaign_rvmd' => $campaign_rvmd,
            'campaign_postcard' => $campaign_postcard,
            'campaign_id' => $this->data['id'],
            'admins_id' => $admins_id,
            'holiday_list'=>$holiday_list
            ]);
    }else{
        return redirect('/panel/admin/universal-campaign-overview');
    }
}

public function universal_campaign_edit_user_view(){
    if($data = $this->campaign->check_exists([['id','=',$this->data['id']]])) {
        $users_id = Sessions::get_users_id_by_token(Sessions::get_token());
        $campaign = UniversalCampaign::where('id', '=', $this->data['id'])->first();
        $campaign_text = UniversalCampaignText::where('universal_campaign_id', '=', $this->data['id'])->orderByRaw('CAST(delay_day AS DECIMAL) ASC')->get();
        $campaign_email = UniversalCampaignEmail::where('universal_campaign_id', '=', $this->data['id'])->orderByRaw('CAST(delay_day AS DECIMAL) ASC')->get();
        $campaign_rvmd = UniversalCampaignRvmd::where('universal_campaign_id', '=', $this->data['id'])->orderByRaw('CAST(delay_day AS DECIMAL) ASC')->get();
        $campaign_postcard = UniversalCampaignPostcard::where('universal_campaign_id', '=', $this->data['id'])->orderByRaw('CAST(delay_day AS DECIMAL) ASC')->get();
        $signature = Users::where('id','=',$users_id)->select('email_signature','email_footer','logo','picture')->first();
        $include = Users::where('id','=',$users_id)->select('twilio_number','include_function')->first();
        $video_tutorial = VideoTutorial::where('section','=','Universal campaign edit')->select('link')->get();
        $number = $include->twilio_number;
        $holiday_list =  DB::table('holyday_list')->get();
        $customField =  ContactsDopField::where('users_id','=',$users_id)->select('name')->groupBy('name')->get();

        return view('user.campaign_overview_edit',[
            'campaign' => $campaign,
            'number'=>$number,
            'video_tutorial'=> $video_tutorial,
            'campaign_text' => $campaign_text,
            'campaign_email' => $campaign_email,
            'campaign_rvmd' => $campaign_rvmd,
            'campaign_postcard' => $campaign_postcard,
            'campaign_id' => $this->data['id'],
            'users_id' => $users_id,
            'signature'=>$signature,
            'include'=>$include->include_function,
            'holiday_list'=>$holiday_list,
            'customField' =>$customField
            ]);
    }else{
        return redirect('/panel/user/campaign-overview');
    }
}

public function delete_campaign(){
    $this->campaign_postcard->delete_postcard($this->data['campaign_id'],'front');
    $this->campaign_postcard->delete_postcard($this->data['campaign_id'],'back');
    $this->campaign_rvmd->delete_audio($this->data['campaign_id']);
    $this->campaign->delete_campaign([['id','=',$this->data['campaign_id']]]);
    if(!empty($this->data['admins_id'])) {
        Activity::create_activity([
            'admins_id' => $this->data['admins_id'],
            'type' => 'admin',
            'message' => 'Administrator was deleted universal campaign'
            ]);
    }else{
        Activity::create_activity([
            'users_id'=>$this->data['users_id'],
            'type'=>'user',
            'message'=> 'Was deleted universal campaign'
            ]);
    }
    return response()->json(['success'=>true,'message'=>'success deleted campaign']);
}

public function delete_email(){
    $this->campaign_email->where('id','=',$this->data['email_id'])->delete();
    return response()->json(['success'=>true],200);
}

public function delete_rvmd(){
    $rvmd_data = $this->campaign_rvmd->where('id','=',$this->data['rvmd_id'])->select('src')->first();
    if(file_exists($rvmd_data->src)){
        unset($rvmd_data->src);
    }
    $this->campaign_rvmd->where('id','=',$this->data['rvmd_id'])->delete();
    return response()->json(['success'=>true],200);
}

public function delete_postcard(){
    $postcard_data = $this->campaign_postcard->where('id','=',$this->data['postcard_id'])->select('front_src','back_src')->first();
    if(!empty($postcard_data->front_src)){
        if(file_exists($postcard_data->front_src)){
            unset($postcard_data->front_src);
        }
    }
    if(!empty($postcard_data->back_src)){
        if(file_exists($postcard_data->back_src)){
            unset($postcard_data->back_src);
        }
    }
    $this->campaign_postcard->where('id','=',$this->data['postcard_id'])->delete();
    return response()->json(['success'=>true],200);

}

public function delete_text(){
    $this->campaign_text->where('id','=',$this->data['text_id'])->delete();
    return response()->json(['success'=>true],200);
}
public function create_email(Request $request){         
    $insert_data = [
    'subject' => $this->data['subject'],
    'text' => $this->data['text'],
    'delay_day'=>$this->data['delay_day'],
    'type_acc'=>$this->data['type_acc'],
    'message_name'=>$this->data['message_name'],
    ];
    if(!empty($this->data['repeat_every_year'])){
        $insert_data = array_merge($insert_data,['repeat_every_year'=>$this->data['repeat_every_year']]);
    }else{
        $insert_data = array_merge($insert_data,['repeat_every_year'=>0]);
    }
    $insert_data = array_merge($insert_data,['email_day_type'=>'']);
    $insert_data = array_merge($insert_data,['holiday_date'=>'']);

    if(!empty($request->file('email_attachment'))){
        $url_picture = $this->campaign_email->upload_file($request->file('email_attachment'));
        $insert_data = array_merge(['email_attachment'=>$url_picture],$insert_data);
    }elseif(!empty($this->data['email_hidden_attachment'])){
        $insert_data = array_merge(['email_attachment'=>$this->data['email_hidden_attachment']],$insert_data);
    }

    $email_id = 0;
    if(!empty($this->data['email_id'])){
// dd($this->data);
        if($data = $this->campaign_email->check_exists([['id','=',$this->data['email_id']]])) {
            if(empty($this->data['email_hidden_attachment'])){                    
                if($this->data['email_hidden_attachment']==''){                        
                    $atta_data = $this->campaign_email->where('id','=',$this->data['email_id'])->first();
                    if($atta_data){                            
                        $insert_data = array_merge(['email_attachment'=>''],$insert_data);
                        $path = public_path().'/file_attachment/' . $atta_data->email_attachment;
                        File::delete($path);

                    }
                }
            }

            $this->campaign_email->update_data($insert_data,[
                'id'=>$this->data['email_id']
                ]);
            $email_id = $this->data['email_id'];
        }
        $type = 'edit';
    }else{
        $insert_data = array_merge($insert_data,['universal_campaign_id'=>$this->data['universal_campaign_id']]);
        $email_data = $this->campaign_email->create_new($insert_data);
        $email_id = $email_data->id;
        $type = 'new';

        $import_id_exist = UniversalCampaign::where('assigned_campaign_id', '=', $this->data['universal_campaign_id'])->get();

        if(!empty($import_id_exist)){
            foreach($import_id_exist as $value){
                $insert_data = array_merge($insert_data,['universal_campaign_id'=>$value->id]);
                $email_data = $this->campaign_email->create_new($insert_data);
            }

        }
    }
    return response()->json(['success'=>true,'type'=>$type,'message'=>'success created email',
        'email_id'=>$email_id,
        'delay_day'=>$this->data['delay_day'],
        'message_name'=>$this->data['message_name'],
        'text'=>$this->data['text'],
        'subject'=>$this->data['subject'],
        'repeat'=>$insert_data['repeat_every_year'],
        'email_attachment'=>(isset($insert_data['email_attachment'])?$insert_data['email_attachment']:''),
        ],200);
}

public function create_text(){
    $insert_data = [
    'text' => $this->data['text_desc'],
    'delay_day'=>$this->data['delay_day'],
    'type_acc'=>$this->data['type_acc'],
    'text_name'=>$this->data['text_name'],
    ];
    if(!empty($this->data['repeat_every_year'])){
        $insert_data = array_merge($insert_data,['repeat_every_year'=>$this->data['repeat_every_year']]);
    }else{
        $insert_data = array_merge($insert_data,['repeat_every_year'=>0]);
    }
    $insert_data = array_merge($insert_data,['text_day_type'=>0]);
    $insert_data = array_merge($insert_data,['holiday_date'=>0]);

    if(!empty($this->data['text_id'])){
        if($this->campaign_text->check_exists([['id','=',$this->data['text_id']]])) {
            $this->campaign_text->update_data($insert_data, [
                'id' => $this->data['text_id']
                ]);
        }
        $type = 'edit';
        $text_id = $this->data['text_id'];
    }else{
        $insert_data = array_merge($insert_data,['universal_campaign_id'=>$this->data['universal_campaign_id']]);
        $text_data = $this->campaign_text->create_new($insert_data);
        $type = 'new';
        $text_id = $text_data->id;

        $import_id_exist = UniversalCampaign::where('assigned_campaign_id', '=', $this->data['universal_campaign_id'])->get();

        if(!empty($import_id_exist)){
            foreach($import_id_exist as $value){
//echo $value->id;
                $insert_data = array_merge($insert_data,['universal_campaign_id'=>$value->id]);
                $email_data = $this->campaign_text->create_new($insert_data);
            }

        }
    }
    return response()->json(['success'=>true,
        'message'=>'success created text',
        'text_id'=>$text_id,
        'type'=>$type,
        'text'=>$this->data['text_desc'],
        'delay_day'=>$this->data['delay_day'],
        'text_name'=>$this->data['text_name'],
        'repeat'=>$insert_data['repeat_every_year'],
        ],200);
}

public function create_audio(){
    try {
        $insert_data = [
        'src' => $this->data['src'],
        'delay_day' => $this->data['delay_day'],
        'type_acc' => $this->data['type_acc'],
        ];
        if(!empty($this->data['repeat_every_year'])){
            $insert_data = array_merge($insert_data,['repeat_every_year'=>$this->data['repeat_every_year']]);
        }else{
            $insert_data = array_merge($insert_data,['repeat_every_year'=>0]);
        }

        if (!empty($this->data['record_name'])) {
            $insert_data = array_merge($insert_data, ['record_name' => $this->data['record_name']]);
        }
        $insert_data = array_merge($insert_data,['holiday_date'=>0]);
        $insert_data = array_merge($insert_data,['rvm_day_type'=>0]);
        if(!empty($this->data['rvmd_id'])){
            if($this->campaign_rvmd->check_exists([['id','=',$this->data['rvmd_id']]])) {
                $this->campaign_rvmd->update_data($insert_data, [
                    'id' => $this->data['rvmd_id']
                    ]);
                $rvmd_id = $this->data['rvmd_id'];
                $type = 'edit';
            }

        } else {

            $insert_data = array_merge($insert_data, ['universal_campaign_id' => $this->data['universal_campaign_id']]);
            $rvmd_data = $this->campaign_rvmd->create_new($insert_data);
            $rvmd_id = $rvmd_data->id;
            $type = 'new';
            $import_id_exist = UniversalCampaign::where('assigned_campaign_id', '=', $this->data['universal_campaign_id'])->get();

            if(!empty($import_id_exist)){
                foreach($import_id_exist as $value){
                    $insert_data = array_merge($insert_data,['universal_campaign_id'=>$value->id]);
                    $email_data = $this->campaign_rvmd->create_new($insert_data);
                }
            }
        }            
        return response()->json(['success' => true,
            'message' => 'audio success created',
            'rvmd_id'=>$rvmd_id,
            'src'=>$this->data['src'],
            'delay_day'=>$this->data['delay_day'],
            'repeat'=>$insert_data['repeat_every_year'],
            'type'=>$type,
            'record_name'=>$this->data['record_name'],
            ], 200);
    }catch (\Exception $e){
        return response()->json(['success' => false, 'message' => $e->getMessage()], 200);
    }
}

public function create_postcard(){
    $pdf = new FPDF('L','pt','a5');
    $insert_data = [
    'delay_day'=>$this->data['delay_day'],
    'type_acc'=>$this->data['type_acc'],
    'postcard_name'=>$this->data['postcard_name'],
    ];
    if(!empty($this->data['repeat_every_year'])){
        $insert_data = array_merge($insert_data,['repeat_every_year'=>$this->data['repeat_every_year']]);
    }else{
        $insert_data = array_merge($insert_data,['repeat_every_year'=>0]);
    }
    $insert_data = array_merge($insert_data,['holiday_date'=>0]);
    $insert_data = array_merge($insert_data,['postcard_day_type'=>0]);

    $photo_links = self::upload_postcard();
    if(!empty($photo_links['front_src'])) {
        $insert_data = array_merge($insert_data, ['front_src' => $photo_links['front_src']]);
    }
    if(!empty($photo_links['back_src'])) {
        $insert_data = array_merge($insert_data, ['back_src' => $photo_links['back_src']]);
    }
    if(!empty($this->data['front_html'])) {
        if(strstr($this->data['front_html'],'{address_side_content}') || strstr($this->data['front_html'],'{logo}')  || strstr($this->data['front_html'],'{picture}'))
        {
            $insert_data = array_merge($insert_data, ['front_html' => $this->data['front_html']]);
        }
    }
    if(!empty($this->data['text_postcard'])) {
        $insert_data = array_merge($insert_data, ['editor_content' => $this->data['text_postcard']]);
    }
//dd($insert_data);
    if(!empty($this->data['postcard_id'])){
        if($this->campaign_postcard->check_exists([['id','=',$this->data['postcard_id']]])) {
            $this->campaign_postcard->update_data($insert_data, [
                'id' => $this->data['postcard_id']
                ]);
        }
        $type = 'edit';
        $postcard_id = $this->data['postcard_id'];
    }else{
        $insert_data = array_merge($insert_data,['universal_campaign_id'=>$this->data['universal_campaign_id']]);
        $postcard_data = $this->campaign_postcard->create_new($insert_data);
        $type = 'new';
        $postcard_id = $postcard_data->id;
        $this->campaign_postcard->update_data($insert_data, [
            'id' => $postcard_id
            ]);
    }
    return response()->json(['success'=>true,
        'message'=>'success created postcard',
        'postcard_id'=>$postcard_id,
        'type'=>$type,
        'front_src'=>$photo_links['front_src'],
        'back_src'=>$photo_links['back_src'],
        'delay_day'=>$insert_data['delay_day'],
        'repeat'=>$insert_data['repeat_every_year'],
        'postcard_name'=>$insert_data['postcard_name'],
        ],200);
}


public function upload_postcard(){
    $front_src = '';
    $back_src = '';
    if(!empty($this->data['front_src'])){
        if(!empty($this->data['postcard_id'])) {
            $this->campaign_postcard->delete_postcard($this->data['postcard_id'], 'front');
        }
        $name = $this->campaign_postcard->generate_name();
        $photo_name=$name.'.jpg';
        $full_img_src=public_path('postcard/' . $photo_name);
        $full_pdf_src=public_path('postcard/' . $photo_name.'.pdf');
        if(Sessions::check_type_session('users_id')) {
            $users_id = Sessions::get_users_id_by_token(Sessions::get_token());
            $users = Users::where('id','=',$users_id)->first();
            $front_html = str_replace('{return_address}', $users->return_address , $this->data['front_html']);
            $front_html = str_replace('{address_side_content}', wordwrap( $users->address_side_content, 40, "\n", true ) , $front_html);
            if($users->use_logo_image==0)
            {
                $front_html = str_replace('{picture}', '<img src="'.$users->logo.'" style="width:100%">' , $front_html); 
                $front_html = str_replace('{logo}', '' , $front_html); 
            }
            else
            {
                $front_html = str_replace('{logo}', '<img src="'.$users->picture.'" style="width:100%">' , $front_html); 
                $front_html = str_replace('{picture}', '' , $front_html); 
            }
            $front_html = str_replace('{postcard_footer}', $users->postcard_footer , $front_html); 
            $front_html = str_replace('{editor_content}', $this->replace_contact_data($this->data['text_postcard'],$users_id) , $front_html); 
            /********front end html to image***************/
            $output = PDF::loadHTML($front_html)->setPaper('a4', 'landscape')->setWarnings(false)->save($full_pdf_src);

            $pdf = new \Spatie\PdfToImage\Pdf($full_pdf_src);
            $pdf->saveImage($full_img_src);

            $img = Image::make($full_img_src)->resize(800, 600)->save( $full_img_src );
        }
        else
        {
            $front_html = str_replace('{editor_content}', $this->data['text_postcard'] , $this->data['front_html']); 
            $front_html = str_replace('<div class="addressSideContent" style="margin-top: 2px;">', '<div class="addressSideContent" style="margin-top: 180px;">' , $front_html); 
            /********front end html to image***************/
            $output = PDF::loadHTML($front_html)->setPaper('a4', 'landscape')->setWarnings(false)->save($full_pdf_src);
            $pdf = new \Spatie\PdfToImage\Pdf($full_pdf_src);
            $pdf->saveImage($full_img_src);

            $img = Image::make($full_img_src)->resize(800, 600)->save( $full_img_src );
        }
        $front_src  =  asset('postcard/'.$photo_name);
    }else{
        $front_src = $this->data['front_src_url'];
    }
    if(!empty($this->data['back_src'])){
        if(!empty($this->data['postcard_id'])) {
            $this->campaign_postcard->delete_postcard($this->data['postcard_id'], 'back');
        }
        $name = $this->campaign_postcard->generate_name();
        $photo_name=$name.'.jpg';
        $full_img_src=public_path('postcard/' . $photo_name);
        $full_pdf_src=public_path('postcard/' . $photo_name.'.pdf');
        $this->campaign_postcard->upload_img($this->data['back_src'],$full_img_src);
        $this->campaign_postcard->upload_pdf($full_img_src,$full_pdf_src);
        $back_src = asset('postcard/'.$photo_name);
    }else{
        $back_src = $this->data['back_src_url'];
    }
    return ['front_src'=>$front_src,'back_src'=>$back_src];
}
public function replace_contact_data($text,$users_id){
    $array_words = ['{review_link}','{user_first_name}',
    '{user_last_name}','{user_email}','{user_mobile_phone}','{user_on_call_phone_number}',
    '{user_alt_phone}','{user_street_address}','{user_suite}','{user_city}',
    '{user_state}','{user_postal_code}','{user_birthday_mounth}','{user_industry}',
    '{email_signature}','{email_footer}','{email_prospect}','{email_elevator}',
    '{email_referral}','{email_disclaimer}','{email_holidays}','{email_networking}','{user_postcard_footer}',
    '{user_base_price}','{user_postcard_price}','{user_rvm_price}','{user_text_price}',
    '{user_twilio_number}','{user_logo}','{user_picture}','{user_type}','{user_include_function}','{user_company_name}','{user_rrp_link}'];
    foreach ($array_words as $key=>$array_word) {
        if(stristr($text, $array_word)){
            $db_field = substr($array_word, 0, -1);
            $db_field = substr($db_field, 1, strlen($db_field));
            if($key > 0)
            {
                $data_key = str_replace('{', '', $array_word);
                $data_key = str_replace('}', '', $data_key);
                $data_key = str_replace('user_', '', $data_key);
                $users_data = Users::where('id','=',$users_id)->select($data_key)->first();
                $val = $users_data->$data_key;
            }
            elseif($array_word=='{disclaimer}'){
                $users_data = Users::where('id','=',$users_id)->select('disclaimer')->first();
                $val = $users_data->disclaimer;
            }
            elseif ($array_word=='{review_link}'){
                $users_data = ReviewBuilder::where('users_id','=',$users_id)->select('unique_id')->first();
                if(!empty($users_data)){
                    $val = asset('/review/'.$users_data->unique_id);
                }else{
                    $val = '';
                }
            }
            if(!empty($val)) {
                $text = str_replace($array_word, $val, $text);
            }
            else{
                $text = str_replace($array_word, '', $text);
            }
        }
    }
    return $text;
}
public function copy(){
    $campaign_id = $this->data['campaign_id'];
    $users_id = Sessions::get_users_id_by_token(Sessions::get_token());
    $campaign = UniversalCampaign::where('id', '=', $campaign_id)->first();
    $campaign_text = UniversalCampaignText::where('universal_campaign_id', '=', $campaign_id)->orderBy('delay_day','ASC')->get();
    $campaign_email = UniversalCampaignEmail::where('universal_campaign_id', '=', $campaign_id)->orderBy('delay_day','ASC')->get();
    $campaign_rvmd = UniversalCampaignRvmd::where('universal_campaign_id', '=', $campaign_id)->orderBy('delay_day','ASC')->get();
    $campaign_postcard = UniversalCampaignPostcard::where('universal_campaign_id', '=', $campaign_id)->orderBy('delay_day','ASC')->get();
    if($campaign->count()>0)
    {
        $insert_data = [
        'users_id'=> $campaign->users_id,
        'admins_id' =>$campaign->admins_id,
        'type_acc' => $campaign->type_acc,
        'name'=> $campaign->name.'_copy',
        'assigned_campaign_id'=> $campaign->assigned_campaign_id,
        'type_of_campaign'=>$campaign->type_of_campaign,
        'assigned_number'=>$campaign->assigned_number,
        'current_date'=>date('Y-m-d'),
        'leadsrain_campaign_id'=>$campaign->leadsrain_campaign_id,
        'is_send'=>0,
        'activity_reminder'=>$campaign->activity_reminder,
//'date'=>$campaign->date,
//'date_type'=>$campaign->date_type,
        ];
        $campaign_data = $this->campaign->create_new($insert_data);

    }
    if($campaign_text->count()>0)
    {
        foreach ($campaign_text as $key => $value) {
            $insert_data = [
            'universal_campaign_id'=>$campaign_data->id,
            'type_acc' =>$value->type_acc,
            'text_name' => $value->text_name,
            'text'=> $value->text,
            'delay_day'=> $value->delay_day,
            'is_send'=>0,
            'repeat_every_year'=>$value->repeat_every_year,
            ];
            $campaigntext_data = $this->campaign_text->create_new($insert_data);
        }
    }
    if($campaign_email->count()>0)
    {
        foreach ($campaign_email as $key => $value) {
            $insert_data = [
            'universal_campaign_id'=>$campaign_data->id,
            'type_acc' =>$value->type_acc,
            'message_name' => $value->message_name,
            'text'=> $value->text,
            'subject'=> $value->subject,
            'delay_day'=> $value->delay_day,
            'is_send'=>0,
            'repeat_every_year'=>$value->repeat_every_year,
            'email_attachment' =>$value->email_attachment,
            ];
            $campaigntext_data = $this->campaign_email->create_new($insert_data);
        }
    }
    if($campaign_rvmd->count()>0)
    {
        foreach ($campaign_rvmd as $key => $value) {
            $insert_data = [
            'universal_campaign_id'=>$campaign_data->id,
            'type_acc' =>$value->type_acc,
            'src' => $value->src,
            'record_name'=> $value->record_name,
            'delay_day'=> $value->delay_day,
            'is_send'=>0,
            'repeat_every_year'=>$value->repeat_every_year,
            'leadsrain_campaign_id'=>$value->leadsrain_campaign_id,
            ];
            $campaigntext_data = $this->campaign_rvmd->create_new($insert_data);
        }
    }
    if($campaign_postcard->count()>0)
    {
        foreach ($campaign_postcard as $key => $value) {
            $insert_data = [
            'universal_campaign_id'=>$campaign_data->id,
            'type_acc' =>$value->type_acc,
            'front_src' => $value->front_src,
            'back_src'=> $value->back_src,
            'delay_day'=> $value->delay_day,
            'postcard_name'=> $value->postcard_name,
            'front_html'=> $value->front_html,
            'back_html'=> $value->back_html,
            'is_send'=>0,
            'repeat_every_year'=>$value->repeat_every_year,
            ];
            $campaigntext_data = $this->campaign_postcard->create_new($insert_data);
        }
    }
    return response()->json(['success'=>true],200);
}
public function admin_copy(){
    $campaign_id = $this->data['campaign_id'];
    $campaign = UniversalCampaign::where('id', '=', $campaign_id)->first();
    $campaign_text = UniversalCampaignText::where('universal_campaign_id', '=', $campaign_id)->orderBy('delay_day','ASC')->get();
    $campaign_email = UniversalCampaignEmail::where('universal_campaign_id', '=', $campaign_id)->orderBy('delay_day','ASC')->get();
    $campaign_rvmd = UniversalCampaignRvmd::where('universal_campaign_id', '=', $campaign_id)->orderBy('delay_day','ASC')->get();
    $campaign_postcard = UniversalCampaignPostcard::where('universal_campaign_id', '=', $campaign_id)->orderBy('delay_day','ASC')->get();
    if($campaign->count()>0)
    {
        $insert_data = [
        'users_id'=> $campaign->users_id,
        'admins_id' =>$campaign->admins_id,
        'type_acc' => $campaign->type_acc,
        'name'=> $campaign->name.'_copy',
        'assigned_campaign_id'=> $campaign->assigned_campaign_id,
        'type_of_campaign'=>$campaign->type_of_campaign,
        'assigned_number'=>$campaign->assigned_number,
        'current_date'=>date('Y-m-d'),
        'leadsrain_campaign_id'=>$campaign->leadsrain_campaign_id,
        'is_send'=>0,
        'activity_reminder'=>$campaign->activity_reminder,
        ];
        $campaign_data = $this->campaign->create_new($insert_data);
    }
    if($campaign_text->count()>0)
    {
        foreach ($campaign_text as $key => $value) {
            $insert_data = [
            'universal_campaign_id'=>$campaign_data->id,
            'type_acc' =>$value->type_acc,
            'text_name' => $value->text_name,
            'text'=> $value->text,
            'delay_day'=> $value->delay_day,
            'is_send'=>0,
            'repeat_every_year'=>$value->repeat_every_year,
            ];
            $campaigntext_data = $this->campaign_text->create_new($insert_data);
        }
    }
    if($campaign_email->count()>0)
    {
        foreach ($campaign_email as $key => $value) {
            $insert_data = [
            'universal_campaign_id'=>$campaign_data->id,
            'type_acc' =>$value->type_acc,
            'message_name' => $value->message_name,
            'text'=> $value->text,
            'subject'=> $value->subject,
            'delay_day'=> $value->delay_day,
            'is_send'=>0,
            'repeat_every_year'=>$value->repeat_every_year,
            'email_attachment' =>$value->email_attachment,
            ];
            $campaigntext_data = $this->campaign_email->create_new($insert_data);
        }
    }
    if($campaign_rvmd->count()>0)
    {
        foreach ($campaign_rvmd as $key => $value) {
            $insert_data = [
            'universal_campaign_id'=>$campaign_data->id,
            'type_acc' =>$value->type_acc,
            'src' => $value->src,
            'record_name'=> $value->record_name,
            'delay_day'=> $value->delay_day,
            'is_send'=>0,
            'repeat_every_year'=>$value->repeat_every_year,
            'leadsrain_campaign_id'=>$value->leadsrain_campaign_id,
            ];
            $campaigntext_data = $this->campaign_rvmd->create_new($insert_data);
        }
    }
    if($campaign_postcard->count()>0)
    {
        foreach ($campaign_postcard as $key => $value) {
            $insert_data = [
            'universal_campaign_id'=>$campaign_data->id,
            'type_acc' =>$value->type_acc,
            'front_src' => $value->front_src,
            'back_src'=> $value->back_src,
            'delay_day'=> $value->delay_day,
            'postcard_name'=> $value->postcard_name,
            'front_html'=> $value->front_html,
            'back_html'=> $value->back_html,
            'is_send'=>0,
            'repeat_every_year'=>$value->repeat_every_year,
            ];
            $campaigntext_data = $this->campaign_postcard->create_new($insert_data);
        }
    }
    return response()->json(['success'=>true],200);
}
}

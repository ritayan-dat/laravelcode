<?php

    namespace App\Http\Middleware\Campaign;

    use Closure;
    use Exception;
    use Illuminate\Support\Facades\DB;
    class CreateCampaign
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
            try{
                $data = $request->post();
//                $this->check_empty_data($data);
                $this->check_exists($data['campaign'],$data['name'],$data);
                return $next($request);
            }catch (Exception $e){
                return response(json_encode(['success' => false,"message"=>$e->getMessage()],200));
            }
        }

        private function check_exists($type,$name,$data){
            if(!empty($data['users_id'])){
                $where = [['users_id','=',$data['users_id']]];
            }elseif (!empty($data['admins_id'])){
                $where = [['admins_id','=',$data['admins_id']]];
            }
            switch ($type){
                case 'universal':
                    $data = DB::table('universal_campaign')
                        ->where($where)
                        ->where('name','=',$name)
                        ->exists();
                    if(!empty($data)){
                        throw new Exception('Campaign with current name is already exists!', 404);
                    }
                break;
                case 'holiday':
                    $data = DB::table('holiday_campaign')->where($where)->where('name','=',$name)->exists();
                    if(!empty($data)){
                        throw new Exception('Campaign with current name is already exists!', 404);
                    }
                break;
                case 'dated':
                    $data = DB::table('dated_campaign')->where($where)->where('name','=',$name)->exists();
                    if(!empty($data)){
                        throw new Exception('Campaign with current name is already exists!', 404);
                    }
                break;
            }
        }

        private function check_empty_data($data){
            $array_required = ['email'=>'Email','password'=>'Password'];
            foreach ($array_required as $item=>$full_name) {
                if(!array_key_exists($item,$data)){
                    throw new Exception($full_name." is empty!", 404);
                }
            }
        }
    }

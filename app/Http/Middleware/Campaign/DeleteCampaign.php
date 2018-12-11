<?php

    namespace App\Http\Middleware\Campaign;

    use Closure;
    use Exception;
    use Illuminate\Support\Facades\DB;
    class DeleteCampaign
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
                $this->check_exists($data['type'],$data['campaign_id']);
                return $next($request);
            }catch (Exception $e){
                return response(json_encode(['success' => false,"message"=>$e->getMessage()],200));
            }
        }

        private function check_exists($type,$id){
            switch ($type){
                case 'universal':
                    $data = DB::table('universal_campaign')->where('id','=',$id)->exists();
                    if(empty($data)){
                        throw new Exception('Campaign was not found!', 404);
                    }
                break;
                case 'holiday':
                    $data = DB::table('holiday_campaign')->where('id','=',$id)->exists();
                    if(empty($data)){
                        throw new Exception('Campaign was not found!', 404);
                    }
                break;
                case 'dated':
                    $data = DB::table('dated_campaign')->where('id','=',$id)->exists();
                    if(empty($data)){
                        throw new Exception('Campaign was not found!', 404);
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

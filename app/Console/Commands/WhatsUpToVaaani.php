<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsUpToVaaani extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:whatsuptovaani';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $log = Log::channel('whatsup_to_vaani');

        $all_user_details = DB::connection('dev_eosglobe')
				->table('user_details')
                ->select('id','mobile','monthly_ctc','joining_days','languages_known','preferred_time_to_talk')
				->where('ischatend', '2')
                ->where('is_processed', '0')->take(50)->get()->map(function($user) {
                    return (array) $user;  // Convert each stdClass object to an associative array
                })->toArray();
    
       

       $data = ["campaign_id"=>"EOS_HR","list_id"=>"9236747"];
       $ids_array = [];


       foreach($all_user_details as $user_data){
        $data['data'][] = $user_data;
        $ids_array[] = $user_data['id'];
       }

       $log->debug(__line__."User Details Fetched".json_encode($data));
       

       if(!empty($data['data'])){
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://vaani.edas.tech/vaani/admin/cronjobs/lead_insert_from_whatsup.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $log->debug(__line__."Response".$response);

            if($response == "success"){
                $log->debug(__line__."Ids for update".json_encode($ids_array));

                $update = DB::connection('dev_eosglobe')
                ->table('user_details')->whereIn('id',$ids_array)->update( [ 'is_processed' => '1']);
            }
       }
    }
}

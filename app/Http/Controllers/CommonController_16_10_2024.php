<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\LoginStatus;
use Carbon\Carbon;
use App\Http\Controllers\LoginController;
use App\Models\Users;
class CommonController extends Controller
{
    public function updateLoginStatus()
    {
		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '2048M');
		$log = Log::channel('cron_activity');
		$log->debug("\n\n--------------------Start Here (Auto login satus) ------------------------------------\n");
		
		// Calculate the datetime 50 seconds ago
		$profileArr = ['user'];
		$seconds = 3600;
		$thresholdDatetime = Carbon::now()->subSeconds($seconds);

		// Fetch records based on the condition
        $query = DB::table('session_log')
            ->select('user_id')
            ->where('login_status', 1)
			->orderBy('created_at', 'asc');
		$data = $query->get();	
		// echo "data<pre>"; print_r($data);
		if($data)
		{	
			$data = json_decode(json_encode($data), true);
			$data = array_unique(array_column($data, 'user_id'));
			$chunkData = array_chunk($data, 500);
						
			foreach($chunkData as $arr)
			{							
				$log->debug("\n Process for Data : ". json_encode($arr));
				
				$results = LoginStatus::where('updated_at', '<', $thresholdDatetime)
				->whereIn('profile', $profileArr)
				->whereIn('user_id', $arr)
				->select('user_id')->get();
				
				if($results)
				{
					$results = json_decode(json_encode($results), true);
					$results = array_unique(array_column($results, 'user_id'));
					$clientId = Users::whereIn('id', $results)->pluck('client_id');
					
					if(!empty($results)){
						 //echo "results1<pre>"; print_r($results);die;
						$status1 = DB::table('session_log')
							->whereIn('user_id', $results)
							->where('login_status', 1)
							->update([
								'login_status' => 2,
								'updated_at' => date("Y-m-d H:i:s")
							]);	
						// Instantiate LoginController
						$loginController = new LoginController();
						// Create a dummy request or retrieve the actual request object, depending on your situation
						$dummyRequest = new Request([
							'agentid' => $results,
							'agentrole' => 'user', // or any appropriate role
							'clientid' => $clientId
							// Add other required request parameters here
						]);

						// Call the signout function from LoginController
						$status2 = $loginController->signout($dummyRequest);	
						// $status2 = DB::table('users')
						// 	->whereIn('id', $results)
						// 	->update([
						// 		'login_status' => 5,
						// 		'updated_at' => date("Y-m-d H:i:s")
						// 	]);						
						$log->debug("\n\n update Status1 : ". $status1 ." && Status2 : ". $status2 ." For Data : ".json_encode($results));
					}
					
				}
				else{
					$log->debug("\n No data for delete ");		
				}	
			}
		}
		else{
			$log->debug("\n No login found");		
		}
		
		exit;
    }	
}

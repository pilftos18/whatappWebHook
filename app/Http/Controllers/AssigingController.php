<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
// use League\Flysystem\Util;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Models\Chats;
use App\Traits\CommonTraits;
use App\Traits\ApiTraits;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class AssigingController extends Controller
{
	use ApiTraits;
	use CommonTraits;
	
    public function assigning()
    {
		$url	= Config::get('custom.SendMSG.UAT_URL');
		$uniqueID = round(microtime(true) * 1000);
		// echo "\nStart : ". date("l jS \of F Y h:i:s A");
		$custom_log = Log::channel('cron_activity');
        $custom_log->debug("\n\n-------------------- Auto assign log change -------------");
		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '2048M');
		$carbonTime 	= Carbon::createFromTimestamp(time());
		$formattedTime 	= $carbonTime->format('Hi');
        $active 	= 1;
        $activeArr 	= [0,1];
        $inactive 	= 2;
		$wp_number 	= '';
        // select Non assinged chat // also need to check auto assigning is allowed or not in campaing
        $query = DB::table('chats')
            ->join('campaign', 'campaign.id', '=', 'chats.campaign_id')
            ->select('chats.id', 'chats.unique_id', 'chats.client_id','chats.campaign_id', 'chats.cust_unique_id', 'chats.customer_name', 'campaign.interaction_per_user', 'campaign.dist_method','campaign.call_window_from', 'campaign.call_window_to', 'campaign.queue', 'campaign.wp_number', 'campaign.wp_crud')
            ->whereIn('chats.status', $activeArr)
            ->whereIn('campaign.status', $activeArr)
            ->where('campaign.dist_type','auto')
            ->where('campaign.deleted_at',$active)
             ->where('chats.assigning_flag', 2)
             ->whereNull('chats.assigned_to')
             ->where('chats.is_closed', 1)
             ->where('campaign.call_window_from', '<', $formattedTime)
             ->where('campaign.call_window_to', '>', $formattedTime);
			// $sql = $query->toSql();
			// echo $sql;
		// $custom_log->debug("\n Step1 Chat Data : ". json_encode($query->toSql()));
		// $custom_log->debug("\n Step1 Chat Data1 : ". $formattedTime);
		// $custom_log->debug("\n Step1 Chat Data2 : ". json_encode($activeArr));
		// $custom_log->debug("\n Step1 Chat Data3 : ". json_encode($active));
		$chat_data = $query->get();
        $chat_data_array = json_decode(json_encode($chat_data), true);
		$custom_log->debug("\n Step1 Chat Data : ". json_encode($chat_data));
	//   echo "chat_data_array<pre>"; print_r($chat_data_array);die;
      
        $data = [];
        if(!empty($chat_data_array)){
            $clientArr = [];
            $custom_log->debug("\n Step2 ");
				foreach ($chat_data_array as $chat) {
					
					$allData[$chat['id']] = $chat;
					$chatData[$chat['campaign_id']][$chat['id']] = $chat['id'];
					$campaignData[$chat['campaign_id']]['interaction_per_user'] = $chat['interaction_per_user'];
					$campaignData[$chat['campaign_id']]['dist_method'] = $chat['dist_method'];
					$campaignData[$chat['campaign_id']]['call_window_from'] = $chat['call_window_from'];                
					$campaignData[$chat['campaign_id']]['call_window_to'] = $chat['call_window_to'];
					$campaignData[$chat['campaign_id']]['queue'] = (!empty($chat['queue']) ? explode(',', $chat['queue']) : '');
					
					$custom_log->debug("\n Step3");
				}  

        
        foreach($chatData as $campID => $campWiseData) {
			
			$custom_log->debug("\n Step4");
			// Get campaign mapped users who is logged into the system
				$userData = DB::table('queue_mapping')
				->select('queue_mapping.mapped_user', 'queue_mapping.user_id', 'users.name')
				->join('users', 'queue_mapping.user_id', '=', 'users.id')
				->whereIn('queue_mapping.status', $activeArr)
				->whereIn('queue_mapping.queue_id', $campaignData[$campID]['queue'])
				->where('users.is_deleted', $active)
				->whereIn('users.status', $activeArr)
				// ->whereNotIn('users.login_status', [0,1])
				->where('users.login_status', 2)
				->get();
				
				$mappedData = json_decode(json_encode($userData), true);
				//  echo "mappedData<pre>"; print_r($mappedData);
			 // die;

                $mappedUsersData = array_unique(array_column($mappedData, 'user_id'));
				$AgentArr = [];
				if(!empty($mappedData))
				{
					foreach($mappedData as $k => $val)
					{
						$AgentArr[$val['user_id']] = $val['name'];	 					
					}
				}

				//  echo "AgentArr<pre>"; print_r($AgentArr);
				$custom_log->debug("\n Step5");
            // Get already assigned chat count per user
                $chatCounts = DB::table('chats')
                ->select('assigned_to', DB::raw('count(assigned_to) as chat_count'))
                ->whereIn('status', $activeArr)
                ->whereIn('assigned_to', $mappedUsersData)
                ->where('campaign_id', $campID)
                ->where('is_closed', '!=' , 2)
                ->whereNotNull('assigned_to')
                ->groupBy('assigned_to')
                ->get();
				
			  
      
                // $usersCountArr = json_decode(json_encode($chatCounts), true);
				$usersCountArr = [];
				foreach ($chatCounts as $chatData) 
				{
					$usersCountArr[$chatData->assigned_to] = $chatData->chat_count;
				}
				
				$custom_log->debug("\n Step6 usersCountArr :". json_encode($usersCountArr));
				
				// if Assignment process is LINIER
				if($campaignData[$campID]['dist_method'] == '1')
				{

					$custom_log->debug("\n Step7");
					foreach($mappedUsersData as $key=>&$users) { 
						$custom_log->debug("\n Step8");
						$existingCnt = 0;				
						if(array_key_exists($users, $usersCountArr))
						{
							$existingCnt = $usersCountArr[$users];
						}	
						
						$assignCnt = $campaignData[$campID]['interaction_per_user'] - $existingCnt;	
						
						$custom_log->debug("\n existingCnt :". $existingCnt);
						$custom_log->debug("\n assignCnt :". $assignCnt);
						$custom_log->debug("\n interaction_per_user :". $campaignData[$campID]['interaction_per_user']);				
						$custom_log->debug("\n campaignData :". json_encode($campaignData[$campID]));	
						
					
								
						if($assignCnt >= 1)
						{
							$maxallocatedData = array_splice($campWiseData, 0, $assignCnt);		
							$result = DB::table('chats')
							->whereIn('status', $activeArr)
							->whereIn('id', $maxallocatedData)
							// ->take($assignCnt)
							->update([
								'assigned_to' => $users,
								'assigned_at' => date("Y-m-d H:i:s"),
								'assigned_by' => 0
							]);
							$custom_log->debug('campWiseData : ' . json_encode($campWiseData));
							$custom_log->debug('maxallocatedData : ' . json_encode($maxallocatedData));
							$custom_log->debug('assignCnt : ' . json_encode($assignCnt));
						// echo "otsiddif<pre>"; print_r($maxallocatedData);
							if($result && !in_array($campID, [45]))
							{

								
								$custom_log->debug("\n Step9");
								if(!empty($maxallocatedData))
								{
									
									//echo "insideIF<pre>"; print_r($maxallocatedData);
			  					// die;
									
									$custom_log->debug("\n Step10");
									
									foreach($maxallocatedData as $chatID => $data)
									{
										//Delete from prompt table once Assigned to 
										DB::table('prompt_msg')->where('chat_id', $chatID)->delete();
										
										$custom_log->debug("\n Step11");									
										$chatID = $data;
										$custom_log->debug('data : ' . json_encode($data));
										$custom_log->debug('chatID : ' . $chatID);
										$payload = array(
											'client_id' => $allData[$chatID]['client_id'],
											'campaign_id' => $allData[$chatID]['campaign_id'],
											'mobile' => $allData[$chatID]['cust_unique_id'],
											'agent_id' => $users,
											'chatID' => $chatID,
											'message_type' => 'text',
											'message' => 'Appreciate your patience. Now you can chat with an agent ('.$AgentArr[$users].')',
											'wp_number' => $allData[$chatID]['wp_number'],
											'mediaUrl' => '',											
											'event' => '',											
											'reply_id' => ''											
										);			
										
										$campData['wp_number'] 	= $allData[$chatID]['wp_number'];
										$campData['wp_crud'] 	= $allData[$chatID]['wp_crud'];
										// $res = ApiTraits::curlHit($url, $payload, 'POST');
										$res = ApiTraits::sendWPMsg($payload, false, 'cron_activity', $campData);
										$custom_log->debug('req : ' . json_encode($payload));
										$custom_log->debug('res : ' . json_encode($res));
										// $response 	= json_decode($res['response'], true);
										// $custom_log->debug('URL : ' . $this->url);
										// $log->debug('Payload : ' . json_encode($payload));
										// $log->debug('Gupshup API response : ' . json_encode($response));
										echo "Assinged Chat ID : ".$chatID. " To Agent : ".$users." (".$AgentArr[$users].")";
									}									
								}		
							}
						}						
					}
				}
				
				// if Assignment process is ROUND ROBIN
				if($campaignData[$campID]['dist_method'] == '2')
				{	 
					$custom_log->debug("\n Step12");
					$assignments 		= [];
					$entityCount 		= count($mappedUsersData);
					$lastAssignedIndex 	= 0;
					
					foreach ($campWiseData as $item) {
						$custom_log->debug("\n Step13");
						if($entityCount > 0){
							// Get the current entity
							$assignedEntity = $mappedUsersData[$lastAssignedIndex];
							// Check if the entity has reached the maximum count
							$maxCount = $campaignData[$campID]['interaction_per_user'];
							
							if (!isset($usersCountArr[$assignedEntity])) {
								$usersCountArr[$assignedEntity] = 0;
							}

							if ($usersCountArr[$assignedEntity] < $maxCount) {
								// Assign the item to the entity
								$assignments[$item] = $assignedEntity;

								// Increment the count for the assigned entity
								$usersCountArr[$assignedEntity]++;
							} else {
								// Find the next available entity within the limit
								$nextIndex = ($lastAssignedIndex + 1) % $entityCount;
								$attempts = 0;
								while ($usersCountArr[$mappedUsersData[$nextIndex]] >= $maxCount && $attempts < $entityCount) {
									$nextIndex = ($nextIndex + 1) % $entityCount;
									$attempts++;
								}

								// Assign the item to the next available entity
								$assignedEntity = $mappedUsersData[$nextIndex];
								$assignments[$item] = $assignedEntity;

								// Increment the count for the assigned entity
								$usersCountArr[$assignedEntity]++;
							}

							// Move to the next entity in a circular manner
							$lastAssignedIndex = ($lastAssignedIndex + 1) % $entityCount;
						}
					}
					//echo "<pre>".$entityCount; print_r($assignments);die;	
					if(!empty($assignments))
					{
						$custom_log->debug("\n Step14");
						foreach ($assignments as $item => $entity) {	
							$custom_log->debug("\n Step15");				
							$result = DB::table('chats')
							->whereIn('status', $activeArr)
							->where('id', $item)
							->update([
								'assigned_to' => $entity,
								'assigned_at' => date("Y-m-d H:i:s"),
								'assigned_by' => 0
							]);
							
							//Delete from prompt table once Assigned to 
							DB::table('prompt_msg')->where('chat_id', $item)->delete();
							
							if($result && !in_array($campID, [45]))
							{	
								$custom_log->debug("\n Step16");							
								// Send Msg to customer after assinging a agent
																
								$payload = array(
									'client_id' => $allData[$item]['client_id'],
									'campaign_id' => $allData[$item]['campaign_id'],
									'mobile' => $allData[$item]['cust_unique_id'],
									'agent_id' => $entity,
									'chatID' => $item,
									'message_type' => 'Text',
									'message' => 'Appreciate your patience. Now you can chat with an agent ('.$AgentArr[$entity].')',
									'wp_number' => $allData[$item]['wp_number'],
									'mediaUrl' => '',											
									'event' => '',											
									'reply_id' => ''								
								);
								$custom_log->debug("\n Payload:".json_encode($payload));
								
								$campData['wp_number'] 	= $allData[$item]['wp_number'];
								$campData['wp_crud'] 	= $allData[$item]['wp_crud'];
								$res = ApiTraits::sendWPMsg($payload, false, 'cron_activity', $campData);
								$custom_log->debug('res : ' . json_encode($res));								
									// echo " Payload:".json_encode($payload);								
								// $res = ApiTraits::curlHit($url, $payload, 'POST');
								
								echo "Assinged Chat ID : ".$item. " To Agent : ".$entity." (".$AgentArr[$entity].")";
							}
						}
					}					
				}				
           // die;
            }
        }
		$custom_log->debug("\n End Auto Assigning");
		echo "and End : ". date("l jS \of F Y h:i:s A");
		exit;
    }

	public function autoClosedChat()
    {
		$uniqueID = round(microtime(true) * 1000);
		echo "\nStart : ". date("l jS \of F Y h:i:s A");
		// echo "Start autoClosedChat at ". date("l jS \of F Y h:i:s A");
		$custom_log = Log::channel('cron_activity');
        $custom_log->debug("\n\n-------------------- autoClosedChat -------------");
		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '2048M');

		 $result = DB::table('chat_log AS cl')
			->leftJoin('chats AS c', 'c.id', '=', 'cl.chat_id')
			->whereIn('c.status', [0, 1])
			->whereIn('c.assigning_flag', [0, 1])
			->where('c.is_closed', 1)
			->whereNotIn('c.campaign_id', [37])
			->groupBy('cl.chat_id')
			->havingRaw('TIMESTAMPDIFF(MINUTE, MAX(cl.created_at), NOW()) > ?', [60])
			->orderByDesc('c.id')
			// ->pluck('cl.chat_id')
			->select('cl.chat_id','c.client_id', 'c.campaign_id', 'c.cust_unique_id','c.assigned_to','c.org_no')
			->get();

			// echo "closed<pre>"; print_r($result); die;
			
			$custom_log->debug("\nStep1 : ". print_r($result, true));
			
		if(!empty($result)){			
							
			foreach($result as $k => $val)
			{
				$msgStr = "Thank you, LiveChat session has been ended";
				if($val->campaign_id == 45)
				{
					$msgStr = "As we have not received any response from your end, your live chat session has been ended. Please send us *Hi* to re-initiate a session";
				}
				$payload = array(
					'client_id' => $val->client_id,
					'campaign_id' => $val->campaign_id,
					'mobile' => $val->cust_unique_id,
					'agent_id' => $val->assigned_to,
					'chatID' => $val->chat_id,
					'message_type' => 'Text',
					'message' => $msgStr,
					'wp_number' => $val->org_no,
					'mediaUrl' => '',											
					'event' => '',											
					'reply_id' => ''								
				);
				$custom_log->debug("\n Payload:".print_r($payload, true));
				
				$res = ApiTraits::sendWPMsg($payload, false, 'cron_activity', []);
				$custom_log->debug('Auto closed : ' . print_r($res, true));
				
				
				$query = DB::table('chats')
				->where('id', $val->chat_id)
				->update(['dispo' => 'Dispo_by_system', 'is_closed' => '2', 'closed_at' => now()]);


				$updateData = [
					'flow_level' => '1',
					'flow_sub_level' => 0,
				];
				$queryp = DB::connection('panasonic_connection')
				->table('anchor_smart_saver')
				->where('mobile', $val->cust_unique_id)
				->update($updateData);	
				
				$custom_log->debug("\nStep2 : ". print_r($query, true));

				$custom_log->debug("\nSetLevelipStep3 : ". print_r($queryp, true));
			}
		}
		$custom_log->debug("\n --------------------End autoClosedChat -------------");
		echo " and End : ". date("l jS \of F Y h:i:s A");
		exit;
    }
	
	public function autoClosedAssignedChat()
    {
		$uniqueID = round(microtime(true) * 1000);
		echo "\nStart : ". date("l jS \of F Y h:i:s A");
		// echo "Start autoClosedChat at ". date("l jS \of F Y h:i:s A");
		$custom_log = Log::channel('cron_activity');
        $custom_log->debug("\n\n-------------------- autoClosedAssignedChat -------------");
		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '2048M');
		
		//, 'c.assigned_at', 'c.org_no', 'c.cust_unique_id', 'c.customer_name', 'c.created_at', 'c.client_id', 'c.campaign_id'
		$today = now()->toDateString();
		$chatIds = DB::table('chat_log AS cl')
			->leftJoin('chats AS c', 'c.id', '=', 'cl.chat_id')
			->select('cl.chat_id')
			->whereIn('c.status', [0, 1])
			->whereIn('c.assigning_flag', [2])
			->whereIn('c.is_closed', [0, 1])
			->where('c.campaign_id', '!=', '37')
			->whereDate('c.assigned_at', '<', $today)
			->groupBy('cl.chat_id')
			->orderByDesc('c.id')
			->get();

		// If you need an array, you can use toArray()
		$result = $chatIds->toArray();
			$custom_log->debug("\nStep1 : ". json_encode($result));
			
		if(!empty($result)){			
							
			$query = DB::table('chats')
				->whereIn('id', $result)
				->update(['dispo' => 'Dispo_by_system', 'is_closed' => '2', 'closed_at' => now()]);
				$custom_log->debug("\nStep2 : ". json_encode($query));
		}
		$custom_log->debug("\n --------------------End autoClosedAssignedChat -------------");
		echo " and End : ". date("l jS \of F Y h:i:s A");
		exit;
    }

}

<?php
namespace App\Traits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\Chats;
use App\Models\Chat_log;
use App\Models\Api_log;

trait CommonTraits	
{	
	public function uuid($appendStr = '')
	{
		return $string = str_replace('-', '',Str::uuid()->toString()).$appendStr; 
	}
	
	public function isChatIDExists($orgNo, $custNo){
		
		$data = DB::table('chats')
			->select('id')
			->whereIn('status', [0,1])
			->where('org_no', $waNumber)
			->where('cust_unique_id', $mobile) 
			->whereIn('is_closed', [0,1])
			->get()
			->first();
		return $chatID = isset($data->id) ? $data->id : '';
	}
	
	public function getChatDetails($waNumber, $mobile)
	{
		$data = DB::table('chats')
			->select('*')
			->whereIn('status', [0,1])
			->where('org_no', $waNumber)
			->where('cust_unique_id', $mobile) 
			->whereIn('is_closed', [0,1])
			->get()
			->first();
		// Check if a record was found
		if ($data) {
			// Convert the result to an array
			$resultArray = (array)$data;

			// Return the array
			return $resultArray;
		} else {
			// Handle the case where no record was found
			return null;
		}
	}
	
	public function addChatIfNotExist($orgNo, $custNo, $client, $campaign, $name = '')
	{
		$chatID  = $this->isChatIDExists($orgNo, $custNo);
		if(empty($chatID))
		{
			$this->addNewChat($orgNo, $custNo, $client, $campaign, $name = '');
		}		
		return $chatID;		
	}
	
	public function addNewChat($orgNo, $custNo, $client, $campaign, $name = '')
	{
		$chat_data = new Chats();
		$chat_data->unique_id 		= $this->uuid();
		$chat_data->client_id 		= $client;
		$chat_data->campaign_id 	= $campaign;
		$chat_data->org_no 			= $orgNo;
		$chat_data->cust_unique_id 	= $custNo;
		$chat_data->customer_name 	= $name;
		$chat_data->assigning_flag 	= 1;
		// if($campaign =='44' && $client == '38')
		// {
		// 	$chat_data->assigning_flag 	= 2;
		// }
		$chat_data->is_closed 		= 1;
		$chat_data->status 			= 1;
		$chat_data->created_at 		= now();
		$chat_data->created_by 		= 0;
		$chat_data->save();
		
		return $chat_data->id;	
	}
	
	public function MessageType($messageType)
	{
		switch (strtolower($messageType)) {
			case "text":
				return 1;
				// Handle text message
				break;

			case "image":
				return 2;
				// Handle image message
				break;

			case "video":
				return 3;
				// Handle video message
				break;

			case "document":
				return 4;
				// Handle document message
				break;

			case "audio":
				return 5;
				// Handle audio message
				break;

			case "location":
				return 6;
				// Handle location message
				break;

			case "template":
				return 7;
				// Handle template message
				break;

			case "contact":
				return 8;
				// Handle contact message
				break;

			case "interactive":
				return 9;
				// Handle interactive message
				break;

			default:
				return 0;
				// Handle unknown message type or provide an error message
				break;
		}
	}
    
	public function endChat($chatID, $closed_by = 0)
	{		
		$result = Chats::where('id', $chatID)
		->where('is_closed', 1)
		->update(['is_closed' => 2, 'closed_by' => $closed_by, 'closed_at' => now()]);
		return ($result) ? $result : '';
	}
	
	public function logAPIRowData($orgNo, $custNo, $client, $campaign, $chat_log_id, $data, $table = 'chat_log', $type = 'WEBHOOK')
	{
		$api_log = new api_log();
		$api_log->client_id 	= $client;
		$api_log->campaign_id 	= $campaign;
		$api_log->ref_table 	= $table;
		$api_log->ref_id 		= $chat_log_id;
		$api_log->type 			= $type;
		$api_log->cust_no 		= $custNo;
		$api_log->org_no 		= $orgNo;
		$api_log->response_data = is_array($data) ? json_encode($data) : $data;
		$api_log->save();
		
		return isset($api_log->id) ? $api_log->id : '';
	}

	public function getCampaignDetails($campID)
	{
		$data = DB::table('campaign')
			->select('wp_number', 'wp_crud')
			->whereIn('status', [0,1])
			->where('id', $campID)
			->first();
		// Check if a record was found
		if ($data) {
			$resultArray = (array)$data;
			return $resultArray;
		} else {
			return null;
		}
	}
}
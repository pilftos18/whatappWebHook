<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\API_User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Traits\CommonTraits;
use App\Traits\ApiTraits;
use App\Models\SM_API_Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;

class CommonAPIController extends Controller
{
	use ApiTraits;
	use CommonTraits;


	public function dynamicUrl($url){
		// Explode the URL by '/'
		$urlParts = explode('/', $url);

		// Remove the last element
		array_pop($urlParts);

		// Reconstruct the URL
		$newUrl = implode('/', $urlParts);
		// echo $newUrl;

		return $newUrl;
	}


    public function sendImgMsg(Request $request){

        $log = Log::channel('img_api_log');
		$log->debug(__line__."---- Start sendImgMsg -----");
		$log->debug(__line__."Request Data".print_r($request->all(),true));

		// if($_SERVER['REQUEST_METHOD'] != "POST"){
		// 	$data = [
		// 		'request' => ['method'=> $_SERVER['REQUEST_METHOD'] ],
		// 		'response' => 'Only Post request are acceptable',
		// 	];
		// 	$this->insertAPILog($data);
        //     return response()->json(['error' => 'Only Post request are acceptable'], 400);
        //     $log->debug(__line__."Only Post request are acceptable");
        //     exit;
        // }

		//Exit if https is not on
        if(!(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')){
			$data = [
				'request' => ['url'=> $request->fullUrl()],
				'response' => 'Please make a secure connection via https only',
			];
			$this->insertAPILog($data);
            return response()->json(['error' => 'Please make a secure connection via https only'], 400);
            $log->debug(__line__."Please make a secure connection via https only");
            exit;
        }


		$validator = Validator::make($request->all(), [
			'username' => 'required',
			'password' => 'required',
			'image_file' => 'required|max:4096',
			'mobile' => 'required|digits:10|regex:/^[0-9]+$/',
			'wp_number' => 'required|digits:10|regex:/^[0-9]+$/',
			// 'caption' => 'required',
		], [
			'username.required' => 'Username is required.',
			'password.required' => 'Password is required.',
			'image_file.required' => 'Image is required.',
			'mobile.required' => 'Mobile is required and should be 10 digits',
			'wp_number.required' => 'wp_number is required and should be 10 digits.',
			// 'caption.required' => 'Caption is required.',
		]);

		if ($validator->fails()) {
			$data = [
				'request' => ['Request Data'=> json_encode($request->all()) ],
				'response' => json_encode($validator->errors()),
			];
			$this->insertAPILog($data);
			return response()->json(['errors' => $validator->errors()], 422);
		}

		//Get the count of users_details if exists in db or not
        $client_count = API_User::select('id')->where('username',$request['username'])->where('password',$request['password'])->count();

		//If username and password are not found in the db then show a error message
        if($client_count == 0){
			$data = [
				'request' => ['client_count'=>$client_count,'username'=>$request['username'],'password'=>$request['password']],
				'response' => 'Unauthorized Access',
			];
			$this->insertAPILog($data);
            return response()->json(['error' => 'Unauthorized Access'], 401);
            $log->debug(__line__."Unauthorized Access");
            exit;
        }  

		//If the client exists then fetch the details of the client
        $query = API_User::select('client','campaign_id','username','password')->where('username',$request['username'])->where('password',$request['password'])->first();
        $userDetails = $query->toArray();

        try{

            $timeStamp = now()->timestamp;
            $imageName = $timeStamp .'.'.$request->image_file->getClientOriginalExtension();
			$file = $request->file('image_file');

			// Get the size of the uploaded file in kilobytes
			$fileSizeKB = ($file->getSize())/1024;
			$fileSizeMB = $fileSizeKB / 1024 ;
			if ($fileSizeMB > 2) { // Check if file size is greater than 2MB
				$data = [
					'request' => ['image_file_size'=>$fileSizeMB.'MB','image_file_detail'=> json_encode($_FILES)],
					'response' => 'Images should be less than 4mb in size',
				];
				$this->insertAPILog($data);

				return response()->json(['error' => 'Images should be less than 4mb in size'], 400);
				$log->debug(__line__."Images should be less than 4mb in size");
				exit;
			}


			$extension = pathinfo($imageName, PATHINFO_EXTENSION);
			$extension_validate = ['png','jpeg','jpg'];
			
			if(!in_array(strtolower($extension),$extension_validate)){
				$data = [
					'request' => ['image_file_extension'=>$extension,'image_file_detail'=> json_encode($_FILES)],
					'response' => 'Unauthorized Access',
				];
				$this->insertAPILog($data);

				return response()->json(['error' => 'Only image files with extension(jpg,jpeg,png) are allowed'], 400);
				$log->debug(__line__."Only image files with extension(jpg,jpeg,png) are allowed");
				exit;
			}

            $storagePath 		= "api_files/received/";

            $destinationFilePath= "{$storagePath}/{$imageName}";

			//Make the directory with above mentioned storage path
            try {
                if (!Storage::disk('public')->exists($storagePath)) {
                    Storage::disk('public')->makeDirectory($storagePath);
                    $log->debug(__LINE__.'Dir Created : ' . $storagePath);
                }else{
                    $log->debug(__LINE__.'Dir exists :'. $storagePath);
                }
            } catch (\Exception $e) {
                $log->debug(__LINE__.'Error creating directory : ' . $e->getMessage());
            }

			//If file upload is successfull then send then call the api
            if(Storage::disk('public')->putFileAs($storagePath,$request->image_file,$imageName)){

                $reqData['client_id']	= $userDetails['client'];
                $reqData['campaign_id'] = $userDetails['campaign_id'];
                $reqData['mobile'] 		= $request['mobile'];
                $reqData['method'] 	= 'SendMediaMessage';
                $reqData['action'] 	= '';
                // $reqData['msg'] 	= $request['caption'] ? $request['caption'] : $imageName;
                $reqData['msg'] 	= 'Team Anchor by Panasonic';
                $reqData['msg_id'] 	= CommonTraits::uuid();
                $reqData['msg_type'] = 'IMAGE';
                // $reqData['media_url'] = "https://edas-webapi.edas.tech/vaaniSMDev/storage/app/public/api_files/received/$imageName";
                $reqData['media_url'] = $this->dynamicUrl($request->fullUrl())."/storage/app/public/api_files/received/$imageName";
                $reqData['filename'] = $imageName;
                $reqData['isHSM'] = 'false';
                // $reqData['caption'] = $request['caption'] ? $request['caption'] : '';
                $reqData['caption'] = 'Team Anchor by Panasonic';
                $reqData['data_encoding'] = 'Unicode_text';
                $reqData['wp_number'] = $request['wp_number'];
				$reqData['templateType']= 'image';	
				$reqData['interactive_type']= '';	

				$log->debug(__line__."Post Data".print_r($reqData,true));
			    $autRepMsg = $this->sendWPMsg($reqData, false,'img_api_log');
				$log->debug(__LINE__.'response autRepMsg : ' . print_r($autRepMsg, true));

				if($autRepMsg['msg'] == 'success'){
					$data = [
						'request' => json_encode($reqData),
						'response' => 'Request send successfully',
					];
					$this->insertAPILog($data);

					return response()->json([
					    'success'=>'Request send successfully'
					]);
				}

				if($autRepMsg['msg'] != 'failure'){
					$data = [
						'request' => json_encode($reqData),
						'response' => 'Message not send',
					];
					$this->insertAPILog($data);

					return response()->json([
					    'failure'=>'Message not send'
					],200);
				}
            }
            
        }catch(Exception $e){
            return response()->json(['message'=>"Something went wrong"],500);
        }
    }



    // to send msg using the internal function you can use this function
	public function sendWPMsg($postData, $isTemplate = false, $logSource = 'wp_webhooks'){
		$url = '';
		if(isset($logSource) && !empty($logSource))
		{
			$log = Log::channel($logSource);
		}
		else
		{
			$log = Log::channel('wp_send');
		}
		
		$log->debug("\n--------------------Start sendWPMsg ---------\n");

		
		$result 	= ['msg'=>'Initiated msg','status'=> 'failed', 'status_code'=>'101', 'data' => []];
		$timestamp 	= round(microtime(true) * 1000);
		$messageId 	= CommonTraits::uuid();
		$templateID = 0; $filename = ''; $data = []; $payload = []; $apiRes = []; $methodFlag 	= false; $validateFlag = true;
		$wp_number 	= isset($postData['wp_number']) ? $postData['wp_number'] : '';
		$clientID 	= $postData['client_id'];
		$campaignID = $postData['campaign_id'];
		$sendTo 	= $postData['mobile'];
		$msg_type 	= strtolower(isset($postData['msg_id']) ? $postData['msg_id'] : 'text');
		$event 		= isset($postData['event']) ? $postData['event'] : '';
		$message 	= isset($postData['msg']) ? $postData['msg'] : '';
		$msg_type 	= strtolower(isset($postData['msg_type']) ? $postData['msg_type'] : 'text');
		$mediaUrl 	= isset($postData['media_url']) ? $postData['media_url'] : '';
		$method 	= isset($postData['method']) ? $postData['method'] : '';
		$action 	= isset($postData['action']) ? $postData['action'] : '';
		$intType 	= isset($postData['interactive_type']) ? $postData['interactive_type'] : '';
		$footer 	= isset($postData['footer']) ? $postData['footer'] : '';
	


		if(!empty($mediaUrl)){
			$file 		= basename($mediaUrl);
			$filename 	= pathinfo($file, PATHINFO_FILENAME);
		}

		if($validateFlag === true)
		{
			if(empty($campData['wp_number']) || empty($campData['wp_crud']))
			{
				$campData 	= CommonTraits::getCampaignDetails($campaignID);
			}

			$log->debug(__line__.'Campaign Data : ' . print_r($campData, true));
			
			$wp_number	= isset($campData['wp_number']) ? $campData['wp_number'] : '';
			
			if(isset($campData['wp_crud']) && !empty($campData['wp_crud']))
			{					
				$crudArr	= json_decode($campData['wp_crud'], true);
				$twoway_userid 		= $crudArr['twoway_userid'];
				$twoway_password 	= $crudArr['twoway_password'];
				$hsm_userid 		= $crudArr['hsm_userid'];
				$hsm_password 		= $crudArr['hsm_password'];
				$auth_scheme 		= isset($crudArr['auth_scheme']) ? $crudArr['auth_scheme'] : 'plain';
				$version 			= isset($crudArr['version']) ? $crudArr['version'] : '1.1';
				$data_encoding 		= isset($crudArr['data_encoding']) ? $crudArr['data_encoding'] : 'Unicode_text';

				if(preg_match('/image|document|voice|audio|video|application/', $msg_type)) {
						$payload = array(
							"format" => "json",
							"userid" => $hsm_userid,
							"password" => $hsm_password,
							"auth_scheme" => $auth_scheme,
							"v" => $version,
							"send_to" => $sendTo
						);		

						if(!empty($mediaUrl))
						{								
							$payload = array_merge($payload, array(
								"method" => $method,
								"msg_type" => $msg_type,
								"caption" => $message,
								// "action" => $action,
								// "interactive_type" => $intType,
								// "footer" => $footer,
								"media_url" => $mediaUrl,
								"msg_id" => $messageId
							));
							$methodFlag = true;
						}
						else{
							
							$result = ['msg'=>'Missing Required parameters2','status'=>'failed', 'status_code'=>'400','data' =>['mediaUrl'=>$mediaUrl]];
							$log->debug(__line__.'Result : ' . print_r($result, true));

						}	
				}
				else
				{					
					$result = ['msg'=>'Missing Required paramenters3','status'=>'failed', 'status_code'=>'400','data' =>['tempType'=>$tempType, 'action'=>$action, 'intType'=>$intType]];
					$log->debug(__line__.'Result : ' . print_r($result, true));

				}
			


				if($methodFlag === true)
				{
					$url	= '';
					// $log->debug(__line__.'payload : ' . print_r($payload, true));
					$res 	= ApiTraits::curlHit($url, $payload, 'GET');
					
					$log->debug(__line__.'res : ' . print_r($res, true));
					if(!empty($res['response']))
					{
						$log->debug(__line__.'response : ' . print_r($res['response'], true));
						$apiRes = json_decode($res['response'], true);
						$log->debug(__line__.'error : ' . json_last_error());
						if(json_last_error() != JSON_ERROR_NONE){
							$apiRes = explode('|', $res['response']);
						}
					}
							
					
					if((isset($apiRes[0]) && $apiRes[0] == 'success') || isset($apiRes['response']['status']) && $apiRes['response']['status'] == 'success')
					{
						$result = ['msg'=>'success','status'=> 'success', 'status_code'=>'200', 'data' => $res['response']];
						$log->debug(__line__.'Result : ' . print_r($result, true));
					}
					else{
						
						$result = ['msg'=>$apiRes[2],'status'=> $apiRes[0], 'status_code'=>$apiRes[1], 'data' => $res['response']];
						$log->debug(__line__.'Result : ' . print_r($result, true));
					}
				
				}
				else{
					$result = ['msg'=>'Incomplete payload message or media missing','status'=> 'failed', 'status_code'=>'400', 'data' => $postData];
					$log->debug(__line__.'Result : ' . print_r($result, true));
				}
			}
			else{
				$result = ['msg'=>'API Details Missing','status'=> 'failed', 'status_code'=>'400', 'data' => $campData];
				$log->debug(__line__.'Result : ' . print_r($result, true));
			}
		}

		return $result;
	}


	//Insert into API Log table
	public function insertAPILog($data){
		$api_log = new SM_API_Log;
		$api_log->request = json_encode($data['request']);
		$api_log->response = $data['response'];
		$api_log->save();
	}

	
}

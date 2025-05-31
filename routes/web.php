<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\BreakLogController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DispositionController;
use App\Http\Controllers\BreakController;
use App\Http\Controllers\GupshupWebhookPanasonicController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\AssignUserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BulkFileUploadController;;
use App\Http\Controllers\GupshupWebhookController;
use App\Http\Controllers\GupshupGenericWebhookController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\AssigingController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\CommonAPIController;
use App\Http\Controllers\GupshupWebhookEosController;
use App\Http\Controllers\WebhookPanasonicSmartSaverController;
use App\Http\Controllers\WebhookPanasonicSmartSaverWbotController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/  

//first landing page route urls
  //Route::get('/', [LoginController::class, 'indexfun'])->name('login');
  
  Route::get('/', function () {
		return redirect('/login');
	});


  Route::get('/dashboard', [LoginController::class, 'indexfun']);
  Route::get('/login', [LoginController::class, 'indexfun'])->name('login');
  Route::post('/signin', [LoginController::class, 'signin']);
  // Route::get('/signout', [LoginController::class, 'signout']);
  Route::get('/signout', [LoginController::class, 'signout'])->name('signout');
  Route::get('/signoutuser/{userid}/{role}/{clientid}', [LoginController::class, 'signoutuser']);
  Route::get('/signoutuser_admin/{userid}/{role}/{clientid}', [LoginController::class, 'signoutuser_admin']);

 Route::middleware(['auth.user'])->group(function () {
        Route::post('userdashboard/list', [DashboardController::class, 'getUserDashboardList'])->name('userdashboard.list');
        Route::get('/chat', [ChatController::class, 'chats']);
        Route::get('/sidebarchat', [ChatController::class, 'sidebarchat']);
        Route::get('/fetch_chatdata', [ChatController::class, 'getchats']);
        Route::get('/fetch_chatdata_latest', [ChatController::class, 'getchats_latest']);
        Route::post('/store_msg', [ChatController::class, 'storechats']);
        Route::get('/msg_closed', [ChatController::class, 'msg_closedFun']);
        Route::get('/search_chats', [ChatController::class, 'search_chats']);
        Route::get('/get_sub_dispo', [ChatController::class, 'get_sub_dispo']);
        Route::get('/get_sub_sub_dispo', [ChatController::class, 'get_sub_sub_dispo']);
        Route::get('/store_break', [BreakLogController::class, 'store_break']);
        Route::get('/refresh_break_time', [DashboardController::class, 'refresh_break_time']);
        Route::get('/fetch_dashboard_data', [DashboardController::class, 'fetch_dashboard_data']);
        Route::get('/check_agent_is_login', [DashboardController::class, 'check_agent_is_login']);
        Route::get('/setCampaignSession', [DashboardController::class, 'setCampaignSession']);
        Route::get('/check_new_entries', [ChatController::class, 'check_new_entries']);
});	

       

  Route::middleware(['auth.superadmin'])->group(function () {
    
    //++++++++++= Routes that require authentication =+++++++++++//
    ///////////// Start routs for users module ////////////////////// 
    Route::get('/check_admin_is_login', [DashboardController::class, 'check_admin_is_login']);
        Route::get('/users', [UsersController::class, 'index'])->name('users.index');
        Route::post('users/list', [UsersController::class, 'getUserList'])->name('users.list');
        Route::post('users/manageruserlist', [UsersController::class, 'getUserManagerList'])->name('users.manageruserlist');
        Route::get('/users/create', [UsersController::class, 'create'])->name('users.create');
        Route::post('/users', [UsersController::class, 'store'])->name('users.store');
        Route::get('/users/{users}/edit', [UsersController::class, 'edit'])->name('users.edit');
        Route::put('/users/{users}', [UsersController::class, 'update'])->name('users.update');
        Route::post('/users/{users}/status', [UsersController::class, 'updateStatus'])->name('users.updateStatus');
        Route::delete('/users/{users}', [UsersController::class, 'destroy'])->name('users.destroy');
        Route::get('/users/client_list', [UsersController::class, 'getClientList'])->name('users.client_list');
        Route::get('/users/manager_list', [UsersController::class, 'getClientManagerList'])->name('users.manager_list');
        Route::get('/users/supervisor_list', [UsersController::class, 'getClientsupervisorList'])->name('users.supervisor_list');
        Route::post('api/data', [ModuleController::class, 'getApiData'])->name('api.data');
        Route::post('dashboard/list', [DashboardController::class, 'getDashboardList'])->name('dashboard.list');
   
        /////////////Start routes for client module  //////////////////////
        Route::get('/client', [ClientController::class, 'index'])->name('client.index');
        Route::post('/client', [ClientController::class, 'store'])->name('client.store');
        Route::post('list/client', [ClientController::class, 'getClientList'])->name('list.client');
        Route::get('/client/{client}/edit', [ClientController::class, 'edit'])->name('client.edit');
        Route::put('/client/{client}', [ClientController::class, 'update'])->name('client.update');
        Route::get('/client/create', [ClientController::class, 'create'])->name('client.create');
        Route::get('/client/delete/{id}', [ClientController::class, 'delete'])->name('client.delete');

        //////////////////////start route for queue////////////////////////////////////////
        Route::get('/queue', [QueueController::class, 'index'])->name('queue.index');
        Route::post('/queue', [QueueController::class, 'store'])->name('queue.store');
        Route::post('list/queue', [QueueController::class, 'getQueueList'])->name('list.queue');
        Route::get('/queue/{queue}/edit', [QueueController::class, 'edit'])->name('queue.edit');
        Route::put('/queue/{queue}', [QueueController::class, 'update'])->name('queue.update');
        Route::get('/queue/create', [QueueController::class, 'create'])->name('queue.create');
        Route::get('/queue/delete/{id}', [QueueController::class, 'delete'])->name('queue.delete');

        /////////////Start routes for campaign module  ////////////////////// 
        Route::get('/campaign', [CampaignController::class,'index'])->name('campaign.index');
        Route::post('/campaign', [CampaignController::class,'store'])->name('campaign.store');
        Route::post('list/campaign', [CampaignController::class, 'getCampaignList'])->name('list.campaign');
        Route::get('/campaign/{campaign}/edit', [CampaignController::class, 'edit'])->name('campaign.edit');
        Route::put('/campaign/{campaign}', [CampaignController::class, 'update'])->name('campaign.update');
        Route::get('/campaign/create', [CampaignController::class, 'create'])->name('campaign.create');
        Route::get('/campaign/delete/{id}', [CampaignController::class, 'delete'])->name('campaign.delete');

        /////////////Start routes for disposition module  ////////////////////// 
        Route::resource('disposition', DispositionController::class);
        Route::get('/disposition', [DispositionController::class,'index'])->name('disposition.index');
        Route::get('/disposition/create', [DispositionController::class, 'create'])->name('disposition.create');
        Route::get('/disposition/{disposition}/edit', [DispositionController::class, 'edit'])->name('disposition.edit');
        Route::get('/disposition/delete/{id}', [DispositionController::class, 'delete'])->name('disposition.delete');
        Route::post('list/disposititonlist', [DispositionController::class, 'getDispositionList'])->name('list.disposititonlist');

        /////////////Start routes for break module //////////////////////
        Route::get('/break', [BreakController::class,'index'])->name('break.index');
        Route::post('/break', [BreakController::class,'store'])->name('break.store');
        Route::post('list/break', [BreakController::class, 'getBreakList'])->name('list.break');
        Route::get('/break/{break}/edit', [BreakController::class, 'edit'])->name('break.edit');
        Route::put('/break/{break}', [BreakController::class, 'update'])->name('break.update');
        Route::get('/break/create', [BreakController::class, 'create'])->name('break.create');
        Route::get('/break/delete/{id}', [BreakController::class, 'delete'])->name('break.delete');

        /////////////Start routes for clientchange dropdown //////////////////////
        Route::get('list/fetchclients', [LoginController::class, 'getfetchClients'])->name('list.fetchclients');
        Route::get('data/changeclient', [LoginController::class, 'getchangeClient'])->name('data.changeclient');

        /////////////Start routes for assigningusers /////////////////////
        Route::get('/userassign', function () {
          return view('userassign.userassign');
        });
        Route::get('assign/campaign_list', [AssignUserController::class, 'getCampaignList'])->name('assign.campaign_list');
        Route::get('list/userlistassign', [AssignUserController::class, 'getUserAssignList'])->name('list.userlistassign');
        Route::get('assign/user_list', [AssignUserController::class, 'getUserList'])->name('assign.user_list');
        Route::get('assign/assigned_users', [AssignUserController::class, 'setAssignUser'])->name('assign.assigned_users');


      ///////////////////////start routes for report (login,whatsapp crm history,chat details)/////////////////////////////
      Route::get('/summaryreport', function () {
        return view('report.summaryreport');
      });

      Route::get('/agentactivityreport', function () {
        return view('report.agentactivityreport');
      });

      Route::get('/loginreport', function () {
        return view('report.loginreport');
      });

      Route::post('csv/loginreport', [ReportController::class, 'getLoginReport'])->name('csv.loginreport');
      
      Route::get('/crmreport', function () {
        return view('report.sm_crmreport');
      });
      Route::get('data/userloginlist', [ReportController::class, 'getUserLoginList'])->name('data.userloginlist');
      Route::post('csv/summaryreport', [ReportController::class, 'getSummaryReport'])->name('csv.summaryreport');
      Route::post('csv.agentactreport', [ReportController::class, 'getAgentActivityReport'])->name('csv.agentactreport');
      Route::post('csv/crmreport', [ReportController::class, 'getWhatsappCrmReport'])->name('csv.crmreport');
      Route::get('list/chatslist', [ReportController::class, 'getChatsList'])->name('list.chatslist');
      Route::get('/chats/{id}/show', [ChatController::class, 'show'])->name('chat.show');
      Route::get('/allchats/{id}/showAll', [ChatController::class, 'showAll'])->name('allchats.showAll');
      

      Route::post('list/superadmindashboard', [DashboardController::class, 'getSuperadminDashboardList'])->name('list.superadmindashboard');

      Route::get('list/getstats', [LoginController::class, 'getstats'])->name('list.getstats');

      Route::get('csv/chatdetailscsv', [ChatController::class, 'getChatDetails'])->name('csv.chatdetailscsv');
      
      // Route::get('/module/delete/{id}', [ModuleController::class, 'delete'])->name('module.delete');
      // Route::get('/company/delete/{id}', [CompanyController::class, 'delete'])->name('company.delete');
      // Route::post('module/list', [ModuleController::class, 'getModuleList'])->name('module.list');
      // Route::post('company/list', [CompanyController::class, 'getCompanyList'])->name('company.list');

    // Add more routes here
 });


// Forgot Password Routes
Route::get('password/reset_req/{id}', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.req');
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
// Route::post('password/reset_msg', [ResetPasswordController::class, 'msg'])->name('password.msg');


//------------------------------Webhook -----------------------------------//
// Route::post('/send', [GupshupWebhookController::class, 'sendNewMessage'])->name('send');
// Route::post('/incoming', [GupshupWebhookController::class, 'handleWebhook'])->middleware('web');
// Route::get('/dlr', [GupshupWebhookController::class, 'updateDLR'])->middleware('web');
Route::get('/bulk_templating', [GupshupWebhookController::class, 'getBulkData'])->middleware('web');
Route::get('/auto_assigning', [AssigingController::class, 'assigning'])->middleware('web');
Route::get('/autoClosedChat', [AssigingController::class, 'autoClosedChat'])->middleware('web');
Route::get('/update_login_status', [CommonController::class, 'updateLoginStatus'])->middleware('web');

Route::post('/send', [GupshupWebhookPanasonicController::class, 'sendNewMessage'])->name('send');
Route::post('/incoming', [GupshupWebhookPanasonicController::class, 'handleWebhook'])->middleware('web');
Route::get('/dlr', [GupshupWebhookPanasonicController::class, 'updateDLR'])->middleware('web');
Route::post('/incoming_eos', [GupshupWebhookEosController::class, 'handleEosWebhook'])->middleware('web');

//generic webhook to receive WA msg
Route::post('/incoming_generic', [GupshupGenericWebhookController::class, 'handleWebhook'])->middleware('web');

Route::get('/markExpire', [GupshupWebhookPanasonicController::class, 'markExpire'])->middleware('web');
Route::get('/promptMsg', [GupshupWebhookPanasonicController::class, 'promptMsg'])->middleware('web');
Route::get('/promptEosMsg', [GupshupWebhookPanasonicController::class, 'prompteosmsg'])->middleware('web');

Route::post('/wpssincoming', [WebhookPanasonicSmartSaverController::class, 'handleWebhook'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/incoming_wpbf', [WebhookPanasonicSmartSaverWbotController::class, 'handleWebhook'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/testdata', [WebhookPanasonicSmartSaverWbotController::class, 'test'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::middleware(['auth.admin'])->group(function () {

  Route::get('/filelist', [BulkFileUploadController::class, 'index'])->name('uploadfile.index');
  Route::post('postData/FileBulk', [BulkFileUploadController::class, 'store'])->name('postData.FileBulk');

  Route::post('list/bulkuploadfile', [BulkFileUploadController::class, 'BulkUploadList'])->name('list.bulkuploadfile');

  Route::post('list/admindashboard', [DashboardController::class, 'getadminDashboardList'])->name('list.admindashboard');

  Route::get('list/getstats', [LoginController::class, 'getstats'])->name('list.getstats');


  Route::get('/loginreport', function () {
    return view('report.loginreport');
  });

  Route::post('csv/loginreport', [ReportController::class, 'getLoginReport'])->name('csv.loginreport');

  Route::get('/summaryreport', function () {
    return view('report.summaryreport');
  });

  Route::get('/agentactivityreport', function () {
    return view('report.agentactivityreport');
  });
  
  Route::get('/crmreport', function () {
    return view('report.sm_crmreport');
  });
  Route::get('data/userloginlist', [ReportController::class, 'getUserLoginList'])->name('data.userloginlist');
  Route::post('csv/summaryreport', [ReportController::class, 'getSummaryReport'])->name('csv.summaryreport');
  Route::post('csv.agentactreport', [ReportController::class, 'getAgentActivityReport'])->name('csv.agentactreport');
  Route::post('csv/crmreport', [ReportController::class, 'getWhatsappCrmReport'])->name('csv.crmreport');
  Route::get('list/chatslist', [ReportController::class, 'getChatsList'])->name('list.chatslist');
  Route::get('/chats/{id}/show', [ChatController::class, 'show'])->name('chat.show');
  Route::get('/allchats/{id}/showAll', [ChatController::class, 'showAll'])->name('allchats.showAll');

});

Route::middleware(['auth.manager'])->group(function () {
  
  Route::post('list.managerdashboard', [DashboardController::class, 'getManagerDashboardList'])->name('list.managerdashboard');

});

// Route::middleware(['auth.mis'])->group(function () {

// });

Route::post('/sendImgRequest',[CommonAPIController::class,'sendImgMsg'])->middleware('web');
// Route::get('/sendImgRequest',[CommonAPIController::class,'sendImgMsg'])->middleware('web');
Route::post('/sendTemplateMsg',[CommonAPIController::class,'sendTemplateMsg'])->middleware('web');
<?php
namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Clients;
use App\Models\LicenseDetails;
use Illuminate\Support\Facades\Rouste;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App;
use App\Rules\PasswordPolicy;
class UsersController extends Controller
{

    public function index()
    {   
        
        $users = Users::whereIn('status', [0,1,2])->latest()->get(); //compact('users')
        return view('users.index', $users);
    }

    public function getUserList(Request $request)
    {   
        $sessionData = session('data');
        //print_r($sessionData);exit;
        $client_id = $sessionData['Client_id'];
        if ($request->ajax()) {
           // $data = Users::whereIn('status', [0,1,2])->latest()->get();
            $data = DB::table('users')
            ->leftJoin('clients', 'users.client_id', '=', 'clients.id')
            ->select('users.*','clients.name as client')
            ->whereIn('users.status', [0,1,2])
            ->whereIn('users.is_deleted',[0,1])
            ->whereIn('clients.status', [0,1])
            ->whereIn('clients.id',[$client_id])
            ->where('clients.deleted_at', 1)
            ->where('users.role', '!=','super_admin')
            ->latest()
            ->get();

            // $sql = $data->toSql();
            // echo $sql;die;
            //echo "<pre>"; print_r($data);die;
            return DataTables::of($data)
                ->addColumn('action', function($row){
                    $editUrl = route('users.edit', $row->id);
                    $btn = '<a href="'.$editUrl.'" class=""><i class="bi bi-pencil-square"></i></a>';
                    $btn = '<a class="text-danger" key-value = "'.$row->id.'"><i class="bi bi-trash3-fill"></i></a>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return abort(404);
    }

    public function getUserManagerList(Request $request)
    {   
        $sessionData = session('data');
        //print_r($sessionData);exit;
        $client_id = $sessionData['Client_id'];
        if ($request->ajax()) {
           // $data = Users::whereIn('status', [0,1,2])->latest()->get();
            $data = DB::table('users')
            ->leftJoin('clients', 'users.client_id', '=', 'clients.id')
            ->select('users.*','clients.name as client')
            ->whereIn('users.status', [0,1])
            ->whereIn('clients.status', [0,1])
            ->whereIn('clients.id',[$client_id])
            ->where('clients.deleted_at', 1)
            ->whereNotIn('users.role', ['super_admin', 'manager','admin'])
            ->latest()
            ->get();

            // $sql = $data->toSql();
            // echo $sql;die;
            //echo "<pre>"; print_r($data);die;
            return DataTables::of($data)
                ->addColumn('action', function($row){
                    $editUrl = route('users.edit', $row->id);
                    $btn = '<a href="'.$editUrl.'" class=""><i class="bi bi-pencil-square"></i></a>';
                    $btn = '<a class="text-danger" key-value = "'.$row->id.'"><i class="bi bi-trash3-fill"></i></a>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return abort(404);
    }

    public function create()
    {       
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $usernamelist = Users::where('client_id', $client_id)
        ->whereIn('status', [0,1,2])
        ->pluck('username');

        $userlist = Users::where('client_id', $client_id)
        ->whereIn('status', [0,1,2])
        ->pluck('name');

        $emailList = Users::whereIn('status', [0,1,2])
        ->pluck('email');
        //$passwrod = generateRandomPassword(); // Call the helper function
        //echo "<pre>"; print_r($passwrod);die;
        return view('users.create',compact('usernamelist','emailList','userlist'));
    }

    public function store(Request $request)
    {   
        $sessionData = session('data');
        // print_r($sessionData['Client_id']);exit;
        $clientid = $sessionData['Client_id'];
        $role = $request->input('role');
        $ip = $request->ip();
        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n------Start The User creation for ------------".$role);
        $licensecount = LicenseDetails::where('client_id', $clientid)->where('name',$role)->pluck('lic_count');
        // echo $role;//print_r($licensecount);exit;
        
        $license = intval($licensecount[0]);
        //print_r($license);//exit;

        $custom_log->debug(__LINE__."\n\n\n------license count for role------------".$license);
        $roleexistcount = Users::where('client_id', $clientid)->where('role',$role)->where('is_deleted','1')->count();
        $roleexist = intval($roleexistcount);
        //print_r("--".$roleexistcount);exit;
        $custom_log->debug(__LINE__."\n\n\n------license exist for role------------".$roleexist);
        if($license == $roleexist){
            //echo "<pre>"; echo "i am here"; exit;
            $custom_log->debug(__LINE__."\n\n\n------license count matches------------");
            $custom_log->debug(__LINE__."\n\n\n------redirected to create page------------");
            return redirect()->route('users.create')
                ->with('error', 'Please check License Count for Role.');
        }
        else{
            $custom_log->debug(__LINE__."\n\n\n------license granted------------");
            //exit;
        //dd($request->all());
            $request->validate([
                'client_id' => 'required',
                'name' => 'required|unique:users',
                'role' => 'required',
                'email' => 'required|email|unique:users',
                'mobile' => 'required',
                'username' => 'required|unique:users',
                'password' => ['required', 'string', new PasswordPolicy],
                'status' => 'required',
            ]); 
            $custom_log->debug(__LINE__."\n\n\n---- validations are proper --------");

            $users = new Users();
            $users->client_id = $request->input('client_id');
            $users->name =$request->input('name');
            $users->role =$request->input('role');
            $users->email =$request->input('email');
            $users->mobile = $request->input('mobile');
            $users->username =$request->input('username');
            $users->password =Hash::make($request->input('password'));
            $users->status = $request->input('status');
            $users->is_deleted = 1;
            $users->manager_id = $request->input('manager') ?? 0;
            $users->supervisor_id = $request->input('supervisor') ?? 0;
            $custom_log->debug(__LINE__."\n\n\n----requested data are proper-----");
            $users->save();

            $custom_log->debug(__LINE__."\n\n\n----user data inserted by -----".$ip);

            return redirect()->route('users.index')
                ->with('success', 'User created successfully.');
        }       
    }

    public function show(Users $users)
    {
        return view('users.show', compact('users'));
    }

    public function edit($id)
    {   
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];

        $clients = Clients::where('id', $client_id)->pluck('name');

        $users = Users::findOrFail($id);
        if(session('data.userRole') === 'super_admin'){
            $role = array('admin'=>'Admin','manager'=>'Manager','supervisor' =>'Supervisor','mis' => 'Mis','user'=>'User');
        }else{
            $role = array('supervisor' =>'Supervisor','mis' => 'Mis','user'=>'User');
        }
        $manager = Users::where('client_id', $client_id)
        ->where('role', 'manager')
        ->whereIn('status', [0, 1])
        ->pluck('name', 'id');
        $supervisor = Users::where('client_id', $client_id)
        ->where('role', 'supervisor')
        ->whereIn('status', [0, 1])
        ->pluck('name', 'id');

        $userlist = Users::where('client_id', $client_id)
        ->whereIn('status', [0,1,2])
        ->pluck('name');

        $emailList = Users::whereIn('status', [0,1,2])
        ->pluck('email');
        //echo "<pre>"; print_r($users->status);die;
        // compact('users', 'clients')
        return view('users.edit', compact('users', 'clients','role','manager','supervisor','userlist','emailList'));
    }

    public function update(Request $request, $id)
    {   
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        $ip = $request->ip();
        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n------User update for--".$id);
        $users = Users::findOrFail($id);
        //echo "<pre>"; print_r($client_id);die;
        $request->validate([
            'client_id' => 'required',
            'name' => [
                'required',
                Rule::unique('users')->ignore($id),
            ],
            'email' => 'required|email',
            'role' => 'required',
            'mobile' => 'required',
            'status' => 'required',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($id),
            ],
            'username' => [
                'required',
                'min:3',
                'max:255',
                Rule::unique('users')->ignore($id),
            ]
        ]);
        $custom_log->debug(__LINE__."\n\n\n---- validations are proper --------");

        $users->client_id = $client_id;
        $users->name = $request->input('name');
        $users->role = $request->input('role');
        $users->email = $request->input('email');
        $users->mobile = $request->input('mobile');
        $users->username = $request->input('username');
        $users->status = $request->input('status');
        $users->is_deleted = 1;
        $users->manager_id = $request->input('manager');
        $users->supervisor_id = $request->input('supervisor');
        $custom_log->debug(__LINE__."\n\n\n----requested data are proper-----");
        $users->save();
        $custom_log->debug(__LINE__."\n\n\n----user data updated by -----".$ip);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, Users $users)
    {
       //echo "<pre>"; print_r($request);die;
       $status = $users->delete();
      // print_r($status);
        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function updateStatus(Request $request,$id)
    {
        $user = Users::findOrFail($id);
        $ip = $request->ip();
        DB::enableQueryLog();
        $custom_log = Log::channel('adminactivity');
        $custom_log->debug("\n\n\n----\-----------------------/-----");
        $custom_log->debug(__LINE__."\n\n\n----delete user -----");
        $user->status = request('status');
        $user->is_deleted = 2;
        $custom_log->debug(__LINE__."\n\n\n-----set user status 2 by-----".$ip);
        $user->save();
        $custom_log->debug(__LINE__."\n\n\n-----delete user by-----".$ip);
        return response()->json(['success' => true]);
    }

    public function getClientList()
    {   
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        //echo "Heysfgsdfgadfgadfgafga";die;
        $clients = Clients::where('id', $client_id)->whereIn('status', [0,1])->pluck('name', 'id'); // Assuming 'name' is the field for the client name and 'id' is the field for the client ID
        return response()->json($clients);
    }

    public function getClientManagerList()
    {   
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        //echo "Heysfgsdfgadfgadfgafga";die;
        $manager = Users::where('client_id', $client_id)
        ->where('role', 'manager')
        ->whereIn('status', [0, 1])
        ->pluck('name', 'id'); // Assuming 'name' is the field for the client name and 'id' is the field for the client ID
        return response()->json($manager);
    }

    public function getClientSupervisorList()
    {   
        $sessionData = session('data');
        $client_id = $sessionData['Client_id'];
        //echo "Heysfgsdfgadfgadfgafga";die;
        $supervisor = Users::where('client_id', $client_id)
        ->where('role', 'supervisor')
        ->whereIn('status', [0, 1])
        ->pluck('name', 'id'); // Assuming 'name' is the field for the client name and 'id' is the field for the client ID
        return response()->json($supervisor);
    }
}

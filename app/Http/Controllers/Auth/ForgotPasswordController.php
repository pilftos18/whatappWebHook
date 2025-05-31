<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    /**
     * Display the password reset email form.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showLinkRequestForm($id = '')
    {
        if(!empty($id)){
            $userData =  DB::table('users')
            ->select('id', 'email')
            ->whereIn('status', [0,1])
            ->where('id', $id)
            ->latest()
            ->get()
            ->toArray();

            return view('auth.passwords.email', array('email' => $userData[0]->email));
        }
        else{
            return view('auth.passwords.email', array('email' =>''));
        }
        
    }

    // public function showLinkRequestForm()
    // {
    //     return view('auth.passwords.email');
        
    // }

    /**
     * Send a password reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        // $users = Users::whereIn('status', [0,1,2])->get();
       

        // $sessionData = session('data');
        //echo "<pre>aadsfasd"; print_r($sessionData);die;
        $request->validate(['email' => 'required|email']);

        $response = $this->broker()->sendResetLink(
            $request->only('email')
        );

        return $response == Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($request, $response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }
}

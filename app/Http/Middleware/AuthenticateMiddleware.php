<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Session_Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class AuthenticateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */

    public function handle($request, Closure $next)
    { 
       // echo "session : <pre>"; print_r(session('data'));die; 
        if ($request->session()->exists('data') && session('data.userRole') == 'user') {
            $sessionId = session('data.userSessionID');
            $data = Session_Log::where('session_id', $sessionId)->where('login_status', 1)->first();
            if($data)
            {
                return $next($request);
            }
            else{
                Session::flush();
                return redirect('/login');
            }
        }
        else{
            // return redirect()->route('login');
            return redirect('/login');
        }
    }
}

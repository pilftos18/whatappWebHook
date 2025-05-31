<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Session_Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;


class AdminAccess extends BaseVerifier
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
        //echo "<pre>"; print_r(session('data'));die;
        if ($request->session()->exists('data') && (session('data.userRole') == 'admin' || session('data.userRole') == 'super_admin' || session('data.userRole') == 'manager' || session('data.userRole') == 'mis' || session('data.userRole') == 'supervisor')) {
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
            return redirect('/login');
        }
    }

    // protected function tokensMatch($request)
    // {
    //     $sessionToken = $request->session()->token();

    //     $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

    //     if (!$token && $header = $request->header('X-XSRF-TOKEN')) {
    //         $token = $this->encrypter->decrypt($header);
    //     }

    //     if (!hash_equals($sessionToken, $token)) {
    //         return false;
    //     }

    //     return true;
    // }

    // public function handle($request, Closure $next)
    // {
    //     if ($this->isReading($request) || $this->tokensMatch($request)) {
    //         return $this->addCookieToResponse($request, $next($request));
    //     }

    //     return redirect()->route('login'); // Redirect to the login page
    // }
}

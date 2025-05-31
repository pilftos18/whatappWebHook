@if(session()->has('email'))
  return redirect('/');
@else

@extends('layout')

@section('content')



      <div class="login-container">
        @if(Session::get('success'))
            <?php $message = Session::get('success') ?>
            <?php echo '<script>swal.fire({text:"'. $message .'",icon:"success",timer:3000,showConfirmButton:false});</script>' ?>
        @endif
        
        @if(Session::get('error'))
            <?php $message = Session::get('error') ?>
            <?php echo '<script>swal.fire({text:"'. $message .'",icon:"error",timer:3000,showConfirmButton:false});</script>' ?>
        @endif
      </div>

      <div id="wrap">
        <div class="bg-loop gap-2" data-cnt="">
          <div class="section gap-2 container-fluid">

            <div class="item col-md-2" data-pos="1">
              <img src="https://picsum.photos/id/1/300" alt="">
              <img src="https://picsum.photos/id/2/300" alt="">
              <img src="https://picsum.photos/id/3/300" alt="">
              <img src="https://picsum.photos/id/4/300" alt="">
            </div>
            <div class="item col-md-3" data-pos="2">
              <img src="https://picsum.photos/id/1/300" alt="">
            </div>
            <div class="item col-md-3" data-pos="3">
              <img src="https://picsum.photos/id/1/300" alt="">
            </div>
            <div class="item col-md-3" data-pos="4">
              <img src="https://picsum.photos/id/1/300" alt="">
              <img src="https://picsum.photos/id/2/300" alt="">
              <img src="https://picsum.photos/id/3/300" alt="">
            </div>
            <div class="item col-md-3" data-pos="5">
              <img src="https://picsum.photos/id/1/300" alt="">
            </div>
            <div class="item col-md-3" data-pos="6">
              <img src="https://picsum.photos/id/1/300" alt="">
              <img src="https://picsum.photos/id/2/300" alt="">
              <img src="https://picsum.photos/id/3/300" alt="">
            </div>
            <div class="item col-md-3" data-pos="7">
              <img src="https://picsum.photos/id/1/300" alt="">
            </div>
        
            <div class="item col-md-3" data-pos="8">
              <img src="https://picsum.photos/id/1/300" alt="">
              <img src="https://picsum.photos/id/2/300" alt="">
              <img src="https://picsum.photos/id/3/300" alt="">
            </div>
            </div>

            

          </div>

      </div>

      <div class="wrapper">
        <div id="particles-js"></div>
        <div class="content-area">   
          <!-- <form>
            <div class="mb-3">
              <center><img width="160" src="images/vaani-logo.png" alt="logo"/></center>
            </div>
            <div class="mb-3">
              <input type="email" class="form-control text-center login-field" placeholder="Agent ID">
            </div>
            <div class="mb-3">
              <input type="password" class="form-control text-center login-field" id="password" placeholder="**********">
            </div>

            <button type="submit" class="btn btn-warning w-100 rounded-pill p-2 fs-6 text-uppercase fw-bolder">Submit</button>
          </form> -->

          <div class="card-body">
                  <div class="d-flex justify-content-center">
                  <a href="#"  class="logo d-flex align-items-center w-auto">
                    <img src="assets/img/logo-sm-2.png" alt="">
                  </a>
                </div>
                  

                  <form class="form loginform g-3 row mt-3" method="POST" action="{{asset('signin')}}">
                  @csrf
                    <div class="col-12">
                      <div class="input-group has-validation">
                       
                        <input type="text" name="username" class="form-control login-field" required="required" placeholder="Username">
                        <div class="invalid-feedback">Please enter your username.</div>
                      </div>
                    </div>

                    <div class="col-12">
                      <div class="input-group has-validation">
                  
                          <input type="password" name="passwd" id="password" class="form-control login-field" required="required" placeholder="Password">

                          <div class="input-group-append">
                            <button class="btn" type="button" id="password-toggle">
                                <i class="fa fa-eye"></i>
                            </button>
                          </div>
                          <div class="invalid-feedback">Please enter your password!</div>
                      </div>
                    </div>
					
                    <div class="col-12 text-center">
                      <button class="loginField btn btn-warning w-100 login-btn-set text-uppercase fw-bolder mb-0" type="submit">Login</button>
                    </div>
                  </form>

                </div>
              </div>


        </div>
      </div>

      <!-- <section class="section register min-vh-100 d-flex flex-column">
        <div class="container-fluid ">
          <div class="row mx-0">
            <div class="col-lg-3 col-md-3 d-flex flex-column align-items-center px-0">
              <div class="card mb-0">
                <div class="card-body">
                  <div class="d-flex justify-content-center">
                  <a href="https://edas.tech/" target="_blank" class="logo d-flex align-items-center w-auto">
                    <img src="assets/img/edas-logo-mn.png" alt="">
                  </a>
                </div>
                  <div class="pt-4 pb-2">
                    <h5 class="card-title text-center pb-0 fs-4">Login to Your Account</h5>
                    <p class="text-center small">Enter your credentials to login</p>
                  </div>

                  <form class="form loginform g-3 row" method="POST" action="{{asset('signin')}}">
                  @csrf
                    <div class="col-12">
                      <div class="input-group has-validation">
                        <span class="input-group-text" id="inputGroupPrepend"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" required="required" placeholder="Username">
                        <div class="invalid-feedback">Please enter your username.</div>
                      </div>
                    </div>

                    <div class="col-12">
                      <div class="input-group has-validation">
                        <span class="input-group-text" id="inputGroupPrepend"><i class="bi bi-lock"></i></span>
                        <input type="password" name="passwd" id="password" class="form-control" required="required" placeholder="Password">

                        <div class="input-group-append" style="position: absolute; top: 10px; right: 0.5rem;">
                          <button class="btn" type="button" id="password-toggle" style="padding: 0px 15px;">
                              <i class="fa fa-eye"></i>
                          </button>
                        <div class="invalid-feedback">Please enter your password!</div>
                      </div>
                    </div>
                  </div>
					
                    <div class="col-12 text-center">
                      <button class="btn btn-primary loginField" type="submit">Login</button>
                    </div>
                  </form>

                </div>
              </div>

              

            </div>

            



          </div>
        </div>

      </section> -->

<script>

const passwordInput = document.getElementById("password");
const passwordToggle = document.getElementById("password-toggle");

passwordToggle.addEventListener("click", function () {
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        passwordToggle.innerHTML = '<i class="fa fa-eye-slash"></i>';
    } else {
        passwordInput.type = "password";
        passwordToggle.innerHTML = '<i class="fa fa-eye"></i>';
    }
});
</script>
@endsection
@endif
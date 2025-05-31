@extends('layout')
<style>
.eye-icon {
    position: absolute;
    right: 25px;
    top: 52%;
    transform: translateY(-50%);
    cursor: pointer;
    width: 25px;
    height: 25px;
    background-image: url("{{asset('assets/img/eye-password.png')}}"); /* Replace with your eye icon image */
    background-size: cover;
}
</style>
<?php //echo "<pre>";print_r($sessionData = session('data'));exit;?>
@section('content')
<main id="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>Create New User</h1>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('users.index') }}" title="back"><i class="bi bi-arrow-left"></i></a>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Error!</strong> Please check the form fields.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card pt-4">
        <div class="card-body">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Client :</label>
                            <select name="client_id" id="client_id" class="form-control">
                                <option value="">Select Client</option>
                            </select required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Name:</label>
                            <input type="text" name="name" class="form-control" id="name" placeholder="Name" value="{{old('name')}}" required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Role:</label>
                            <select name="role" id="role" class="form-control" required>
                                <option value="">Select Role</option>
                                @if(session('data.userRole') == 'super_admin')
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Manager</option>
                                <option value="supervisor" {{ old('role') == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                                <option value="mis" {{ old('role') == 'mis' ? 'selected' : '' }}>Mis</option>
                                    <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
                                @else
                                <option value="supervisor" {{ old('role') == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                                <option value="mis" {{ old('role') == 'mis' ? 'selected' : '' }}>Mis</option>
                                <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-6" id="assign-by-div" style="display: none;">
                        <div class="mb-4">
                            <label class="form-label">Manager:</label>
                            <select name="manager" id="manager" class="form-control">
                                <!-- Populate this dropdown with relevant options -->
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-6" id="assign-by-div2" style="display: none;">
                        <div class="mb-4">
                            <label class="form-label">Supervisor:</label>
                            <select name="supervisor" id="supervisor" class="form-control">
                                <!-- Populate this dropdown with relevant options -->
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Email Id:</label>
                            <input type="text" name="email" class="form-control" id="email" placeholder="Email" value="{{old('email')}}" required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Mobile:</label>
                            <input type="text" class="form-control" minlength="10" maxlength="10" name="mobile" id="mobileno" placeholder="Mobile" value="{{ old('mobileno') }}" required onkeypress="return isNumberKey(event)" onpaste="return false">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Username:</label>
                            <input type="text" name="username" class="form-control" placeholder="Username" id="username"  value="{{old('username')}}" required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-4"> 
                            <label class="form-label">Password:</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Password" value="" required><span id="toggle-password" class="eye-icon"></span>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Status:</label>
                            <select name="status" class="form-control" required>
                                <option value="1">Active</option>
                                <option value="2">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary submit_button">Save</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</main>
<script>

    $(document).ready(function() {
        $('#toggle-password').click(function() {
            var passwordInput = $('#password');
            var passwordFieldType = passwordInput.attr('type');
            if (passwordFieldType === 'password') {
                passwordInput.attr('type', 'text');
                //  $('#toggle-password').css('background-image', url("{{asset('assets/img/eye-o.png')}}"));
            } else {
                // $('#toggle-password').css('background-image', url("{{asset('assets/img/eye-password.png')}}"));
                passwordInput.attr('type', 'password');
            }
        });

        $.ajax({
            url: "{{ route('users.client_list') }}",
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                var options = '<option value="">Select Client </option>';
                $.each(data, function(key, value) {
                    options += '<option value="' + key + '">' + value + '</option>';
                });
                $('#client_id').html(options);
            }
        });

        $('#username').on('blur',function() {
            var username = $(this).val();
            var usernamelist = @json($usernamelist);
            var usernamefound = usernamelist.includes(username);
            if (usernamefound === false) {
                $('.submit_button').prop('disabled', false);
            } else {
                swal.fire('username is already exist.');
                $('.submit_button').prop('disabled', true);
            }
            console.log(usernamefound);
        });

        $('#name').on('blur',function() {
            var name = $(this).val();
            var userlist = @json($userlist);
            var usernamefound = userlist.includes(name);
            if (usernamefound === false) {
                $('.submit_button').prop('disabled', false);
            } else {
                swal.fire('name is already exist.');
                $('.submit_button').prop('disabled', true);
            }
        });

        $('#email').on('blur',function(){

            var email = $(this).val();
                var emailList = @json($emailList);
                var Emailfound = emailList.includes(email);
                if (Emailfound === false) {
                    $('.submit_button').prop('disabled', false);
                } else {
                    swal.fire('Email is already exist.');
                    $('.submit_button').prop('disabled', true);
                }
                console.log(Emailfound);
        });


        $(document).on('keypress','#mobileno',function(e){
            if($(e.target).prop('value').length>=10){  
            if(e.keyCode!=32)
            {return false} 
            }
            var inputValue = e.key;
            if (inputValue === '-' || inputValue === '+' || inputValue === 'E' || inputValue === 'e' ) {
                e.preventDefault(); // Prevent the "-" character from being entered
            }
        });

        $('#role').change(function() {
            var selectedRole = $(this).val();
            // Show the 'Assign By' dropdown when 'supervisor' or 'agent' is selected
            if (selectedRole === 'supervisor' || selectedRole === 'user') {
                $('#assign-by-div').show();
                $('#manager').prop('required',true);
            } else {
                $('#assign-by-div').hide();
                $('#manager').prop('required',false);
            }   

            $.ajax({
                url: "{{ route('users.manager_list') }}",
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var options = '<option value="">Select manager</option>';
                    $.each(data, function(key, value) {
                        options += '<option value="' + key + '">' + value + '</option>';
                    });
                    $('#manager').html(options);
                }
            });
        });

        $('.submit_button').click(function (){
            var email = $('#email').val();
            var emailList = @json($emailList);
            var Emailfound = emailList.includes(email);
            var name = $('#name').val();
            var userlist = @json($userlist);
            var namefound = userlist.includes(name);
            var username = $('#username').val();
            var usernamelist = @json($usernamelist);
            var usernamefound = usernamelist.includes(username);

            if(usernamefound === true){
                swal.fire('Username is already exist.');
                    return false;
            }else if(Emailfound === true) {
                swal.fire('Email Id is already exist.');
                    return false;
            }else if(namefound === true){
                swal.fire('Name is already exist.');
                    return false;
            }
        });


        $('#role').change(function() {
            var selectedRole = $(this).val();
            // Show the 'Assign By' dropdown when 'supervisor' or 'agent' is selected
            if (selectedRole === 'user') {
                $('#assign-by-div2').show();
                $('#supervisor').prop('required',true);
            } else {
                $('#assign-by-div2').hide();
                $('#supervisor').prop('required',false);
            }   

            $.ajax({
                url: "{{ route('users.supervisor_list') }}",
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var options = '<option value="">Select Assignto</option>';
                    $.each(data, function(key, value) {
                        options += '<option value="' + key + '">' + value + '</option>';
                    });
                    $('#supervisor').html(options);
                }
            });
        });
        
    });


        function isNumberKey(evt) {
        var charCode = (evt.which) ? evt.which : event.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
        }
        return true;
    }
</script>
@endsection
@extends('layout')

@section('content')

<?php //echo "<pre>";print_r($manager);exit;?>
<main id="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>Edit User</h1>
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
    <?php 

    //echo "asdfasd<pre>"; print_r($clients);die;
?>
    <div class="card pt-4">
        <div class="card-body">
            <form action="{{ route('users.update', $users->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Client :</label>
                            {{-- <select name="client_id" id="client_id" class="form-control">
                                @foreach ($clients as $clientId => $clientName)
                                    <option value="{{ $clientId }}" {{ $users->client_id == $clientId ? 'selected' : '' }}>{{ $clientName }}</option>
                                @endforeach
                            </select> --}}
                            <input type="text" name="client_id" id="client_id" class="form-control" value="{{$clients[0]}}" required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Name:</label>
                            <input type="text" name="name" id="name" value="{{ $users->name }}" class="form-control" placeholder="Name"required>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-element-label">Role</label>
                            <input type="text" name="role" id="role" value="{{strtolower($users->role)}}" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="col-lg-6" id="assign-by-div" style="display: none;">
                        <div class="mb-4">
                            <label class="form-label">Manager:</label>
                            <select name="manager" id="manager" class="form-control">
                                @foreach ($manager as $managerid => $managername)
                                    <option value="{{ $managerid }}" {{ $users->manager_id == $managerid ? 'selected' : '' }}>{{ $managername }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-6" id="assign-by-div2" style="display: none;">
                        <div class="mb-4">
                            <label class="form-label">Supervisor:</label>
                            <select name="supervisor" id="supervisor" class="form-control">
                                @foreach ($supervisor as $supervisorid => $supervisorname)
                                <option value="{{ $supervisorid }}" {{ $users->supervisor_id == $supervisorid ? 'selected' : '' }}>{{ $supervisorname }}</option>
                            @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Email:</label>
                            <input type="text" name="email" value="{{ $users->email }}" class="form-control" placeholder="Email" required>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Mobile:</label>
                            {{-- <input type="number" id="mobileno" name="mobile" value="{{ $users->mobile }}" class="form-control" placeholder="Mobile" required> --}}
                            <input type="text" class="form-control" minlength="10" maxlength="10" name="mobile" id="mobileno" placeholder="Mobile" value="{{ $users->mobile }}" required onkeypress="return isNumberKey(event)" onpaste="return false">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Username:</label>
                            <input type="text" name="username" value="{{ $users->username }}" class="form-control" placeholder="Username" required readonly>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Status:</label>
                            <select name="status" class="form-control" required>
                                <option value="1" {{ ($users->status == '1' || strtolower($users->status) == 'active') ? 'selected' : '' }}>Active</option>
                                <option value="2" {{ ($users->status == '2'  || strtolower($users->status) == 'inactive')? 'selected' : '' }}>Inactive</option>
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


        var selectedRole = $('#role').val();
        if (selectedRole === 'supervisor' || selectedRole === 'user') {
        $('#assign-by-div').show();
            $('#assignby').prop('required', true);
        } else {
            $('#assign-by-div').hide();
            $('#assignby').prop('required', false);
        }
        if (selectedRole === 'user') {
        $('#assign-by-div2').show();
        $('#supervisor').prop('required', true);
        } else {
            $('#assign-by-div2').hide();
            $('#supervisor').prop('required', false);
        }


        var selectedRole = $('#role').val();
        // Show the 'Assign By' dropdown when 'supervisor' or 'user' is selected
        if (selectedRole === 'supervisor' || selectedRole === 'user') {
            $('#assign-by-div').show();
            $('#manager').prop('required', true);
        } else {
            $('#assign-by-div').hide();
            $('#manager').prop('required', false);
        }

            var selectedRole = $('#role').val();
            // Show the 'Assign By' dropdown when 'user' is selected
            if (selectedRole === 'user') {
                $('#assign-by-div2').show();
                $('#supervisor').prop('required', true);
            } else {
                $('#assign-by-div2').hide();
                $('#supervisor').prop('required', false);
            }

        function isNumberKey(evt) {
        var charCode = (evt.which) ? evt.which : event.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
        return true;
        }


</script>

@endsection
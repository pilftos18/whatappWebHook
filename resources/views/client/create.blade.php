
@extends('layout')
@section('content')
    
        {{-- @php
            $isEdit = isset($client);
            //$route =  $isEdit ? route('client.update', $client->id) : route('client.store');
            //$isEdit ? route('client.update', $client->id) : route('client.store');

            $route = route('client.store');
            $method = $isEdit ? 'PUT' : 'POST';
            $title = $isEdit ? 'Edit Client' : 'Create Client';
            $button = $isEdit ? 'Update' : 'Create';
        @endphp --}}
   
        
<main id="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>Create Client</h1>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('client.index') }}" title="back"><i class="bi bi-arrow-left"></i></a>
            </div>
        </div>
    </div>
    <div class="card pt-4">
        <div class="card-body">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <!-- create.blade.php -->
            <form method="POST" action="{{ route('client.store') }}"  enctype="multipart/form-data">
                    @csrf
                    {{-- @if($isEdit)
                            @method('PUT')
                    @endif --}}
                    
                <div class="row">
                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="name">Client Name</label>
                    <input type="text" class="form-control" name="name" id="name" value="{{old('name')}}" required>
                    {{-- {{$client->name}} --}}
                    @error('client')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="email">Email Id.</label>
                    <input type="email" class="form-control"  name="email" id="email" value="{{old('email')}}">
                    {{-- {{$client->email}} --}}
                    @error('email')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="description">Description</label>
                    <textarea name="description" class="form-control"  id="description" cols="" rows="" style="height: 40px;" value="{{old('description')}}"></textarea>
                    {{-- {{$client->description}} --}}
                    @error('description')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

      
                {{-- <div class="col-lg-6 mb-4">
                    <label class="form-label" for="mobileno">Mobile No.</label>
                    <input type="number" class="form-control" minlength="10" maxlength="10" name="mobileno" id="mobileno" value="{{old('mobileno')}}" required>
                    @error('mobileno')
                        <span>{{ $message }}</span>
                    @enderror
                </div> --}}

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" class="form-control" name="status" required>
                    <option value="1" >Active</option>
                    <option value="2" >Inactive</option>
                    </select>
                    @error('status')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                {{-- {{ old('status', isset($client) && $client->status == 1 ? 'selected' : '' )}} --}}
                {{-- {{ old('status', isset($client) && $client->status == 2 ? 'selected' : '' )}} --}}

                {{-- <div style="margin-top : 4%;" class="col-lg-4">
                    <label for="whatsapp mr-4">Whatsapp.</label>
                    <input style="margin-left:10px;" type="checkbox" id="whatsapp" name="whatsapp"  {{ old('whatsapp', isset($client->whatsapp)) ? 'checked' : '' }} required>
                    @error('whatsapp')
                            <span>{{ $message }}</span>
                    @enderror
                </div> --}}

                <h4>License Count</h4>

                <div class="col-lg-4 mb-4">
                    <label class="form-label" for="admin">Admin</label>
                    <input type="number" class="form-control"  name="admin" id="admin"  value="{{old('admin')}}" min="0" required>
                    {{-- {{$client->admin}} --}}
                    @error('admin')
                        <span>{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-lg-4 mb-4">
                    <label class="form-label" for="manager">Manager</label>
                    <input type="number" class="form-control"  name="manager" id="manager" value="{{old('manager')}}" min="0"  required>
                    @error('manager')
                        <span>{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-lg-4 mb-4">
                    <label class="form-label" for="supervisor">Supervisor</label>
                    <input type="number" class="form-control"  name="supervisor" id="supervisor"   value="{{old('supervisor')}}" min="0" required>
                    @error('supervisor')
                        <span>{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-lg-4 mb-4">
                    <label class="form-label" for="mis">Mis</label>
                    <input type="number" class="form-control"  name="mis" id="mis" value="{{old('mis')}}" min="0" required>
                    @error('mis')
                        <span>{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-lg-4 mb-4">
                    <label class="form-label" for="agent">Agent</label>
                    <input type="number" class="form-control"  name="agent" id="agent" value="{{old('agent')}}" min="0" required>
                    @error('agent')
                        <span>{{ $message }}</span>
                    @enderror
                </div>


                <div class="text-center">
                    <button class="btn btn-primary submit_button" type="submit">Save</button>
                </div> 
                </div>
            </form>

        </div>
    </div>
	<script>
    $(document).ready(function() {


        $('#admin,#manager,#supervisor,#mis,#agent').keypress(function(event) {
            var inputValue = event.key;
            if (inputValue === '-' || inputValue === '+' || inputValue === 'E' || inputValue === 'e' ) {
                event.preventDefault();
            }
            if($(event.target).prop('value').length>=3){
            if(event.keyCode!=32)
            {return false} 
            }
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

        $('#name').on('blur change',function() {
            var client = $(this).val();
            var clientlist = @json($clientlist);
            //console.log(clientlist);
            var clientfound = clientlist.includes(client);
            if (clientfound === false) {
                $('.submit_button').prop('disabled', false);
            } else {
                swal.fire('Client is already exist.');
                $('.submit_button').prop('disabled', true);
            }
            //console.log(campainfound);
        });

        $('.submit_button').click(function (){
            var client = $('#name').val();
            var clientlist = @json($clientlist);
            var clientfound = clientlist.includes(client);
            var agent = $('#agent').val();
            var supervisor = $('#supervisor').val();
            var manager = $('#manager').val();

            if(agent !== '0' ){
                if(supervisor === '0' || manager == '0'){
                    swal.fire('Manager and Supervisor are mandatory for creation of Agent');
                    return false;
                }
            }else if(supervisor !== '0' ){

                if(manager === '0' ){
                    swal.fire('Manager are mandatory for creation of supervisor');
                    return false;
                }

            }else if(clientfound === true) {
                swal.fire('Client is already exist.');
                    return false;
            }

        });

        $("form").submit(function (event) {
            var nameInput = $("#name");
            // var emailInput = $("#email");
            var allowCreditInput = $('#mobileno,#admin,#manager,#supervisor,#mis,#agent');
            var phoneNumber = $('#mobileno');

            var isValid = true;
            

            // Check if Name field is empty
            if (nameInput.val().trim() === "") {
            nameInput.addClass("is-invalid");
            isValid = false;
            } else {
            nameInput.removeClass("is-invalid");
            }

            // Check if Email field is empty or invalid format
            // var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            // if (emailInput.val().trim() === "" || !emailPattern.test(emailInput.val())) {
            // emailInput.addClass("is-invalid");
            // isValid = false;
            // } else {
            // emailInput.removeClass("is-invalid");
            // }

            // Check if Allow field is empty or non-numeric
            if (allowCreditInput.val().trim() === "" || isNaN(allowCreditInput.val())) {
            allowCreditInput.addClass("is-invalid");
            isValid = false;
            } else {
            allowCreditInput.removeClass("is-invalid");
            }

            if (!isValid) {
            event.preventDefault();
            }
        });
    });

    
</script>
</main>
@endsection
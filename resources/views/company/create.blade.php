
@extends('layout')
@section('content')

<main id="main">
<div class="pagetitle justify-content-between d-flex">
    <h1>Add Organization</h1>
    <a class="btn btn-outline-primary btn-sm" href="{{ route('company.index') }}"> <i class="bi bi-arrow-left"></i></a>
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
<form method="POST" action="{{ route('company.store') }}" enctype="multipart/form-data">
    @csrf
<div class="row">
    <div class="col-lg-6 mb-4">
        <label class="form-label" for="name">Company Name</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Company Name" required>
        @error('name')
            <span>{{ $message }}</span>
        @enderror
    </div>

    <div  class="col-lg-6 mb-4">
        <label class="form-label" for="email">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="Email Address" required>
        @error('email')
            <span>{{ $message }}</span>
        @enderror
    </div>

    <div class="col-lg-6 mb-4">
        <label class="form-label" for="envtype">Environment Type</label>
        <select id="envtype" class="form-control" name="envtype" required>
            <option value="production" {{ old('envtype') }}>Production</option>
            <option value="preproduction" {{ old('envtype') }}>Pre-production</option>
        </select>
        @error('envtype')
            <span>{{ $message }}</span>
        @enderror
    </div>


    <div  class="col-lg-6 mb-4 credit">
        <label class="form-label" for="max_count">Allow Credits</label>
        <input type="number" class="form-control" id="max_count" name="max_count" value="{{ old('max_count') }}" placeholder="Credits " required>
        @error('email')
            <span>{{ $message }}</span>
        @enderror
    </div>

    <div class="col-lg-6 mb-4 expirydate">
        <label class="form-label" for="date">Expiry Date</label>
            <input type="text" id="datepicker" class="form-control datepicker" name="date">
    </div>


    {{-- <div  class="col-lg-6 mb-4">
        <label class="form-label" for="website">Website</label>
        <input type="text" class="form-control" id="website" name="website" value="{{ old('website') }}" required>
        @error('website')
            <span>{{ $message }}</span>
        @enderror
    </div>

    <div  class="col-lg-6 mb-4">
        <label class="form-label" for="file">Upload Image</label>
        <input type="file" class="form-control" id="file" name="file" required>
       @error('file') <!-- Updated field name to 'file' -->
            <span>{{ $message }}</span>
        @enderror
    </div> --}}

    <div  class="col-lg-6 mb-4">
        <label class="form-label" for="status">Status</label>
        <select id="status" class="form-control" name="status" required>
            <option value="1" {{ old('status') == 1 ? 'selected' : '' }}>Active</option>
            <option value="2" {{ old('status') == 2 ? 'selected' : '' }}>Inactive</option>
        </select>
        @error('status')
            <span>{{ $message }}</span>
        @enderror
    </div>

    <div class="text-center" style="margin-top: 28px;">
        <button class="btn btn-primary" type="submit">Submit</button>
    </div>
    </div>
</form>

  </div>
</div>

<script>
    $(document).ready(function() {

        $('.expirydate').css('display','none');
        $("#datepicker").attr("required", false);

        $('#max_count').keypress(function(event) {
            var inputValue = event.key;
            
            // Check if the input value is "-"
            if (inputValue === '-') {
                event.preventDefault(); // Prevent the "-" character from being entered
            }
        });

        $('#envtype').change(function(event) {
            var value = $('#envtype').val();
            if(value == 'preproduction'){
                $('.expirydate').css('display','block');
                $("#datepicker").attr("required", true);
            }
            else if(value == 'production')
            {
                $('.expirydate').css('display','none');
                $("#datepicker").attr("required", false);
                $("#datepicker").val(null);
                $("#datepicker").datepicker("setDate", null);
                $("#datepicker").removeAttr("value");

            }
            else{
                $('.expirydate').css('display','none');
                $("#datepicker").attr("required", false);
                $("#datepicker").val(null);
                $("#datepicker").datepicker("setDate", null);
                $("#datepicker").removeAttr("value");
            }
        });


        $( "#datepicker" ).datepicker({
            changeYear: true,
            changeMonth: true,
            minDate:0,
            dateFormat: "yy-m-dd",
            yearRange: "-100:+20",
        });
    });
</script>
</main>
@endsection
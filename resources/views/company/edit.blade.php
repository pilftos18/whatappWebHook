@extends('layout')
@section('content')
 
<main id="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>Update Organization</h1>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('company.index') }}"> <i class="bi bi-arrow-left"></i></a>
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

  <!-- update.blade.php -->
        <form method="POST" action="{{ url('company/' .$company->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
        <div class="row">
            <div class="col-lg-6 mb-4">
                <label class="form-label" for="name">Company Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{$company->name}}" required>
                @error('name')
                    <span>{{ $message }}</span>
                @enderror
            </div>

            <div class="col-lg-6 mb-4">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ $company->email }}" required>
                @error('email')
                    <span>{{ $message }}</span>
                @enderror
            </div>

            
            <div class="col-lg-6 mb-4">
                <label class="form-label" for="max_count">Available Credits</label>
                <input type="number" class="form-control" id="max_count" name="max_count" value="{{ $company->max_count}}" required readonly>
                @error('website')
                    <span>{{ $message }}</span>
                @enderror
            </div>

            <div class="col-lg-6 mb-4">
                <label class="form-label" for="add_max_count">Add more Credits</label>
                <input type="number" class="form-control" id="add_max_count" name="add_max_count" value="0">
                <input type="hidden" id="data_id" name="data_id" val="{{$company->id}}">
            </div>

            <div class="col-lg-6 mb-4">
                <label class="form-label" for="envtype">Environment Type</label>
                <select id="envtype" class="form-control" name="envtype" required>
                    <option value="production" {{ old('envtype', $company->envtype) === 'production' ? 'selected' : '' }}>Production</option>
                    <option value="preproduction" {{ old('envtype', $company->envtype) === 'preproduction' ? 'selected' : '' }}>Pre-production</option>
                </select>
                @error('envtype')
                    <span>{{ $message }}</span>
                @enderror
            </div>
        
        
            <div class="col-lg-6 mb-4 expirydate">
                <label class="form-label" for="date">Expiry Date</label>
                    <input type="text" id="datepicker" class="form-control datepicker" name="date" value="{{ $company->expirydate }}">
            </div>

            {{-- <div class="col-lg-6 mb-4">
                <label class="form-label" for="website">Website</label>
                <input type="text" class="form-control" id="website" name="website" value="{{ $company->website}}" required>
                @error('website')
                    <span>{{ $message }}</span>
                @enderror
            </div>

            <div class="col-lg-6 mb-4">
                <label class="form-label" for="file">Upload Image</label>
                <input type="file" class="form-control" id="file" name="file" required>
                @if ($company->file)
                    <span class="invalid-feedback d-block">Previously uploaded file: {{ $company->file }}</span>
                @endif
                @error('file')
                    <span>{{ $message }}</span>
                @enderror
            </div> --}}

            <div class="col-lg-6 mb-4">
                <label class="form-label" for="status">Status</label>
                <select id="status" class="form-control" name="status" required>
                    <option value="1" {{ $company->status == 1 ? 'selected' : '' }}>Active</option>
                    <option value="2" {{ $company->status == 2 ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')
                    <span>{{ $message }}</span>
                @enderror
            </div>

            <div class="text-center">
            <button class="btn btn-primary" type="submit">Update</button>
            
            </div>
        </form>
        </div>
  </div>
</div>


<script>
    $(document).ready(function() {

        $('.expirydate').css('display','none');
        $("#datepicker").attr("required", false);

        var value = $('#envtype').val();
            if(value == 'preproduction'){
                $('.expirydate').css('display','block');
                $("#datepicker").attr("required", true);
            }

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
                var set_val = "<?php echo $company->expirydate; ?>";
                $("#datepicker").val(set_val);

            }
            else if(value == 'production')
            {
                $('.expirydate').css('display','none');
                $("#datepicker").attr("required", false);
                $("#datepicker").val(null);
                $("#datepicker").removeAttr("value");
            }
            else{
                $('.expirydate').css('display','none');
                $("#datepicker").attr("required", false);
                $("#datepicker").val(null);
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

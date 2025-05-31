
@extends('layout')
@section('content')

        @php
            $isEdit = isset($module);
            $route = $isEdit ? route('module.update', $module->id) : route('module.store');
            $method = $isEdit ? 'PUT' : 'POST';
            $title = $isEdit ? 'Edit Module' : 'Create Module';
            $button = $isEdit ? 'Update' : 'Create';
        @endphp
        

<main id="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>{{$title}}</h1>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('module.index') }}"><i class="bi bi-arrow-left"></i></a>
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
            <form method="POST" action="{{ $route }}"  enctype="multipart/form-data">
                    @csrf
                    @if($isEdit)
                            @method('PUT')
                    @endif
                    
                <div class="row">

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="company">Organization Name</label>
                    <select id="company" class="form-control" name="company[name]" required>
                        @foreach ($companyname as $key => $company)
                            <option value="{{ $company }}" data-id="{{ $key }}"
                                {{ (old('company.name', ($isEdit && $module->company=== $company) ? $company : null)) === $company ? 'selected' : '' }}>
                                {{ $company }}
                            </option>
                        @endforeach
                    </select>
                    @error('company[name]')
                        <span>{{ $message }}</span>
                    @enderror
                    <input type="hidden" name="company[id]" id="company_id" value="{{ old('company.id', $isEdit ? $module->client_id : '') }}">
                </div>

                {{-- <div class="col-lg-6 mb-4">
                    <label class="form-label" for="vendorname">API Vender Name</label>
                    <select id="vendorname" class="form-control" name="vendorname" required>
                        <option value="singzy"{{ old('vendorname', $isEdit ? $module->vendorname : '') }}>Singzy</option>
                        <option value="authbridge"{{ old('vendorname', $isEdit ? $module->vendorname : '') }}>Authbridge</option>
                    </select>
                    @error('vendorname')
                        <span>{{ $message }}</span>
                    @enderror
                </div> --}}

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="vendorname">Vendor</label>
                    <select id="vendorname" class="form-control" name="vendorname" required>
                            <option value="">Select....</option>
                        @foreach ($vendorname as $vendor)
                            <option value="{{ $vendor }}" data-api-name="{{ $vendor }}"
                                {{ (old('vendorname', ($isEdit && $module->vendorname === $vendor) ? $vendor : null)) === $vendor ? 'selected' : '' }}>
                                {{ $vendor }}
                            </option>
                        @endforeach
                    </select>
                    @error('vendorname')
                        <span>{{ $message }}</span>
                    @enderror
                </div>



                    
                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="apidropdown">Module</label>
                    <select id="apiname" class="form-control" name="apiname" required>
                    </select>
                    @error('apiname')
                        <span>{{ $message }}</span>
                    @enderror
                </div>
                

                {{-- <div class="col-lg-6 mb-4">
                    <label class="form-label" for="apiurl">API Url</label>
                    <input type="text" class="form-control" id="apiurl" name="apiurl" value="{{ old('apiurl', $isEdit ? $module->apiurl : '') }}" required>
                    @error('apiurl')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="apitesturl">API Test Url</label>
                    <input type="text" class="form-control" id="apitesturl" name="apitesturl" value="{{ old('apitesturl', $isEdit ? $module->apitesturl : '') }}" required>
                    @error('apitesturl')
                        <span>{{ $message }}</span>
                    @enderror
                </div> --}}

                <div class="col-lg-6 mb-4">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" class="form-control" name="status" required>
                    <option value="1" {{ old('status', isset($module) && $module->status == 1 ? 'selected' : '' )}}>Active</option>
                    <option value="2" {{ old('status', isset($module) && $module->status == 2 ? 'selected' : '' )}}>Inactive</option>
                    </select>
                    @error('status')
                        <span>{{ $message }}</span>
                    @enderror
                </div>

                <div class="text-center">
                    <button class="btn btn-primary" type="submit">{{ $button }}</button>
                    <!-- <a class="btn btn-danger" href="{{ route('module.index') }}"> Back</a> -->
                </div> 
                </div>
            </form>

        </div>
    </div>
	<script>
    $(document).ready(function() {
        // When the company dropdown value changes
        $('#company').change(function() {
            // Get the selected company name
            var companyName = $(this).val();

            // Find the selected option and retrieve the company ID from the data-id attribute
            var companyId = $('option[value="' + companyName + '"]').data('id');

            // Set the company ID in the hidden input field
            $('#company_id').val(companyId);
        });
        // Trigger the change event on page load to initialize the hidden input field
        $('#company').trigger('change');
        ///////////////////////////////////////////////


            var vendorDropdown = $('#vendorname');
            var apiDropdown = $('#apiname');
            var selectedApiName = "{{ isset($module->apiname) ? $module->apiname : '' }}"; // Replace with the actual value of apiname on the edit page
            // Populate the initial value of apiname select element
            apiDropdown.append($('<option></option>').val(selectedApiName).text(selectedApiName));


            vendorDropdown.on('change', function() {
                var selectedVendor = $(this).val();

                var csrfToken = $('meta[name="csrf-token"]').attr('content');

                // Add the CSRF token to the AJAX request headers
                $.ajaxSetup({
                headers: {
                'X-CSRF-TOKEN': csrfToken
                }
                });
                // Make AJAX request
                $.ajax({
                    url: "{{ route('api.data') }}",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        vendor: selectedVendor
                    },
                    success: function(response) {
                        // Populate the second dropdown with retrieved data
                        apiDropdown.empty(); // Clear previous options
                        apiDropdown.append(('<option value="">Select.....</option>'));
                        $.each(response, function(index, api) {
                            apiDropdown.append($('<option></option>')
                                .val(api)
                                .text(api)
                            );
                        });
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            });
});
</script>
</main>
@endsection
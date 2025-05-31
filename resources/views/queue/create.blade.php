
@extends('layout')
@section('content')

<main id="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>Create queue</h1>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('queue.index') }}" title="back"><i class="bi bi-arrow-left"></i></a>
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
            <form method="POST" action="{{ route('queue.store') }}"  enctype="multipart/form-data">
                    @csrf
                    {{-- @if($isEdit)
                            @method('PUT')
                    @endif --}}

                <div class="col-lg-12 queue-row">
                    <div class="row justify-content-center align-items-center form-box">
                        <div class="col-lg-4">
                            <div class="form-group field-vaanicampaignqueue-queue">
                                <label class="form-label" for="queuename">Queue Name: </label>
                                <input type="text" name="queuename" value="{{old('queuename')}}" id="queuename" class="form-control" required>
                                @error('queuename')
                            @enderror
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group field-vaanicampaignqueue-user">
                                <label class="form-label" for="vaanicampaignqueue-user">User</label>
                                <select name="VaaniCampaignqueue[user][]" class="form-control select2-multiple" id="vaanicampaignqueue-user" multiple required>
                                    <option value="All">All</option>
                                    @foreach ($users as $userid => $username)
                                    <option value="{{ $userid }}">{{ $username }}</option>
                                    @endforeach

                                </select>
                                @error('VaaniCampaignqueue[user][]')

                            @enderror
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label" for="status">Status :</label>
                            <select id="status" class="form-control" name="status" required>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                            </select>
                            @error('status')
                                <span>{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                <div class="text-center">
                    <button class="btn btn-primary submit_button" type="submit">Save</button>
                </div> 
                </div>


            </form>

        </div>
    </div>
	<script>
    $(document).ready(function() {

        $('.select2-multiple').select2({
            placeholder: "Select",
            allowClear: true
        });

    });

    //$('#vaanicampaignqueue-user option[value="All"]').prop('selected', true); // Pre-select 'All'
                    
    // Listen for changes in the select element
    $('#vaanicampaignqueue-user').on('change', function () {
        if ($('#vaanicampaignqueue-user option:selected').length > 1) {
            // If more than one option is selected, deselect the 'All' option
            $('#vaanicampaignqueue-user option[value="All"]').prop('selected', false);
        } else if ($('#vaanicampaignqueue-user option:selected').length === 0) {
            // If no options are selected, re-select the 'All' option
            $('#vaanicampaignqueue-user option[value="All"]').prop('selected', false);
        }
    });
    $('#vaanicampaignqueue-user').change(function () {
            var selectedValue = $(this).val();

            if (selectedValue != 'All') {
                $('#vaanicampaignqueue-user option[value="All"]').removeAttr('selected');
            }else{
                $('#vaanicampaignqueue-user option[value="All"]').removeAttr('selected');
            }
        });


</script>
</main>
@endsection
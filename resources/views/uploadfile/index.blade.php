


@extends('layout')
@section('content')

<main id="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>Upload file</h1>
                {{-- <a class="btn btn-outline-primary btn-sm" href="{{ route('uploadfile.index') }}"><i class="bi bi-arrow-left"></i></a> --}}
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
                    
                <div class="row">

                    <div class="col-lg-3 mb-4">
                        <select id="Campaign" class="form-control" name="Campaign" required>
                            <option value="">Select....</option>
                            @foreach ($campaigns as $campaignid => $campaignname)
                            <option value="{{$campaignid}}">{{ $campaignname }}</option>
                        @endforeach
                        </select>
                        @error('Campaign')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>


                    <div class="col-lg-2 mb-4">
                        <select id="templete" class="form-control" name="templete" required>
                            <option value="">Select......</option>
                            @foreach ($templetelist as $templeteid => $templetename)
                                <option value="{{ $templeteid }}">{{ $templetename }}</option>
                            @endforeach
                        </select>
                        @error('templete')
                            <span>{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-lg-4 mb-4">
                        <input class="form-control" type="file" name="file" id="file" accept=".csv" required>

                    </div>

                    <div class="col-lg-3 mb-4">
                        <button class="btn btn-primary submit_button" type="submit">Submit</button>

                        <a href="{{ asset('/storage/app/public/uploads/bulk/sample.csv') }}" class="btn btn-outline-success">Sample CSV</a>
                    </div> 
                </div>
        </div>  

        {{-- <div id="loader" class="loader-wrapper">
            <div class="loader-container">
                <div class="loader-box">
                    <div class="ring"></div>
                    <div class="ring"></div>
                    <div class="ring"></div>
                    <div class="ring"></div>
                    <div class="loading-logo">
                        <img src="{{asset('assets/img/edas-logo-light.png')}}" alt="Edas Logo">
                    </div>
                </div>
            </div>
        </div> --}}

        <div class="alert alert-success" id="alertMessage" style="display:none;">
            <p id="successMSG"></p>
        </div>

        <div class="card-body">
            <table class="table table-striped" id="fileupload_table">
                <thead>
                    <tr>
                        <th>Sr.No</th>
                        <th>Client</th>
                        <th>Campaign</th>
                        <th>Filename</th>
                        <th>Is processed</th>
                        <th>Count</th>
                        <th>created at</th>
                        {{-- <th>Downlaod</th> --}}
                    </tr>
                </thead>
                </table>
        </div>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script>
        $(document).ready(function(){

            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            $('#fileupload_table').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: "{{ route('list.bulkuploadfile') }}",
                    type: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken // Include the CSRF token in the headers
                    }
                },
                columns: [
                    { 
                        data: 'id',
                        render: function (data, type, row, meta) {
                            // Calculate the serial number using the row index
                            var srNo = meta.row + 1;

                            return srNo;
                        },
                        orderable: false,
                        searchable: false
                    },
                    { data: 'client_name', name: 'client_name' },
                    { data: 'campaignname', name: 'campaignname' },
                    { data: 'filename', name: 'filename' },
                    { 
                    data: 'is_processed', 
                    name: 'is_processed',
                    render: function (data, type, row) {
                        if (data == '1') {
                            return '<b class="text-danger">Pending</b>';
                        } else{
                            return '<b class="text-success">Completed</b>';
                        }
                        }
                    },
                    { data: 'cnt', name: 'cnt' },
                    { data: 'created_at', name: 'created_at' },
                    // { 
                    //     data: 'download_url',
                    //     name: 'download_url',
                    //     render: function (data, type, row) {

                    //         return '<b class="text-success"><a href="' + data + '" class=""><i class="bi bi-download"></i></a></b>';
                    //         }
                    // },
                    

                ]
            });


            $('.submit_button').click(function (){

                var campaign = $("#Campaign").val();
                var templete = $("#templete").val();
                var file = $("#file").prop('files')[0];
                // var fileType = file.type;

                if (campaign == '') {
                    swal.fire('Please select campaign');
                    //loader.style.display = 'none';
                    return false;
                } else if (templete == '') {
                    swal.fire('Please select templete');
                    //loader.style.display = 'none';
                    return false;
                }else if (!file) {
                    swal.fire('Please select a file');
                    return false;
                }else if ($("#file").prop('files')[0].type != 'text/csv') {
                    swal.fire('Please select a CSV file');
                    return false;
                    //loader.style.display = 'none';

                }else {

                    var formData = new FormData();

                    formData.append('templete', templete);
                    formData.append('campaign', campaign);
                    formData.append('file', file);


                    var csrfToken = $('meta[name="csrf-token"]').attr('content');

                            
                    $.ajaxSetup({
                    headers: {
                    'X-CSRF-TOKEN': csrfToken
                    }
                    });
            
                    $.ajax({
                    url: "{{ route('postData.FileBulk') }}",
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        if(response.status == 'success'){

                            showAlert(response.status,response.msg, true);
                            $('#file').val('');
                            
                            // if (response.download) {

                            //     var link = document.createElement('a');
                            //     link.href = response.file_url;
                            //     link.download = response.file_name;
                            //     link.style.display = 'none';
                            //     document.body.appendChild(link);
                            //     link.click();
                            //     document.body.removeChild(link);
                            //     }
                            
                        }else{

                            showAlert(response.status,response.msg, true);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', error);
                        loader.style.display = 'none';
                    }
                    });

                }

            });

            });

            setInterval(function() {
                $('#fileupload_table').DataTable().ajax.reload(null, false);
            }, 10000);

            function showAlert(status,message, isSuccess) {
            var alertElement = $("#alertMessage");
            var messageElement = $("#successMSG");

        
            messageElement.html(message);

        
            if (status == 'success') {
                alertElement.addClass('alert-success').removeClass('alert-danger');
            } else {
                alertElement.addClass('alert-danger').removeClass('alert-success');
            }

            alertElement.fadeIn();

            setTimeout(function() {
            
                alertElement.fadeOut();
            }, 11000); 
    }
</script>
@endsection







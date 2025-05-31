
@extends('layout')

@section('content')
<div id="main" class="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle">
                <h1>Dashboard</h1>
            </div>
        </div>
    </div>

    @if(session('data.userRole') == 'user')

    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="card pt-3 text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between dashboard-card">
                        <div class="dashboard-content">
                            <div class="count">750/900</div>
                            <div class="title">Credits</div>
                        </div>
                        <i class="fa fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card pt-3 text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between dashboard-card">
                        <div class="dashboard-content">
                            <div class="count">200</div>
                            <div class="title">RC</div>
                        </div>
                        <i class="fa fa-sitemap"></i>
                    </div>
                </div>
            </div>
        </div>
       
        <div class="col-lg-3 col-md-6">
            <div class="card pt-3 text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between dashboard-card">
                        <div class="dashboard-content">
                            <div class="count">300</div>
                            <div class="title">Challan</div>
                        </div>
                        <i class="fa fa-hand-pointer-o"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card pt-3 text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between dashboard-card">
                        <div class="dashboard-content">
                            <div class="count">250</div>
                            <div class="title">Driving Licence</div>
                        </div>
                        <i class="fa fa-user"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @endif
    @if(session('data.userRole') == 'super_admin')
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="card pt-3 text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between dashboard-card">
                        <div class="dashboard-content">
                            <div class="count">05</div>
                            <div class="title">Organization</div>
                        </div>
                        <i class="fa fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card pt-3 text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between dashboard-card">
                        <div class="dashboard-content">
                            <div class="count">15</div>
                            <div class="title">API</div>
                        </div>
                        <i class="fa fa-sitemap"></i>
                    </div>
                </div>
            </div>
        </div>
       
        <div class="col-lg-3 col-md-6">
            <div class="card pt-3 text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between dashboard-card">
                        <div class="dashboard-content">
                            <div class="count">1500</div>
                            <div class="title">Hit</div>
                        </div>
                        <i class="fa fa-hand-pointer-o"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card pt-3 text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between dashboard-card">
                        <div class="dashboard-content">
                            <div class="count">20</div>
                            <div class="title">User</div>
                        </div>
                        <i class="fa fa-user"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <table class="table table-striped" id="user_table">
        <thead>
            <tr>
                <th>Sr.</th>
                <th>Org name</th>
                <th>Api</th>
                <th>Vendor</th>
                <th>Total Credits</th>
                <th>Credit utilized</th>
                <th>Status CODE</th>
            </tr>
        </thead>
    </table>

    @endif
    
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script>
        $(document).ready(function(){
    // Get the CSRF token value from the meta tag
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Add the "buttons" option for downloading
    $('#user_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('dashboard.list') }}",
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
            { data: 'api_name', name: 'api_name' },
            { data: 'vender', name: 'vender' },
            { data: 'max_count', name: 'max_count' },
            { 
                data: 'count', // New data source for count
                render: function (data) {
                    return data;
                }
            },
            { data: 'response_status_code', name: 'response_status_code' }
        ],
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print' // Add the buttons you want to enable
        ]
    });
});
    </script>
<!-- /#page-wrapper -->
    <!-- Your home page content goes here -->
    @endsection

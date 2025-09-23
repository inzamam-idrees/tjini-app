<!-- [ breadcrumb ] start -->
<div class="page-header">
    <div class="page-block">
        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="page-header-title">
                    <h5 class="m-b-10">TjiniApp</h5>
                </div>
                <ul class="breadcrumb">
                    {{-- <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript: void(0)">Dashboard</a></li>
                    <li class="breadcrumb-item" aria-current="page">Home</li> --}}
                    @yield('breadcrumb')
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- [ breadcrumb ] end -->
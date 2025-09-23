<!DOCTYPE html>
<html lang="en">
<!-- [Head] start -->

<head>
  <title>TjiniApp | {{ $title }}</title>
  <!-- [Meta] -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="description" content="TjiniApp is made using Bootstrap 5 design framework. Download the free admin template & use it for your project.">
  <meta name="keywords" content="TjiniApp, Inzamam Idrees, Inzamam, Idrees, Dashboard UI Kit, Bootstrap 5, Admin Template, Admin Dashboard, CRM, CMS, Bootstrap Admin Template">
  <meta name="author" content="InzamamIdrees">

  @include('admin.layouts.partials.head_files')

</head>
<!-- [Head] end -->
<!-- [Body] Start -->

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->



    <!-- [ Main Content ] start -->
        @yield('content')
    <!-- [ Main Content ] end -->

  

  @include('admin.layouts.partials.footer_script')
  
    

</body>
<!-- [Body] end -->

</html>
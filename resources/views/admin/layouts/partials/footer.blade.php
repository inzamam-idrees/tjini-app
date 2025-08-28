<footer class="pc-footer">
    <div class="footer-wrapper container-fluid">
        <div class="row">
            <div class="col-sm my-1">
                <p class="m-0">TjiniApp &#9829; crafted by Team <a href="https://inzamam-idrees.netlify.app" target="_blank">Inzamam Idrees</a>.</p>
            </div>
            <div class="col-auto my-1">
                <ul class="list-inline footer-link mb-0">
                    <li class="list-inline-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- [Page Specific JS] start -->
<script src="{{ asset('public/assets/js/plugins/apexcharts.min.js') }}"></script>
<script src="{{ asset('public/assets/js/pages/dashboard-default.js') }}"></script>
<!-- [Page Specific JS] end -->

@include('admin.layouts.partials.footer_script')
<!-- Required Js -->
<script src="{{ asset('public/assets/js/plugins/popper.min.js') }}"></script>
<script src="{{ asset('public/assets/js/plugins/simplebar.min.js') }}"></script>
<script src="{{ asset('public/assets/js/plugins/bootstrap.min.js') }}"></script>
<script src="{{ asset('public/assets/js/fonts/custom-font.js') }}"></script>
<script src="{{ asset('public/assets/js/pcoded.js') }}"></script>
<script src="{{ asset('public/assets/js/plugins/feather.min.js') }}"></script>
<script src="{{ asset('public/assets/js/plugins/sweetalert2.all.min.js') }}"></script>


<script>layout_change('light');</script>
<script>change_box_container('false');</script>
<script>layout_rtl_change('false');</script>
<script>preset_change("preset-1");</script>
<script>font_change("Public-Sans");</script>

@stack('scripts')

<script>
    @if (session('success'))
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: "{{ session('success') }}",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer
                toast.onmouseleave = Swal.resumeTimer
            }
        });
    @endif

    @if (session('error'))
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: "{{ session('error') }}",
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer
                toast.onmouseleave = Swal.resumeTimer
            }
        });
    @endif

    @if ($errors->any())
        @foreach ($errors->all() as $error)
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: "{{ $error }}",
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer
                    toast.onmouseleave = Swal.resumeTimer
                }
            });
        @endforeach
    @endif

    document.addEventListener('DOMContentLoaded', function () {
        // Handle delete buttons
        const deleteButtons = document.querySelectorAll('.btn-delete');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                let form = this.closest('form');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This action cannot be undone.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
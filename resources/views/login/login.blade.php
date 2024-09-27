<!-- resources/views/auth/login.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h2>Login</h2>
    <form action="{{ route('login') }}" method="POST">
        @csrf
        <input type="text" name="redirect" value="{{ request('redirect') }}">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>

<script>
    $(document).ready(function() {
        $('#loginForm').on('submit', function(event) {
            event.preventDefault(); 

            $.ajax({
                url: "{{ route('login') }}",
                method: "POST",
                data: {
                    email: $('#email').val(),
                    password: $('#password').val(),
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    // simpan token
                    const token = response.access_token;
                    localStorage.setItem('refresh_token', response.refresh_token);

                    // Kirim token ke port 8001
                    $.ajax({
                        url: "http://127.0.0.1:8001/api/verify-token", 
                        method: "POST",
                        headers: {
                            'Authorization': 'Bearer ' + token 
                        },
                        success: function(response) {
                            // Jika token valid, arahkan ke home
                            alert('Token is valid! User: ' + JSON.stringify(response));
                            window.location.href = "http://127.0.0.1:8001/home"; 
                        },
                        error: function(xhr) {
                            alert('Token is invalid!'); // ini jika gagal
                        }
                    });

                    
                },
                error: function(xhr) {
                    alert('Login failed! Please check your credentials.');
                }
            });
        });
    });
</script>

</body>
</html>

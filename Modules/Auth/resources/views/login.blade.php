<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1565C0 0%, #0D47A1 50%, #0A2472 100%); min-height: 100vh; }
        .login-card { border:none; border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,.3); animation:slideUp .4s ease; }
        .login-logo { width:80px;height:80px;background:#1565C0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem; }
        @keyframes slideUp { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card login-card p-4">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="login-logo">
                            <i class="fas fa-school fa-2x text-white"></i>
                        </div>
                        <h4 class="fw-bold text-dark">{{ config('app.name') }}</h4>
                        <p class="text-muted small">Sign in to your account</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible py-2 small">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            {{ $errors->first() }}
                            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.submit') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                                       placeholder="Enter username" value="{{ old('username') }}" required autofocus>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" name="password" id="passwordInput" class="form-control"
                                       placeholder="Enter password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePass">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-4 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label small" for="remember">Remember me</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </form>

                    <div class="mt-4 p-3 bg-light rounded small">
                        <strong>Demo Credentials:</strong><br>
                        Admin: <code>admin</code> / <code>admin123</code><br>
                        Teacher: <code>rajesh</code> / <code>teacher123</code>
                    </div>
                </div>
            </div>
            <p class="text-center text-white-50 mt-3 small">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePass').addEventListener('click', function () {
    const inp  = document.getElementById('passwordInput');
    const icon = this.querySelector('i');
    inp.type === 'password' ? (inp.type = 'text', icon.classList.replace('fa-eye','fa-eye-slash'))
                            : (inp.type = 'password', icon.classList.replace('fa-eye-slash','fa-eye'));
});
</script>
</body>
</html>

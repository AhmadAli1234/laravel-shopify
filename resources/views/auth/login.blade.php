<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm px-4">
        <h1 class="text-2xl font-semibold mb-6 text-center">Admin Login</h1>

        @if (session('status'))
            <p class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-4 py-3 mb-6">
                {{ session('status') }}
            </p>
        @endif

        @if ($errors->any())
            <div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-4 py-3 mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" autofocus
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 text-gray-600">
                    <input type="checkbox" name="remember">
                    Remember me
                </label>
                <a href="{{ route('password.request') }}" class="text-gray-500 hover:text-gray-700">Forgot password?</a>
            </div>

            <button type="submit" class="w-full bg-gray-900 text-white rounded-md px-4 py-2 text-sm font-medium hover:bg-gray-800">
                Log in
            </button>
        </form>
    </div>
</body>
</html>

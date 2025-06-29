<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="text-center text-white mb-6">
        <h1 class="text-lg font-bold">Welcome to Overtime Fitness</h1>
        <p class="mt-4 text-sm">Book your personal trainer today and reach your fitness goals with ease!</p>
    </div>

    <div class="flex items-center justify-center mt-6">
        <a href="{{ route('login.keycloak') }}">
            <img src="{{ asset('img/logo/logo-mydid.png') }}" alt="MyDigital ID" style="width: 140px; height: auto;" />
        </a>
    </div>
</x-guest-layout>

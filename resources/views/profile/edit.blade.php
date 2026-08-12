@extends('layouts.admin')

@section('page-title', 'My Profile')

@section('content')
<div class="w-full">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">My Profile</h2>
        <p class="text-sm text-gray-600 mt-1 dark:text-gray-400">Kelola informasi akun, kata sandi, dan keamanan Anda</p>
    </div>

    <div class="space-y-6 max-w-3xl">
        <div class="bg-white rounded-lg shadow-sm p-6 dark:bg-gray-800">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 dark:bg-gray-800">
            @include('profile.partials.update-password-form')
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 dark:bg-gray-800">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</div>
@endsection

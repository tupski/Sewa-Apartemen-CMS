@extends('layouts.admin')

@section('page-title', __('profile.title'))

@section('content')
{{--
    /profile is a READ-ONLY detail view of the signed-in user.

    Editing happens in the existing admin user screen
    (admin.users.edit) — this page only links there, it does not
    duplicate the form. The password + account-deletion partials
    below are unchanged Breeze functionality and stay put.
--}}
<div class="w-full">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">{{ __('profile.title') }}</h2>
            <p class="text-sm text-gray-600 mt-1 dark:text-gray-400">{{ __('profile.subtitle') }}</p>
        </div>
        @if($user->isAdmin())
            <a href="{{ route('admin.users.edit', $user) }}"
               data-testid="profile-edit-link"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                {{ __('profile.edit_profile') }}
            </a>
        @endif
    </div>

    <div class="space-y-6 max-w-3xl">
        {{-- Identity card --}}
        <div class="bg-white rounded-lg shadow-sm p-6 dark:bg-gray-800">
            <div class="flex items-center gap-4">
                @if($user->avatar)
                    <img src="{{ $user->avatarUrl(160) }}" alt="{{ $user->name }}"
                         class="w-20 h-20 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                @else
                    <span class="w-20 h-20 rounded-full bg-blue-600 text-white text-2xl font-semibold inline-flex items-center justify-center select-none"
                          aria-hidden="true">{{ $user->initials() }}</span>
                @endif
                <div class="min-w-0">
                    <p class="text-lg font-semibold text-gray-800 truncate dark:text-white">{{ $user->name }}</p>
                    <p class="text-sm text-gray-500 truncate dark:text-gray-400">{{ $user->email }}</p>
                    <span class="mt-1 inline-block px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">
                        {{ $user->roles->first()->name ?? __('admin.no_role') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Details --}}
        <div class="bg-white rounded-lg shadow-sm p-6 dark:bg-gray-800">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('profile.account_details') }}</h3>

            <dl class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('profile.name') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">{{ $user->name }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('profile.email') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">{{ $user->email }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('profile.phone') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">{{ $user->phone ?: '—' }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('profile.role') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">{{ $user->roles->first()->name ?? __('admin.no_role') }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('profile.email_verified') }}</dt>
                    <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                        @if($user->email_verified_at)
                            <span class="text-green-700 dark:text-green-400">{{ __('profile.verified') }} — {{ $user->email_verified_at->format('d M Y') }}</span>
                        @else
                            <span class="text-yellow-700 dark:text-yellow-400">{{ __('profile.not_verified') }}</span>
                        @endif
                    </dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('profile.member_since') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">{{ $user->created_at?->format('d M Y') ?? '—' }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('profile.last_updated') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 dark:text-gray-100">{{ $user->updated_at?->diffForHumans() ?? '—' }}</dd>
                </div>
            </dl>
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

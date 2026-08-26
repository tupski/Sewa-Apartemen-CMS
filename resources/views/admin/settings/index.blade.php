@extends('layouts.admin')

@section('page-title', 'CMS Settings')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="flex flex-col lg:flex-row">
            <!-- Sidebar Navigation -->
            <aside class="w-full lg:w-64 lg:shrink-0 border-b lg:border-b-0 lg:border-r border-gray-200 bg-gray-50">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Settings
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">Configure your CMS</p>
                </div>

                <nav class="py-4" role="navigation" aria-label="Settings groups">
                    <ul class="space-y-1 px-3">
                        @php
                            $groups = [
                                'general'      => ['icon' => 'cog', 'label' => 'General'],
                                'homepage'     => ['icon' => 'house', 'label' => 'Homepage'],
                                'footer'       => ['icon' => 'table-list', 'label' => 'Footer'],
                                'theme'        => ['icon' => 'palette', 'label' => 'Appearance'],
                                'seo'          => ['icon' => 'magnifying-glass', 'label' => 'SEO'],
                                'integrations' => ['icon' => 'plug', 'label' => 'Integrations'],
                                'pricing'      => ['icon' => 'tags', 'label' => 'Pricing & Booking'],
                                'mail'         => ['icon' => 'envelope', 'label' => 'Mail / Email'],
                                'version_control' => ['icon' => 'code-branch', 'label' => 'Version Control'],
                                'email_templates' => ['icon' => 'file-lines', 'label' => 'Email Templates'],
                                'captcha'      => ['icon' => 'shield-halved', 'label' => 'Security (CAPTCHA)'],
                                'currency_api' => ['icon' => 'arrows-left-right', 'label' => 'Currency API'],
                            ];
                            $activeGroup = $group ?? request()->query('group', 'general');
                        @endphp

                        @foreach($groups as $slug => $info)
                            <li>
                                <a href="{{ route('admin.settings.index', ['group' => $slug]) }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                                          {{ $activeGroup === $slug
                                              ? 'bg-blue-50 text-blue-700'
                                              : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
                                   @if($activeGroup === $slug)
                                       aria-current="page"
                                   @endif>
                                    <i class="fa-solid fa-{{ $info['icon'] }} w-5 text-center"></i>
                                    <span>{{ $info['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </aside>

            <!-- Main Content Area -->
            <main class="flex-1 min-w-0">
                <div class="p-6 lg:p-8">
                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center justify-between">
                            <span>{{ session('success') }}</span>
                            <button type="button" onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center justify-between">
                            <span>{{ session('error') }}</span>
                            <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @endif

                    @php
                        $formGroups = [
                            'general'        => '_general',
                            'homepage'       => '_homepage',
                            'footer'         => '_footer',
                            'theme'          => '_theme',
                            'seo'            => '_seo',
                            'integrations'   => '_integrations',
                            'pricing'        => '_pricing',
                            'mail'           => '_mail',
                            'email_templates'=> '_email_templates',
                            'captcha'        => '_captcha',
                            'currency_api'   => '_currency_api',
                        ];
                        // Groups that render a standalone partial (no form wrapper)
                        $standaloneGroups = ['version_control'];
                    @endphp

                    @if($group === 'version_control')
                        @include('admin.settings.partials._git')
                    @elseif(isset($formGroups[$group]))
                        <form method="POST"
                              action="{{ route('admin.settings.update', $group) }}"
                              @if($group === 'general') enctype="multipart/form-data" @endif
                              class="space-y-6"
                              data-warn-unsaved>
                            @csrf
                            @include('admin.settings.partials.' . $formGroups[$group], ['settings' => $settings, 'group' => $group])
                        </form>
                    @else
                        {{-- Unknown group fallback --}}
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Unknown settings group</h3>
                            <p class="mt-1 text-sm text-gray-500">The requested settings group "{{ $group }}" does not exist.</p>
                            <a href="{{ route('admin.settings.index', ['group' => 'general']) }}"
                               class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                Go to General Settings
                            </a>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

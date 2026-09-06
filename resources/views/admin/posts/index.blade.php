@extends('layouts.admin')

@section('page-title', 'Posts')

@section('content')
<div class="w-full">
    <x-admin-page-header
        title="Posts"
        description="Manage your blog posts"
        :create-route="route('admin.posts.create')"
        create-label="Create New Post" />

    <x-admin-filter-bar
        :action="route('admin.posts.index')"
        :reset-route="request()->hasAny(['search', 'status', 'category_id']) ? route('admin.posts.index') : null">
        <div class="min-w-[12rem] flex-1">
            <input type="text" name="search" placeholder="Search by title..."
                   value="{{ request('search') }}"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div class="w-full md:w-48">
            <select name="status" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
            </select>
        </div>
        <div class="w-full md:w-48">
            <select name="category_id" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2 font-medium text-white transition hover:bg-blue-700">Filter</button>
    </x-admin-filter-bar>

    <div class="mt-6 overflow-hidden rounded-lg bg-white shadow-sm">
        @if($posts->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Published</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($posts as $post)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <a href="{{ route('admin.posts.edit', $post) }}" class="text-sm font-medium text-blue-600 hover:text-blue-900">
                                        {{ $post->title }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ $post->category->name ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <x-admin-status-badge :status="$post->status" />
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ $post->published_at ? $post->published_at->format('M d, Y') : '-' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.posts.edit', $post) }}" class="text-blue-600 hover:text-blue-900" title="Edit" aria-label="Edit {{ $post->title }}">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                        <form id="post-delete-{{ $post->id }}" action="{{ route('admin.posts.destroy', $post) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="button"
                                                    class="text-red-600 hover:text-red-900"
                                                    title="Delete"
                                                    aria-label="Delete {{ $post->title }}"
                                                    @click="$dispatch('open-confirm', { id: 'post-delete-modal-{{ $post->id }}', trigger: $el })">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $posts->links() }}
            </div>
        @else
            <x-admin-empty-state
                title="No posts found"
                description="Get started by creating a new post."
                :action-route="route('admin.posts.create')"
                action-label="Buat Post Pertama"
                icon="fa-solid fa-newspaper" />
        @endif
    </div>

    @foreach($posts as $post)
        <x-confirm-modal
            id="post-delete-modal-{{ $post->id }}"
            title="Delete post?"
            :message="'Delete ' . $post->title . '? This action cannot be undone.'"
            confirm-form-id="post-delete-{{ $post->id }}" />
    @endforeach
</div>
@endsection

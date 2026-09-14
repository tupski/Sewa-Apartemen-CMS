<?php

namespace App\Support;

/**
 * The admin navigation, mirroring the legacy sidebar (see docs/ADMIN.md).
 * Every entry points at an existing route; groups reuse the legacy IA.
 */
class AdminMenu
{
    /**
     * @return array<int, array{group: string, items: array<int, array{label: string, route: string, match: string, icon: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'group' => '',
                'items' => [
                    ['label' => __('admin.dashboard'), 'route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'fa-solid fa-gauge-high'],
                ],
            ],
            [
                'group' => __('admin.group_content'),
                'items' => [
                    ['label' => __('admin.pages'), 'route' => 'admin.pages.index', 'match' => 'admin.pages.*', 'icon' => 'fa-regular fa-file-lines'],
                    ['label' => __('admin.blocks'), 'route' => 'admin.blocks.index', 'match' => 'admin.blocks.*', 'icon' => 'fa-solid fa-cubes'],
                    ['label' => __('admin.media'), 'route' => 'admin.media.index', 'match' => 'admin.media.*', 'icon' => 'fa-regular fa-images'],
                    ['label' => __('admin.navigation'), 'route' => 'admin.navigations.index', 'match' => 'admin.navigations.*', 'icon' => 'fa-solid fa-bars-staggered'],
                ],
            ],
            [
                'group' => __('admin.group_properties'),
                'items' => [
                    ['label' => __('admin.properties'), 'route' => 'admin.properties.index', 'match' => 'admin.properties.*', 'icon' => 'fa-solid fa-building'],
                    ['label' => __('admin.amenities'), 'route' => 'admin.amenities.index', 'match' => 'admin.amenities.*', 'icon' => 'fa-solid fa-spa'],
                ],
            ],
            [
                'group' => __('admin.group_blog'),
                'items' => [
                    ['label' => __('admin.posts'), 'route' => 'admin.posts.index', 'match' => 'admin.posts.*', 'icon' => 'fa-regular fa-newspaper'],
                    ['label' => __('admin.categories'), 'route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'icon' => 'fa-solid fa-folder-tree'],
                    ['label' => __('admin.tags'), 'route' => 'admin.tags.index', 'match' => 'admin.tags.*', 'icon' => 'fa-solid fa-tags'],
                ],
            ],
            [
                'group' => __('admin.group_booking'),
                'items' => [
                    ['label' => __('admin.bookings'), 'route' => 'admin.bookings.index', 'match' => 'admin.bookings.*', 'icon' => 'fa-regular fa-calendar-check'],
                    ['label' => __('admin.vouchers'), 'route' => 'admin.vouchers.index', 'match' => 'admin.vouchers.*', 'icon' => 'fa-solid fa-ticket'],
                ],
            ],
            [
                'group' => __('admin.group_system'),
                'items' => [
                    ['label' => __('admin.languages'), 'route' => 'admin.languages.index', 'match' => 'admin.languages.*', 'icon' => 'fa-solid fa-language'],
                    ['label' => __('admin.currency'), 'route' => 'admin.currency-rates.index', 'match' => 'admin.currency-rates.*', 'icon' => 'fa-solid fa-money-bill-transfer'],
                    ['label' => __('admin.users'), 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'fa-solid fa-users'],
                    ['label' => __('admin.slug_settings'), 'route' => 'admin.slug-settings.index', 'match' => 'admin.slug-settings.*', 'icon' => 'fa-solid fa-link'],
                    ['label' => __('admin.redirects'), 'route' => 'admin.redirects.index', 'match' => 'admin.redirects.*', 'icon' => 'fa-solid fa-arrow-right-arrow-left'],
                    ['label' => __('admin.backup'), 'route' => 'admin.backup.index', 'match' => 'admin.backup.*', 'icon' => 'fa-solid fa-database'],
                    ['label' => __('admin.settings'), 'route' => 'admin.settings.index', 'match' => 'admin.settings.*', 'icon' => 'fa-solid fa-gear'],
                ],
            ],
        ];
    }
}

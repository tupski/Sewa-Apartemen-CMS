<?php

// Blog conversion settings.
//
// 'location_tag_to_city' maps location tag slugs (post_tag) to the
// properties.city values they should match for the article property CTA
// and the tag archive property block.
//
// Values were verified against database/seeders/PropertySeeder.php because
// the local MySQL server was not reachable for a live DISTINCT city query:
//   Tangerang Selatan, Kota Tangerang, Tangerang, Jakarta Pusat, Bekasi.
// Re-verify against production before deploying
// (SELECT DISTINCT city FROM properties;).
return [

    'location_tag_to_city' => [
        'bsd-city' => ['Tangerang Selatan'],
        'bintaro' => ['Tangerang Selatan'],
        'tangerang' => ['Tangerang', 'Tangerang Selatan', 'Kota Tangerang'],
        'jakarta' => ['Jakarta', 'Jakarta Pusat'],
        'bekasi' => ['Bekasi'],
        'pik-2' => ['Tangerang'],
    ],

    'sidebar_properties_limit' => 3,
    'article_cta_limit' => 3,
    'tag_properties_limit' => 3,

    // Pillar → cluster architecture (Fase 5): max cluster articles rendered
    // in the "in this guide" list on the pillar article page.
    'cluster_posts_limit' => 8,
];

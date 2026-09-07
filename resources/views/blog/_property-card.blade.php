{{--
    Blog property card.

    Thin wrapper around the shared listing card
    (resources/views/properties/_card.blade.php) so every blog surface
    (article CTA, sidebar) renders the exact same visual language, price
    display and CTA as the property listing page — one card to maintain.

    Variables:
      - $property (required) : App\Models\Property instance. Eager load
        featuredImage, photos.media and amenities before passing it in
        (see BlogPropertyService::CARD_WITH) — the shared card renders
        all three.
--}}
@include('properties._card', ['property' => $property])

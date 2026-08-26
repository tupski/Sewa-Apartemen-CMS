<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'code', 'name', 'native_name', 'flag_emoji', 'flag_code',
        'is_active', 'is_default', 'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Map of common language codes (ISO 639-1) to emoji flags.
     * Used as a reliable fallback so every language always has a flag,
     * even when no flag_emoji / flag_code was stored.
     *
     * @var array<string, string>
     */
    protected static array $flagMap = [
        'id' => '🇮🇩',
        'en' => '🇬🇧',
        'us' => '🇺🇸',
        'zh' => '🇨🇳',
        'ja' => '🇯🇵',
        'ko' => '🇰🇷',
        'ar' => '🇸🇦',
        'fr' => '🇫🇷',
        'de' => '🇩🇪',
        'es' => '🇪🇸',
        'it' => '🇮🇹',
        'pt' => '🇵🇹',
        'pt-br' => '🇧🇷',
        'ru' => '🇷🇺',
        'hi' => '🇮🇳',
        'th' => '🇹🇭',
        'vi' => '🇻🇳',
        'ms' => '🇲🇾',
        'nl' => '🇳🇱',
        'tr' => '🇹🇷',
        'pl' => '🇵🇱',
        'sv' => '🇸🇪',
        'no' => '🇳🇴',
        'da' => '🇩🇰',
        'fi' => '🇫🇮',
        'cs' => '🇨🇿',
        'sk' => '🇸🇰',
        'el' => '🇬🇷',
        'he' => '🇮🇱',
        'uk' => '🇺🇦',
        'ro' => '🇷🇴',
        'hu' => '🇭🇺',
        'bg' => '🇧🇬',
        'hr' => '🇭🇷',
        'sr' => '🇷🇸',
        'fa' => '🇮🇷',
        'bn' => '🇧🇩',
        'ur' => '🇵🇰',
        'ta' => '🇮🇳',
        'te' => '🇮🇳',
        'sw' => '🇰🇪',
        'tl' => '🇵🇭',
    ];

    /**
     * Reliable display flag:
     * stored flag_emoji → mapped by language code → derived from the stored
     * ISO country code → derived from the language code → null.
     *
     * @return string|null
     */
    public function getFlagAttribute(): ?string
    {
        if ($this->flag_emoji) {
            return $this->flag_emoji;
        }

        $code = strtolower(trim($this->code ?? ''));

        if (isset(self::$flagMap[$code])) {
            return self::$flagMap[$code];
        }

        if ($this->flag_code) {
            return self::countryCodeToEmoji($this->flag_code);
        }

        // Last resort: use the first 2 letters of the language code.
        if (strlen($code) >= 2) {
            return self::countryCodeToEmoji(substr($code, 0, 2));
        }

        return null;
    }

    /**
     * Convert an ISO 3166-1 alpha-2 country code into a flag emoji
     * (regional indicator symbols). Works for any valid country code and
     * requires no image assets, no extra CSS library, and no mbstring.
     *
     * @param string $countryCode
     * @return string|null
     */
    public static function countryCodeToEmoji(string $countryCode): ?string
    {
        $countryCode = strtoupper(trim($countryCode));

        if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
            return null;
        }

        $flag = '';
        foreach (str_split($countryCode) as $letter) {
            $codepoint = 0x1F1E6 + (ord($letter) - ord('A'));
            $flag .= html_entity_decode('&#x' . dechex($codepoint) . ';', ENT_NOQUOTES, 'UTF-8');
        }

        return $flag;
    }

    public static function active(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)->orderBy('sort_order')->get();
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->first()
            ?? static::where('is_active', true)->orderBy('sort_order')->first();
    }
}

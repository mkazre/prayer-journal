<?php

use App\Modules\Core\Models\AppSettingModel;

/*
 * View helpers shared by every page: icons, money, settings, user
 * preferences, avatars and small formatting utilities.
 */

if (! function_exists('icon')) {
    /**
     * Inline reference to a symbol in the icon sprite (public/assets/img/icons.svg).
     */
    function icon(string $name, string $class = ''): string
    {
        // Root-relative so <use> never crosses origins (www vs bare domain, proxies).
        static $sprite = null;
        $sprite ??= rtrim((string) parse_url(base_url(), PHP_URL_PATH), '/') . '/assets/img/icons.svg?v=' . @filemtime(FCPATH . 'assets/img/icons.svg');
        $href = $sprite . '#' . $name;

        return '<svg class="icon ' . esc($class, 'attr') . '" aria-hidden="true" focusable="false"><use href="' . esc($href, 'attr') . '"></use></svg>';
    }
}

if (! function_exists('icon_sprite_url')) {
    function icon_sprite_url(): string
    {
        return rtrim((string) parse_url(base_url(), PHP_URL_PATH), '/') . '/assets/img/icons.svg?v=' . @filemtime(FCPATH . 'assets/img/icons.svg');
    }
}

if (! function_exists('site_setting')) {
    /**
     * Reads a value from app_settings, cached for the request.
     */
    function site_setting(string $key, ?string $default = null): ?string
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            try {
                foreach ((new AppSettingModel())->findAll() as $row) {
                    $cache[$row['setting_key']] = $row['setting_value'];
                }
            } catch (\Throwable $e) {
                $cache = [];
            }
        }
        if ($key === '__flush__') {
            $cache = null;

            return null;
        }
        $value = $cache[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }
}

if (! function_exists('site_name')) {
    function site_name(): string
    {
        return site_setting('site_name', 'Prayer Journal');
    }
}

if (! function_exists('logo_url')) {
    function logo_url(): string
    {
        $custom = site_setting('logo_path');

        return $custom ? base_url($custom) : base_url('assets/img/logo-mark.png');
    }
}

if (! function_exists('store_currency')) {
    function store_currency(): string
    {
        return strtoupper(site_setting('currency', 'ZAR'));
    }
}

if (! function_exists('money')) {
    /**
     * Formats integer cents as a price, e.g. 15000 -> "R150.00".
     */
    function money(?int $cents, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?: store_currency());
        $symbols  = ['ZAR' => 'R', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'NGN' => '₦', 'KES' => 'KSh ', 'CAD' => 'CA$', 'AUD' => 'A$'];
        $symbol   = $symbols[$currency] ?? ($currency . ' ');
        $amount   = number_format(((int) $cents) / 100, 2, '.', ',');

        return $symbol . $amount;
    }
}

if (! function_exists('price_label')) {
    function price_label(int $cents, ?string $currency = null): string
    {
        return $cents > 0 ? money($cents, $currency) : 'Free';
    }
}

if (! function_exists('user_profile')) {
    /**
     * Profile row (display name, avatar, preferences) for a user, cached.
     */
    function user_profile(?int $userId = null, bool $fresh = false): array
    {
        static $cache = [];
        $userId ??= auth()->loggedIn() ? (int) auth()->id() : 0;
        if ($userId === 0) {
            return [];
        }
        if ($fresh || ! array_key_exists($userId, $cache)) {
            $row = db_connect()->table('user_profiles')->where('user_id', $userId)->get()->getRowArray();
            $cache[$userId] = $row ?: [];
        }

        return $cache[$userId];
    }
}

if (! function_exists('user_prefs')) {
    /**
     * Display preferences with defaults applied.
     *
     * @return array{theme:string,text:string,reading:string,bible_layout:string,verse_numbers:bool,journal_reminders:bool}
     */
    function user_prefs(?int $userId = null, bool $fresh = false): array
    {
        $defaults = [
            'theme'             => 'system',
            'text'              => 'md',
            'reading'           => 'serif',
            'bible_layout'      => 'paragraph',
            'verse_numbers'     => true,
            'journal_reminders' => true,
            'email_courses'     => true,
            'email_community'   => true,
            'email_reminders'   => true,
        ];
        $profile = user_profile($userId, $fresh);
        $prefs   = json_decode($profile['preferences'] ?? '', true);

        return array_merge($defaults, is_array($prefs) ? $prefs : []);
    }
}

if (! function_exists('display_name')) {
    /**
     * @param object|array|null $user
     */
    function display_name($user = null): string
    {
        if ($user === null && auth()->loggedIn()) {
            $user = auth()->user();
        }
        if ($user === null) {
            return 'Guest';
        }
        $id       = is_array($user) ? ($user['id'] ?? $user['user_id'] ?? 0) : ($user->id ?? 0);
        $username = is_array($user) ? ($user['username'] ?? '') : ($user->username ?? '');
        $profile  = $id ? user_profile((int) $id) : [];

        return trim((string) ($profile['display_name'] ?? '')) ?: ($username ?: 'Friend');
    }
}

if (! function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/[\s._-]+/', trim($name)) ?: [];
        $out   = '';
        foreach (array_slice(array_filter($parts), 0, 2) as $p) {
            $out .= mb_strtoupper(mb_substr($p, 0, 1));
        }

        return $out !== '' ? $out : '?';
    }
}

if (! function_exists('avatar')) {
    function avatar(?int $userId, string $name, string $class = ''): string
    {
        $profile = $userId ? user_profile($userId) : [];
        $inner   = ! empty($profile['avatar_path'])
            ? '<img src="' . esc(base_url($profile['avatar_path']), 'attr') . '" alt="">'
            : esc(initials($name));

        return '<span class="avatar ' . esc($class, 'attr') . '" title="' . esc($name, 'attr') . '">' . $inner . '</span>';
    }
}

if (! function_exists('time_ago')) {
    function time_ago(?string $datetime): string
    {
        if (! $datetime) {
            return '';
        }
        $diff = time() - strtotime($datetime);
        if ($diff < 60) {
            return 'just now';
        }
        $units = [31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute'];
        foreach ($units as $secs => $name) {
            if ($diff >= $secs) {
                $n = (int) floor($diff / $secs);

                return $n . ' ' . $name . ($n > 1 ? 's' : '') . ' ago';
            }
        }

        return '';
    }
}

if (! function_exists('nice_date')) {
    function nice_date(?string $datetime, string $format = 'j M Y'): string
    {
        return $datetime ? date($format, strtotime($datetime)) : '';
    }
}

if (! function_exists('excerpt')) {
    function excerpt(?string $html, int $length = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES)));

        return mb_strlen($text) > $length ? rtrim(mb_substr($text, 0, $length - 1)) . '…' : $text;
    }
}

if (! function_exists('reading_minutes')) {
    function reading_minutes(?string $html): int
    {
        $words = str_word_count(strip_tags((string) $html));

        return max(1, (int) ceil($words / 200));
    }
}

if (! function_exists('stars')) {
    function stars(?float $rating, string $class = ''): string
    {
        $rating = (float) $rating;
        $out    = '<span class="stars ' . esc($class, 'attr') . '" aria-label="' . esc(number_format($rating, 1), 'attr') . ' out of 5">';
        for ($i = 1; $i <= 5; $i++) {
            $out .= icon('star', 'icon-fill' . ($rating + 0.25 >= $i ? '' : ' off'));
        }

        return $out . '</span>';
    }
}

if (! function_exists('lesson_icon')) {
    function lesson_icon(string $type): string
    {
        return [
            'video'      => 'play-circle',
            'audio'      => 'headphones',
            'pdf'        => 'file',
            'embed'      => 'monitor',
            'devotion'   => 'feather',
            'quiz'       => 'list-checks',
            'assignment' => 'pen',
        ][$type] ?? 'file-text';
    }
}

if (! function_exists('lesson_type_label')) {
    function lesson_type_label(string $type): string
    {
        return [
            'text'       => 'Reading',
            'video'      => 'Video',
            'audio'      => 'Audio',
            'pdf'        => 'Document',
            'embed'      => 'Interactive',
            'devotion'   => 'Devotion',
            'quiz'       => 'Quiz',
            'assignment' => 'Assignment',
        ][$type] ?? ucfirst($type);
    }
}

if (! function_exists('video_embed_url')) {
    /**
     * Converts a YouTube/Vimeo watch URL into an embeddable URL. Returns
     * null for anything else (direct media files are played natively).
     */
    function video_embed_url(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&modestbranding=1';
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return null;
    }
}

if (! function_exists('cover_gradient')) {
    /**
     * Deterministic pair of brand colours for a cover with no image.
     */
    function cover_gradient(string $seed): string
    {
        $palettes = [
            ['#2e1052', '#6a35b0'], ['#1a2a4f', '#4b1e82'], ['#3a1240', '#9a3b6b'], ['#14352f', '#2f7a63'],
            ['#40210b', '#a8741a'], ['#1f1b4d', '#5b4bc4'], ['#2b0f3a', '#c9a227'], ['#0f2a3f', '#2f6f9a'],
        ];
        $p = $palettes[abs(crc32($seed)) % count($palettes)];

        return '--c1:' . $p[0] . ';--c2:' . $p[1];
    }
}

if (! function_exists('course_cover_style')) {
    /**
     * Cover colours from the course's appearance settings (falls back to the brand).
     */
    function course_cover_style(array $course): string
    {
        $settings = json_decode((string) ($course['settings'] ?? ''), true) ?: [];
        $brand    = preg_match('/^#[0-9a-f]{6}$/i', (string) ($settings['brand_color'] ?? '')) ? $settings['brand_color'] : '#4b1e82';
        $accent   = preg_match('/^#[0-9a-f]{6}$/i', (string) ($settings['accent_color'] ?? '')) ? $settings['accent_color'] : '#c9a227';

        return '--c1:#1a0d2e;--c2:' . $brand . ';--c3:' . $accent;
    }
}

if (! function_exists('cart_count')) {
    function cart_count(): int
    {
        try {
            return (new \App\Modules\Shop\Models\CartModel())->currentCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}

if (! function_exists('is_staff')) {
    function is_staff(string ...$groups): bool
    {
        if (! auth()->loggedIn()) {
            return false;
        }
        $groups = $groups ?: ['superadmin', 'admin', 'manager', 'instructor', 'vendor'];

        return auth()->user()->inGroup(...$groups);
    }
}

if (! function_exists('nav_active')) {
    function nav_active(string $prefix): string
    {
        $path = trim(uri_string(), '/');
        $prefix = trim($prefix, '/');

        return ($prefix === '' ? $path === '' : ($path === $prefix || str_starts_with($path, $prefix . '/'))) ? ' is-active' : '';
    }
}

if (! function_exists('scripture_links')) {
    /**
     * Wraps Bible references in rich text with links to the reader that
     * show a verse popover on hover.
     */
    function scripture_links(?string $html): string
    {
        return \App\Libraries\Scripture::linkify((string) $html);
    }
}

if (! function_exists('clean_html')) {
    function clean_html(?string $html): string
    {
        return \App\Libraries\HtmlSanitizer::clean((string) $html);
    }
}

if (! function_exists('json_attr')) {
    function json_attr($value): string
    {
        return esc(json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'attr');
    }
}

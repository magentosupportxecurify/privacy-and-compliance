<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Model;

use Magento\Framework\App\Area;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use MiniOrange\CookieConsent\Helper\Data as PrivacyHelper;

class ScriptBlocker
{
    private const BLOCKER_MARKER = '/*mo-consent-blocker*/';

    /**
     * Built-in tracker substrings only (merchant-classified scripts in discovered_map take priority).
     *
     * @var array<string, string>
     */
    private const BUILTIN_TRACKER_PATTERNS = [
        'gtag.js' => 'analytics',
        'analytics.js' => 'analytics',
        'googletagmanager.com' => 'analytics',
        'hotjar.com' => 'analytics',
        'clarity.ms' => 'analytics',
        'segment.com' => 'analytics',
        'mixpanel.com' => 'analytics',
        'heap.io' => 'analytics',
        'fbevents.js' => 'marketing',
        'connect.facebook.net' => 'marketing',
        'linkedin.com/insight' => 'marketing',
        'doubleclick.net' => 'marketing',
        'googlesyndication' => 'marketing',
        'intercom.io' => 'functional',
        'crisp.chat' => 'functional',
        'zendesk.com' => 'functional',
        'tidio.com' => 'functional',
    ];

    public function __construct(
        private readonly PrivacyHelper $privacyHelper,
        private readonly AssetRepository $assetRepository
    ) {
    }

    /**
     * @return list<array{pattern: string, category: string}>
     */
    public static function getBuiltinTrackerPatternsForDisplay(): array
    {
        $rows = [];
        foreach (self::BUILTIN_TRACKER_PATTERNS as $pattern => $category) {
            $rows[] = [
                'pattern' => $pattern,
                'category' => ucfirst((string) $category),
            ];
        }

        return $rows;
    }

    public function processHtml(string $html, int $storeId): string
    {
        if (str_contains($html, self::BLOCKER_MARKER)) {
            return $html;
        }

        $discovered = $this->privacyHelper->getDiscoveredMap($storeId);
        $fallbackPatterns = $this->getMergedFallbackPatterns($storeId);

        $html = (string) preg_replace_callback(
            '/<script\b([^>]*)>(.*?)<\/script>/is',
            function (array $matches) use ($discovered, $fallbackPatterns): string {
                return $this->rewriteScriptTag($matches, $discovered, $fallbackPatterns);
            },
            $html
        );

        $content = $this->getConsentBlockerJsContent();
        $scriptTag = '<script type="text/javascript">' . self::BLOCKER_MARKER . $content . '</script>';
        $html = (string) preg_replace('/<head([^>]*)>/i', '<head$1>' . $scriptTag, $html, 1);

        return $html;
    }

    /**
     * Built-in trackers plus custom textarea patterns (textarea overrides builtins for same needle).
     *
     * @return array<string, string> lowercase needle => category
     */
    private function getMergedFallbackPatterns(int $storeId): array
    {
        $map = [];
        foreach (self::BUILTIN_TRACKER_PATTERNS as $needle => $category) {
            $map[strtolower($needle)] = $category;
        }
        foreach ($this->parseCustomPatterns($this->privacyHelper->getCustomPatternsRaw($storeId)) as $needle => $category) {
            $map[$needle] = $category;
        }

        return $map;
    }

    /**
     * @return array<string, string>
     */
    private function parseCustomPatterns(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, '=')) {
                continue;
            }
            [$pattern, $category] = array_map('trim', explode('=', $line, 2));
            $pattern = strtolower($pattern);
            $category = strtolower($category);
            if ($pattern === '' || !in_array($category, ['analytics', 'marketing', 'functional', 'necessary'], true)) {
                continue;
            }
            $out[$pattern] = $category;
        }

        return $out;
    }

    private function getConsentBlockerJsContent(): string
    {
        $asset = $this->assetRepository->createAsset(
            'MiniOrange_CookieConsent::js/consent-blocker.js',
            ['area' => Area::AREA_FRONTEND]
        );
        $path = $asset->getSourceFile();

        return ($path !== null && is_readable($path)) ? (string) file_get_contents($path) : '';
    }

    /**
     * @param array<int, string> $matches
     * @param array<string, string> $discovered lowercase URL => category
     * @param array<string, string> $fallbackPatterns
     */
    private function rewriteScriptTag(array $matches, array $discovered, array $fallbackPatterns): string
    {
        $attrs = $matches[1];
        $content = $matches[2];

        if (preg_match('/\btype=["\']text\/plain["\']/', $attrs)) {
            return $matches[0];
        }
        if (preg_match('/\btype=["\']text\/x-magento-[^"\']*["\']/', $attrs)) {
            return $matches[0];
        }
        $category = $this->classifyWithDiscoverFirst($attrs . $content, $discovered, $fallbackPatterns);

        if ($category === null || $category === 'necessary') {
            return $matches[0];
        }

        $newAttrs = (string) preg_replace('/\bsrc=(["\'])([^"\']+)\1/', 'data-src=$1$2$1', $attrs);
        $newAttrs = (string) preg_replace('/\btype=(["\'])[^"\']*\1/', '', $newAttrs);

        $safeCategory = htmlspecialchars($category, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<script type="text/plain" data-consent-category="' . $safeCategory . '"'
            . $newAttrs . '>' . $content . '</script>';
    }

    /**
     * @param array<string, string> $discovered
     * @param array<string, string> $fallbackPatterns
     */
    private function classifyWithDiscoverFirst(string $haystack, array $discovered, array $fallbackPatterns): ?string
    {
        $fromDiscover = $this->classifyLongestNeedleMatch($haystack, $discovered);
        if ($fromDiscover !== null) {
            return $fromDiscover;
        }

        return $this->classifyFirstMatch($haystack, $fallbackPatterns);
    }

    /**
     * Longest needle first so more specific discovered URLs win.
     *
     * @param array<string, string> $needleToCategory
     */
    private function classifyLongestNeedleMatch(string $haystack, array $needleToCategory): ?string
    {
        if ($needleToCategory === []) {
            return null;
        }
        $lower = strtolower($haystack);
        $needles = array_keys($needleToCategory);
        usort($needles, static fn ($a, $b) => strlen((string) $b) <=> strlen((string) $a));
        foreach ($needles as $needle) {
            $n = strtolower((string) $needle);
            if ($n !== '' && str_contains($lower, $n)) {
                return $this->normalizeCategory($needleToCategory[$needle]);
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $patterns
     */
    private function classifyFirstMatch(string $haystack, array $patterns): ?string
    {
        $lower = strtolower($haystack);
        foreach ($patterns as $needle => $category) {
            if (str_contains($lower, $needle)) {
                return $this->normalizeCategory($category);
            }
        }

        return null;
    }

    private function normalizeCategory(mixed $category): string
    {
        if (!is_string($category) && !is_numeric($category)) {
            return 'necessary';
        }
        $c = strtolower(trim((string) $category));

        return in_array($c, ['necessary', 'analytics', 'marketing', 'functional'], true) ? $c : 'necessary';
    }
}

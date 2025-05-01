<?php

namespace App\Utils;

class TextAnalyzer
{
    private static array $keywords = [
        // Functional terms
        "app", "platform", "system", "interface", "usability", "performance", "dashboard", "navigation", "design", "account", "authentication", "login", "update", "version",

        // Feedback context
        "feedback", "review", "comment", "opinion", "support", "team", "help", "contact",

        // Travel-specific
        "trip", "hotel", "stay", "vacation", "guide", "flight", "itinerary", "tour", "explore", "destination", "booking", "reservation", "cancel", "refund", "accommodation", "recommend", "transport", "eco-friendly", "sustainable",

        // Positive experience
        "amazing", "excellent", "great", "useful", "friendly", "modern", "stable", "reliable", "helpful", "clean", "intuitive", "easy", "seamless", "efficient", "secure", "impressive",

        // Negative experience
        "bad", "bug", "error", "issue", "crash", "laggy", "slow", "unresponsive", "confusing", "frustrating", "hard", "complicated", "inconvenient", "unusable", "doesn't work"
    ];

    public static function isMeaningful(?string $text): bool
    {
        if (!$text || trim($text) === '') {
            return false;
        }

        $cleanText = preg_replace('/[^\p{L}\p{N}\s]/u', '', strtolower($text));
        $words = preg_split('/\s+/', $cleanText);
        $matchCount = 0;

        foreach ($words as $word) {
            if (strlen($word) < 3) {
                continue;
            }

            if (in_array($word, self::$keywords, true)) {
                $matchCount++;
            }
        }

        $isGibberish = preg_match('/^(.)\1{2,}$|^[a-z]{4,}$/i', $cleanText);
        if ($isGibberish) {
            return false;
        }

        return $matchCount >= 2;
    }
}

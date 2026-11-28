<?php

declare(strict_types=1);

namespace App\Services\IM;

use function html_entity_decode;
use function preg_match_all;
use function preg_replace;
use function str_replace;
use function strlen;
use function substr;
use function trim;
use const ENT_HTML5;
use const ENT_QUOTES;
use const PREG_OFFSET_CAPTURE;

abstract class Base
{
    private const KNOWN_HTML_TAGS_PATTERN = '/<\/?(?:b|strong|i|em|u|ins|s|strike|del|span|tg-spoiler|tg-emoji|code|pre|blockquote|p|div|table|thead|tbody|tr|th|td|ul|ol|li|h[1-6])\b[^>]*>/i';
    private const ANCHOR_TAG_PATTERN = '/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is';

    private const MARKDOWN_V2_SEARCH = [
        '\\', '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!',
    ];

    private const MARKDOWN_V2_REPLACE = [
        '\\\\', '\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!',
    ];

    abstract public function send(int $to, string $msg): void;

    /**
     * Escape plain text characters reserved by Telegram MarkdownV2.
     */
    protected static function escapeMarkdownV2Text(string $text): string
    {
        return str_replace(self::MARKDOWN_V2_SEARCH, self::MARKDOWN_V2_REPLACE, $text);
    }

    /**
     * Normalize HTML notifications (anchors, breaks, tables) into IM-friendly formatting.
     *
     * @param string $mode 'markdownv2' (Telegram), 'markdown' (Discord), or 'slack' (Slack mrkdwn)
     */
    public static function formatHtmlMessage(string $msg, string $mode = 'markdown'): string
    {
        $hasHtmlStructure = preg_match_all(
            '/<(?:br|a|p|div|table|tr|td|th|b|strong|i|em|u|code|pre|blockquote|li)\b/i',
            $msg
        ) > 0;

        if (! $hasHtmlStructure) {
            return $mode === 'markdownv2' ? self::escapeMarkdownV2Text($msg) : $msg;
        }

        $result = '';
        $offset = 0;

        if (preg_match_all(self::ANCHOR_TAG_PATTERN, $msg, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $idx => $fullMatch) {
                $matchStart = $fullMatch[1];
                if ($matchStart > $offset) {
                    $result .= self::formatPlainChunk(substr($msg, $offset, $matchStart - $offset), $mode);
                }

                $rawUrl = html_entity_decode($matches[2][$idx][0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $rawLabel = self::cleanPlainHtml($matches[3][$idx][0]);
                $result .= self::formatLink($rawLabel, $rawUrl, $mode);

                $offset = $matchStart + strlen($fullMatch[0]);
            }
        }

        if ($offset < strlen($msg)) {
            $result .= self::formatPlainChunk(substr($msg, $offset), $mode);
        }

        return trim($result, "\r\n");
    }

    private static function formatPlainChunk(string $chunk, string $mode): string
    {
        $clean = self::cleanPlainHtml($chunk);
        return $mode === 'markdownv2' ? self::escapeMarkdownV2Text($clean) : $clean;
    }

    private static function cleanPlainHtml(string $html): string
    {
        $hadTags = preg_match_all('/<\/?[a-z][a-z0-9]*\b[^>]*>/i', $html) > 0;
        if (! $hadTags) {
            return $html;
        }

        $normalized = (string) preg_replace('/<\/t[dh]>\s*<t[dh]\b[^>]*>/i', ' | ', $html);
        $normalized = (string) preg_replace('/<br\s*\/?>/i', "\n", $normalized);
        $normalized = (string) preg_replace('/<\/(?:tr|p|div|table|li|h[1-6])>/i', "\n", $normalized);
        $normalized = (string) preg_replace(self::KNOWN_HTML_TAGS_PATTERN, '', $normalized);
        $normalized = (string) preg_replace("/\n{3,}/", "\n\n", $normalized);

        return html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function formatLink(string $label, string $url, string $mode): string
    {
        if ($url === '') {
            return $mode === 'markdownv2' ? self::escapeMarkdownV2Text($label) : $label;
        }

        return match ($mode) {
            'markdownv2' => '[' . self::escapeMarkdownV2Text($label) . '](' . str_replace(['\\', ')'], ['\\\\', '\\)'], $url) . ')',
            'slack' => '<' . $url . '|' . $label . '>',
            default => '[' . $label . '](' . $url . ')',
        };
    }
}

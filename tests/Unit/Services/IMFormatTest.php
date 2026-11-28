<?php

/**
 * IM HTML formatting tests using Pest
 */

use App\Services\IM\Base;

require_once __DIR__ . '/../../../app/predefine.php';

describe('IM Base::formatHtmlMessage', function () {
    it('converts ticket HTML anchor tags to clickable MarkdownV2 links for Telegram', function () {
        $input = '管理员，有人回复了 <a href="https://dashboard.ssrr.uk/admin/ticket/41/view">#41</a> 工单，请你及时处理。';
        $output = Base::formatHtmlMessage($input, 'markdownv2');

        expect($output)
            ->toBe('管理员，有人回复了 [\#41](https://dashboard.ssrr.uk/admin/ticket/41/view) 工单，请你及时处理。')
            ->not->toContain('<a ')
            ->not->toContain('</a>');
    });

    it('converts links appropriately for Discord and Slack', function () {
        $input = '你好，有人回复了<a href="https://example.com/user/ticket/5/view">工单</a>，请你查看。';

        expect(Base::formatHtmlMessage($input, 'markdown'))
            ->toBe('你好，有人回复了[工单](https://example.com/user/ticket/5/view)，请你查看。')
            ->and(Base::formatHtmlMessage($input, 'slack'))
            ->toBe('你好，有人回复了<https://example.com/user/ticket/5/view|工单>，请你查看。');
    });

    it('cleans HTML tables and br tags in cron finance notifications', function () {
        $input = '<table><tr><td>金额</td><td>用户ID</td></tr><tr><td>10.00</td><td>1</td></tr></table><br>昨日总收入笔数：1';
        $output = Base::formatHtmlMessage($input, 'markdownv2');

        expect($output)
            ->toContain('金额 \| 用户ID')
            ->toContain('10\.00 \| 1')
            ->toContain('昨日总收入笔数：1')
            ->not->toContain('<table>')
            ->not->toContain('<br>');
    });

    it('preserves plain text with comparison operators and escapes MarkdownV2 characters', function () {
        $input = '节点 HK-01_VIP (1.2.3.4) 掉线了! balance < 5';
        $output = Base::formatHtmlMessage($input, 'markdownv2');

        expect($output)->toBe('节点 HK\-01\_VIP \(1\.2\.3\.4\) 掉线了\! balance < 5');
    });
});
